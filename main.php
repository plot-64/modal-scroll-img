<?php
/*
Plugin Name: modal scroll img
Description: スクロールで広告がモーダルで出る
*/

add_action( 'admin_menu', 'my_add_admin_menu' );
/**
 * 「設定」にメニューを追加
 */
function my_add_admin_menu() {
  add_menu_page(
    'モーダル画像', // 設定画面のページタイトル.
    'モーダル画像', // 管理画面メニューに表示される名前.
    'manage_options',
    'my-original-menu', // メニューのスラッグ
    'my_original_menu_page' // この関数を呼び出す
  );
}

/**
 * メニューページの中身を作成
 */
function my_original_menu_page() {
  // POSTデータの保存処理
  if (
    !empty($_POST['image_path'])
    && !empty($_POST['top'])
    && !empty($_POST['cat_id'])
    && !empty($_POST['url'])
  ) {
    update_option('modal_scroll', $_POST);
  }

  // 保存されたデータの取得
  $data = get_option('modal_scroll');

  // 未定義エラー対策（初期化）
  $saved_image_path = !empty($data['image_path']) ? $data['image_path'] : '';
  $saved_top        = !empty($data['top']) ? $data['top'] : '';
  $saved_cats       = !empty($data['cat_id']) && is_array($data['cat_id']) ? $data['cat_id'] : [];
  $saved_url        = !empty($data['url']) ? $data['url'] : '';
  ?>

  <div class="wrap">
    <h1>モーダル画像設定</h1>
    <form method="POST" action="">
        <p>
          <label>広告画像 /wp-content/uploads/ </label>
          <input type="text" name="image_path" value="<?=esc_attr($saved_image_path)?>">
        </p>
        
        <p>
          <label>スクロールの距離 </label>
          <input type="number" name="top" value="<?=esc_attr($saved_top)?>">
        </p>
        
        <p>
          <label>カテゴリ選択(複数可) </label>
          <select name="cat_id[]" multiple>
          <?php
          $categories = get_categories();
          foreach( $categories as $category ) { 
            // ループの中で現在のカテゴリIDが保存データ内にあるか判定
            if (in_array($category->cat_ID, $saved_cats)) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            ?>
            <option value="<?=esc_attr($category->cat_ID)?>" <?=$selected?>>
                <?=esc_html($category->name)?>
            </option>
          <?php } ?>
          </select>
        </p>
        
        <p>
          <label>広告リンク先 </label>
          <input type="url" name="url" value="<?=esc_url($saved_url)?>">
        </p>

        <p><input type="submit" value="登録"></p>
    </form>
  </div>
  <?php
}

/**
 * スクリプトの読み込み
 */
function modal_scroll_img_scripts() {
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
    );
    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        null,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'modal_scroll_img_scripts' );

/**
 * フッターへのモーダルHTML・JS出力
 */
add_action('wp_footer', function () {
    // 保存されたデータを取得
    $data = get_option('modal_scroll');
    if (empty($data)) {
        return; // データがなければ何も出力しない
    }

    // 各変数の安全な取得
    $image_path = !empty($data['image_path']) ? $data['image_path'] : '';
    $scroll_top = !empty($data['top']) ? intval($data['top']) : 400;
    $saved_cats = !empty($data['cat_id']) && is_array($data['cat_id']) ? $data['cat_id'] : [];
    $ad_url     = !empty($data['url']) ? $data['url'] : '#';

    // カテゴリ制限の判定（投稿ページの場合のみチェック）
    if (is_single()) {
        $post_cats = wp_get_post_categories(get_the_ID());
        // 設定されたカテゴリと、現在の投稿のカテゴリに重複があるか確認
        $has_match = false;
        foreach ($post_cats as $cat_id) {
            if (in_array($cat_id, $saved_cats)) {
                $has_match = true;
                break;
            }
        }
        // 一致するカテゴリがなければモーダルを出力せずに終了
        if (!$has_match) {
            return;
        }
    }

    // アルファベットのみのユニークなIDを生成する
    $unique_id = wp_generate_password( 8, false, false );

    // アップロードフォルダのURLを取得してフルパスを組み立てる
    $upload_dir = wp_upload_dir();
    $image_url = $upload_dir['baseurl'] . '/' . $image_path;

    // HTMLとJavaScriptの出力
    ?>
    <div class="modal fade" id="exampleModal_<?=$unique_id?>" tabindex="-1" aria-labelledby="exampleModalLabel_<?=$unique_id?>" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel_<?=$unique_id?>">広告です</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <a href="<?=esc_url($ad_url)?>" target="_blank" rel="noopener noreferrer">
              <img src="<?=esc_url($image_url)?>" alt="広告画像" style="max-width: 100%; height: auto;">
            </a>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    window.addEventListener("load", function() {
        var hasOpened = false;
        var targetScrollAmount = <?=intval($scroll_top)?>; 
        
        var modalElement = document.getElementById("exampleModal_<?=$unique_id?>");
        if (!modalElement) return;

        var myModal = new bootstrap.Modal(modalElement);

        modalElement.querySelectorAll(".btn-close, .btn-secondary").forEach(function(button) {
            button.addEventListener("click", function() { myModal.hide(); });
        });

        jQuery(window).on("scroll.modal_<?=$unique_id?>", function(){
            var currentScroll = jQuery(this).scrollTop();

            if (!hasOpened && currentScroll >= targetScrollAmount) {
                hasOpened = true;
                myModal.show();
                jQuery(window).off("scroll.modal_<?=$unique_id?>");
            }
        });
    });
    </script>
    <?php
});