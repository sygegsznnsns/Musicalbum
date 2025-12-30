<?php
/**
 * page-recommend.php
 * 功能：渲染基于观演记录的音乐剧推荐页面
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function msr_render_recommend_page() {

    if ( ! is_user_logged_in() ) {
        return '<p>请先登录查看推荐。</p>';
    }

    $user_id = get_current_user_id();

    // 1. 基于其他用户的观演记录推荐
    $personal = musicalbum_recommend_by_crowd( $user_id, 10 );

    // 2. 热门观演推荐
    $trending = musicalbum_recommend_trending( 10 );

    if ( empty( $personal ) && empty( $trending ) ) {
        return '<p>暂无推荐内容。</p>';
    }

    ob_start();
    ?>

    <h2>为你推荐的音乐剧</h2>

    <?php if ( ! empty( $personal ) ) : ?>
        <h3>基于其他观众的观演记录</h3>

        <?php foreach ( $personal as $item ) : ?>
            <article style="margin-bottom: 1em;">
                <h4><?php echo esc_html( $item['musical'] ); ?></h4>
                <p><?php echo esc_html( $item['reason'] ); ?></p>

                <form method="post">
                    <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                    <button type="submit" name="musicalbum_not_interested">
                        不感兴趣
                    </button>
                </form>
            </article>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ( ! empty( $trending ) ) : ?>
        <h3>近期热门观演</h3>

        <?php foreach ( $trending as $item ) : ?>
            <article style="margin-bottom: 1em;">
                <h4><?php echo esc_html( $item['musical'] ); ?></h4>
                <p><?php echo esc_html( $item['reason'] ); ?></p>

                <form method="post">
                    <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                    <button type="submit" name="musicalbum_not_interested">
                        不感兴趣
                    </button>
                </form>
            </article>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}

// 注册简码（保持你原来的）
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );
