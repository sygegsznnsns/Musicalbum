<?php
/**
 * 功能：
 * 注册并渲染 [musicalbum_smart_recommendations] 短代码
 * 负责前端安全输出推荐文章列表
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Musicalbum_Shortcodes {

    public function __construct() {
        add_shortcode(
            'musicalbum_smart_recommendations',
            array( $this, 'render_recommendations' )
        );
    }

    public function render_recommendations( $atts ) {

        // 解析短代码参数
        $atts = shortcode_atts(
            array(
                'limit'    => 6,
                'fallback' => 'latest', // latest | popular（预留）
            ),
            $atts
        );

        $engine = new Musicalbum_Recommendation_Engine();
        $posts  = $engine->get_recommended_posts(
            get_current_user_id(),
            intval( $atts['limit'] ),
            $atts['fallback']
        );

        if ( empty( $posts ) ) {
            return '<p>暂无推荐内容。</p>';
        }

        ob_start();
        ?>
        <div class="musicalbum-recommendations">
            <h3>为你推荐</h3>
            <ul>
                <?php foreach ( $posts as $post ) : ?>
                    <li>
                        <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
                            <?php echo esc_html( get_the_title( $post->ID ) ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}
