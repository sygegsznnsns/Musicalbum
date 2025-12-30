<?php
/**
 * class-recommendation-engine.php
 *
 * 功能：
 * 基于用户浏览行为与 musical taxonomy 构建兴趣画像，
 * 并据此计算推荐分数，输出推荐文章列表。
 */

defined('ABSPATH') || exit;

class Musicalbum_Recommendation_Engine {

    public static function init() {}

    public static function get_recommendations($user_id, $limit = 10) {

        $profile = self::build_user_profile($user_id);

        if (empty($profile)) {
            return [];
        }

        $args = [
            'post_type'      => 'post',
            'posts_per_page' => $limit,
            'tax_query'      => [
                [
                    'taxonomy' => 'musical',
                    'field'    => 'term_id',
                    'terms'    => array_keys($profile),
                ]
            ]
        ];

        $query = new WP_Query($args);

        if (empty($query->posts)) {
            return [];
        }

        foreach ($query->posts as &$post) {
            $post->recommend_score = self::calculate_score(
                $post->ID,
                $profile,
                $user_id
            );
        }

        usort($query->posts, function ($a, $b) {
            return $b->recommend_score <=> $a->recommend_score;
        });

        return $query->posts;
    }

    /**
     * 构建用户兴趣画像（term_id => 权重）
     */
    private static function build_user_profile($user_id) {

        $views = get_user_meta($user_id, 'musicalbum_viewed_posts', true);

        if (!is_array($views) || empty($views)) {
            return [];
        }

        $profile = [];

        foreach ($views as $post_id => $count) {

            $terms = wp_get_post_terms($post_id, 'musical');

            if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
                continue;
            }

            foreach ($terms as $term) {

                if (!is_object($term) || !isset($term->term_id)) {
                    continue;
                }

                $profile[$term->term_id] =
                    ($profile[$term->term_id] ?? 0) + intval($count);
            }
        }

        return $profile;
    }

    /**
     * 计算推荐分数
     */
    private static function calculate_score($post_id, $profile, $user_id) {

        $score = 0;

        $weight_tag       = intval(get_option('musicalbum_recommendation_weight_tag', 5));
        $weight_behavior  = intval(get_option('musicalbum_recommendation_weight_behavior', 3));
        $weight_community = intval(get_option('musicalbum_recommendation_weight_community', 2));

        $terms = wp_get_post_terms($post_id, 'musical');

        if (!is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                if (is_object($term) && isset($term->term_id) && isset($profile[$term->term_id])) {
                    $score += $profile[$term->term_id] * $weight_tag;
                }
            }
        }

        $score += self::get_user_behavior_weight($user_id) * $weight_behavior;
        $score += self::get_community_weight($user_id) * $weight_community;

        return $score;
    }

    private static function get_user_behavior_weight($user_id) {
        $views = get_user_meta($user_id, 'musicalbum_viewed_posts', true);
        return is_array($views) ? count($views) : 0;
    }

    private static function get_community_weight($user_id) {
        if (!class_exists('Musicalbum_Community_Adapter')) {
            return 0;
        }
        return intval(Musicalbum_Community_Adapter::get_user_activity_score($user_id));
    }
}
