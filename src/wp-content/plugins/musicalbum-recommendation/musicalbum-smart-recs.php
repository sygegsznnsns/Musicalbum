<?php
/*
Plugin Name: Musicalbum Smart Recommendations
Description: 基于 CRP 与 YITH 的智能剧目推荐模块
Version: 0.4.0
Author: ji peng
*/

defined('ABSPATH') || exit;

final class Musicalbum_Smart_Recs {

    const VIEWING_CPT = 'musicalbum_viewing';

    public static function init() {
        add_action('init', [__CLASS__, 'register_viewing_cpt']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('rest_api_init', [__CLASS__, 'register_rest']);
        add_shortcode('musicalbum_recommendations', [__CLASS__, 'shortcode']);
    }

    public static function register_viewing_cpt() {
        register_post_type(self::VIEWING_CPT, [
            'label'        => '观演记录',
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'supports'     => ['title'],
        ]);
    }

    public static function enqueue_assets() {
        wp_enqueue_style(
            'musicalbum-recommendation',
            plugins_url('assets/recommendations.css', __FILE__),
            [],
            '0.4.0'
        );

        wp_enqueue_script(
            'musicalbum-recommendation',
            plugins_url('assets/recommendations.js', __FILE__),
            ['jquery'],
            '0.4.0',
            true
        );

        wp_localize_script('musicalbum-recommendation', 'MusicalbumRecs', [
            'rest_url' => rest_url('musicalbum/v1/recommendations'),
            'nonce'    => wp_create_nonce('wp_rest'),
        ]);
    }

    public static function register_rest() {
        register_rest_route('musicalbum/v1', '/recommendations', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'handle_recs'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handle_recs($request) {
        $post_id = intval($request['post_id'] ?? 0);
        $count   = max(1, intval($request['count'] ?? 6));

        $posts = self::get_recommendations($post_id, $count);

        $html = '<div class="musicalbum-recs">';
        if ($posts) {
            foreach ($posts as $p) {
                $html .= sprintf(
                    '<div class="rec-item"><a href="%s">%s</a></div>',
                    esc_url(get_permalink($p)),
                    esc_html(get_the_title($p))
                );
            }
        } else {
            $html .= '<div class="rec-empty">暂无推荐</div>';
        }
        $html .= '</div>';

        return ['html' => $html];
    }

    /**
     * 推荐核心逻辑（CRP → YITH → Fallback）
     */
    private static function get_recommendations($post_id, $count) {

        /* ① CRP 相似内容推荐 */
        if ($post_id && function_exists('crp_get_related_posts')) {
            $related = crp_get_related_posts([
                'postid' => $post_id,
                'limit'  => $count,
                'fields' => 'ids',
            ]);
            if (!empty($related)) {
                return $related;
            }
        }

        /* ② YITH 基于用户收藏的个性化推荐 */
        if (is_user_logged_in() && function_exists('YITH_WCWL')) {
            $wishlist = YITH_WCWL()->get_wishlist_detail();
            $liked_ids = wp_list_pluck($wishlist, 'prod_id');

            if (!empty($liked_ids)) {
                return self::recommend_by_taxonomy($liked_ids, $count);
            }
        }

        /* ③ 兜底：基于当前文章 taxonomy */
        if ($post_id) {
            return self::recommend_by_taxonomy([$post_id], $count);
        }

        return [];
    }

    /**
     * 根据一组 post ID 汇总 taxonomy 后推荐
     */
    private static function recommend_by_taxonomy(array $source_ids, $count) {
        $taxonomies = ['category', 'post_tag'];
        $tax_query = [];

        foreach ($taxonomies as $tax) {
            $terms = wp_get_object_terms($source_ids, $tax, ['fields' => 'ids']);
            if (!is_wp_error($terms) && $terms) {
                $tax_query[] = [
                    'taxonomy' => $tax,
                    'field'    => 'term_id',
                    'terms'    => $terms,
                ];
            }
        }

        if (!$tax_query) {
            return [];
        }

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $count,
            'post__not_in'   => $source_ids,
            'tax_query'      => array_merge(['relation' => 'OR'], $tax_query),
        ];

        return get_posts($args);
    }

    public static function shortcode($atts) {
        $atts = shortcode_atts([
            'post_id' => 0,
            'count'   => 6,
        ], $atts);

        $json = esc_attr(wp_json_encode($atts));

        return <<<HTML
<div class="musicalbum-recommendation-block" data-params='{$json}'>
    <div class="musicalbum-recs-loading">推荐加载中…</div>
</div>
HTML;
    }
}

Musicalbum_Smart_Recs::init();
