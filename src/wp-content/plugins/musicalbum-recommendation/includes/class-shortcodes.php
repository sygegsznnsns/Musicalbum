<?php
/**
 * class-shortcodes.php
 * 
 * 功能：该文件定义短代码功能，用于在前端页面显示推荐内容。
 */


defined('ABSPATH') || exit;

class Musicalbum_Shortcodes {

    public static function render_recommendations($atts) {

        if (!is_user_logged_in()) {
            return '<p>请登录后查看推荐内容。</p>';
        }

        $atts = shortcode_atts([
            'limit' => 10
        ], $atts);

        $posts = Musicalbum_Recommendation_Engine::get_recommendations(
            get_current_user_id(),
            intval($atts['limit'])
        );

        if (empty($posts)) {
            return '<p>暂无推荐内容。</p>';
        }

        ob_start();
        echo '<ul class="musicalbum-recommendations">';
        foreach ($posts as $post) {
            echo '<li><a href="' . get_permalink($post) . '">' . esc_html($post->post_title) . '</a></li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }
}
