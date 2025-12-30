<?php
/**
 * page-recommend.php
 * 功能：渲染音乐剧推荐页面
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function msr_render_recommend_page() {

    if ( ! is_user_logged_in() ) {
        return '<p>请先登录查看推荐。</p>';
    }

    $user_id = get_current_user_id();

    // 使用你已经实现的统一推荐入口
    $recommendations = musicalbum_get_recommendations( $user_id, 10 );

    if ( empty( $recommendations ) ) {
        return '<p>暂无推荐内容。</p>';
    }

    ob_start();
    ?>

    <h2>为你推荐的音乐剧</h2>

    <?php foreach ( $recommendations as $post ) : ?>
        <article style="margin-bottom: 1em;">
            <h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>

            <p>
                推荐理由：  
                <?php
                if ( in_array( $post->ID, musicalbum_get_user_viewing_history( $user_id ), true ) ) {
                    echo '与你观演过的音乐剧相关';
                } else {
                    echo '近期较受关注的音乐剧';
                }
                ?>
            </p>

            <form method="post">
                <input type="hidden" name="musical_id" value="<?php echo esc_attr( $post->ID ); ?>">
                <button type="submit" name="musicalbum_not_interested">
                    不感兴趣
                </button>
            </form>
        </article>
        <hr>
    <?php endforeach; ?>

    <?php
    return ob_get_clean();
}

// 注册简码
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );
