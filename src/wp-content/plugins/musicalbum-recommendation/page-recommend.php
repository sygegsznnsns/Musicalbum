<?php
/**
 * page-recommend.php
 * 功能：渲染基于观演记录 + 喜欢演员的音乐剧推荐页面
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 页面渲染函数
 */
function msr_render_recommend_page() {

    if ( ! is_user_logged_in() ) {
        return '<p>请先登录查看推荐。</p>';
    }

    $user_id = get_current_user_id();

    /**
     * =========
     * 处理演员关注 / 取消关注（POST）
     * =========
     */
    $favorite_actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    if ( ! is_array( $favorite_actors ) ) {
        $favorite_actors = [];
    }

    // 新增关注演员
    if ( isset( $_POST['new_actor'] ) && ! empty( $_POST['new_actor'] ) ) {
        $new_actor = sanitize_text_field( $_POST['new_actor'] );
        if ( ! in_array( $new_actor, $favorite_actors, true ) ) {
            $favorite_actors[] = $new_actor;
            update_user_meta( $user_id, 'musicalbum_favorite_actors', $favorite_actors );
        }
    }

    // 取消关注演员
    if ( isset( $_POST['remove_actor'] ) ) {
        $remove_actor = sanitize_text_field( $_POST['remove_actor'] );
        $favorite_actors = array_values(
            array_diff( $favorite_actors, [ $remove_actor ] )
        );
        update_user_meta( $user_id, 'musicalbum_favorite_actors', $favorite_actors );
    }

    /**
     * =========
     * 获取推荐结果
     * =========
     */

    // 1. 基于其他用户的观演记录
    $personal = musicalbum_recommend_by_crowd( $user_id, 10 );

    // 2. 热门观演
    $trending = musicalbum_recommend_trending( 10 );

    // 3. 基于关注演员的推荐
    $actor_recommend = musicalbum_recommend_by_favorite_actors( $user_id, 10 );

    if ( empty( $personal ) && empty( $trending ) && empty( $actor_recommend ) ) {
        return '<p>暂无推荐内容。</p>';
    }

    ob_start();
    ?>

    <style>
        .msr-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .msr-item {
            border: 1px solid #ddd;
            padding: 12px;
            background: #fff;
            box-sizing: border-box;
        }

        .msr-item h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
        }

        .msr-item form {
            margin-top: 8px;
        }
    </style>

    <h2>为你推荐的音乐剧</h2>

    <!-- ===================== -->
    <!-- 喜欢的演员管理 -->
    <!-- ===================== -->

    <h3>你关注的演员</h3>

    <?php if ( empty( $favorite_actors ) ) : ?>
        <p>你还没有关注任何演员。</p>
    <?php else : ?>
        <ul>
            <?php foreach ( $favorite_actors as $actor ) : ?>
                <li>
                    <?php echo esc_html( $actor ); ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="remove_actor" value="<?php echo esc_attr( $actor ); ?>">
                        <button type="submit">取消关注</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" style="margin-bottom:24px;">
        <input type="text" name="new_actor" placeholder="输入演员姓名">
        <button type="submit">关注演员</button>
    </form>

    <!-- ===================== -->
    <!-- 演员相关推荐 -->
    <!-- ===================== -->

    <?php if ( ! empty( $actor_recommend ) ) : ?>
        <h3>你可能喜欢的演员相关剧目</h3>

        <div class="msr-grid">
            <?php foreach ( $actor_recommend as $item ) : ?>
                <div class="msr-item">
                    <h4><?php echo esc_html( $item['musical'] ); ?></h4>
                    <p><?php echo esc_html( $item['reason'] ); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===================== -->
    <!-- 协同过滤推荐 -->
    <!-- ===================== -->

    <?php if ( ! empty( $personal ) ) : ?>
        <h3>基于其他观众的观演记录</h3>

        <div class="msr-grid">
            <?php foreach ( $personal as $item ) : ?>
                <div class="msr-item">
                    <h4><?php echo esc_html( $item['musical'] ); ?></h4>
                    <p><?php echo esc_html( $item['reason'] ); ?></p>

                    <form method="post">
                        <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                        <button type="submit" name="musicalbum_not_interested">不感兴趣</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ===================== -->
    <!-- 热门推荐 -->
    <!-- ===================== -->

    <?php if ( ! empty( $trending ) ) : ?>
        <h3>近期热门观演</h3>

        <div class="msr-grid">
            <?php foreach ( $trending as $item ) : ?>
                <div class="msr-item">
                    <h4><?php echo esc_html( $item['musical'] ); ?></h4>
                    <p><?php echo esc_html( $item['reason'] ); ?></p>

                    <form method="post">
                        <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                        <button type="submit" name="musicalbum_not_interested">不感兴趣</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}

/**
 * 注册简码（保持你的原始简码名）
 */
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );
