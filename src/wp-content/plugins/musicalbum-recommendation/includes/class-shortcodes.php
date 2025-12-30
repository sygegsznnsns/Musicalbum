<?php
/**
 * class-shortcodes.php
 *
 * 功能：
 * 定义前台短码，用于展示智能推荐结果。
 * - 优先展示推荐系统返回的帖子
 * - 当推荐结果为空时，自动回退为最新文章
 * - 使用当前主题的模板（The Loop + get_template_part）进行渲染，
 *   以保证与 Astra 等主题样式完全一致
 */

defined('ABSPATH') || exit;

class Musicalbum_Shortcodes {

    /**
     * 渲染智能推荐短码
     *
     * 短码：
     * [musicalbum_smart_recommendations limit="10"]
     */
    public static function render_recommendations($atts) {

        if (!is_user_logged_in()) {
            return '<p>请登录后查看推荐内容。</p>';
        }

        $atts = shortcode_atts(
            [
                'limit' => 10,
            ],
            $atts,
            'musicalbum_smart_recommendations'
        );

        $limit   = intval($atts['limit']);
        $user_id = get_current_user_id();

        /**
         * Step 1：从推荐引擎获取推荐帖子
         * 这里假设返回的是 WP_Post[] 或 post ID 数组
         */
        $recommended_posts = Musicalbum_Recommendation_Engine::get_recommendations(
            $user_id,
            $limit
        );

        /**
         * Step 2：构造 WP_Query 参数
         * - 有推荐结果：按推荐顺序展示
         * - 无推荐结果：回退为最新文章
         */
        if (!empty($recommended_posts)) {

            // 兼容返回 post 对象或 ID
            $post_ids = array_map(function ($post) {
                return is_object($post) ? $post->ID : intval($post);
            }, $recommended_posts);

            $query_args = [
                'post_type'      => 'post',
                'post__in'       => $post_ids,
                'orderby'        => 'post__in',
                'posts_per_page' => $limit,
            ];

        } else {

            // 回退方案：按最新文章显示
            $query_args = [
                'post_type'      => 'post',
                'posts_per_page' => $limit,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
        }

        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            return '<p>当前暂无可展示的内容。</p>';
        }

        /**
         * Step 3：使用主题模板进行渲染
         * 关键点：
         * - 不自己拼 article HTML
         * - 完全交给主题（如 Astra）处理结构与样式
         */
        ob_start();

        echo '<div class="musicalbum-smart-recommendations ast-row">';

        while ($query->have_posts()) {
            $query->the_post();

            /**
             * 这一句是“长成 Astra 那种文章卡片”的核心
             * Astra 会在 template-parts/content-*.php 中输出你看到的 <article ...>
             */
            get_template_part('template-parts/content', get_post_type());
        }

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }
}
