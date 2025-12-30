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

    $personal = msr_recommend_by_history( $user_id );
    $trending = msr_recommend_trending( $user_id );

    ob_start();
    ?>

    <h2>🎭 为你推荐的音乐剧</h2>

    <h3>基于你的观演历史</h3>
    <?php foreach ( $personal as $item ) : ?>
        <p>
            <strong><?php echo esc_html( $item['musical'] ); ?></strong><br>
            <?php echo esc_html( $item['reason'] ); ?>
        </p>
        <form method="post">
            <input type="hidden" name="musical" value="<?php echo esc_attr( $item['musical'] ); ?>">
            <button type="submit" name="msr_not_interested">不感兴趣</button>
        </form>
        <hr>
    <?php endforeach; ?>

    <h3>近期热门演出</h3>
    <?php foreach ( $trending as $item ) : ?>
        <p>
            <strong><?php echo esc_html( $item['musical'] ); ?></strong><br>
            <?php echo esc_html( $item['reason'] ); ?>
        </p>
        <form method="post">
            <input type="hidden" name="musical" value="<?php echo esc_attr( $item['musical'] ); ?>">
            <button type="submit" name="msr_not_interested">不感兴趣</button>
        </form>
        <hr>
    <?php endforeach; ?>

    <?php
    return ob_get_clean();
}
