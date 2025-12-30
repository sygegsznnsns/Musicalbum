<?php
/**
 * class-recommendation-engine.php
 * 
 * 功能：该文件实现推荐引擎，基于用户行为生成个性化推荐内容。
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // 禁止直接访问
}


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

        foreach ($query->posts as &$post) {
            $post->recommend_score = self::calculate_score($post->ID, $profile, $user_id);
        }

        usort($query->posts, function ($a, $b) {
            return $b->recommend_score <=> $a->recommend_score;
        });

        return $query->posts;
    }

    private static function build_user_profile($user_id) {
        $views = get_user_meta($user_id, 'musicalbum_viewed_posts', true);
        // 如果没有浏览历史，使用默认规则
        if (empty($views) || !is_array($views)) {
            return self::get_default_profile();
        }    

        $profile = [];

        foreach ($views as $post_id => $count) {
            $terms = wp_get_post_terms($post_id, 'musical');
            foreach ($terms as $term) {
                $profile[$term->term_id] = ($profile[$term->term_id] ?? 0) + $count;
            }
        }

        return $profile;
    }

    private static function get_default_profile() {
    // 方法1：获取最热门的标签
    $popular_terms = get_terms([
        'taxonomy' => 'musical',
        'orderby' => 'count',
        'order' => 'DESC',
        'number' => 3,
    ]);
    
    $profile = [];
    foreach ($popular_terms as $term) {
        $profile[$term->term_id] = 1; // 给热门标签基础权重
    }
    
    return $profile;
    }

    private static function calculate_score($post_id, $profile, $user_id) {

        $score = 0;
        $weight_tag       = get_option('musicalbum_recommendation_weight_tag', 5);
        $weight_behavior  = get_option('musicalbum_recommendation_weight_behavior', 3);
        $weight_community = get_option('musicalbum_recommendation_weight_community', 2);

        $terms = wp_get_post_terms($post_id, 'musical');
        foreach ($terms as $term) {
            if (isset($profile[$term->term_id])) {
                $score += $profile[$term->term_id] * $weight_tag;
            }
        }

        $score += self::get_user_behavior_weight($user_id) * $weight_behavior;
        $score += self::get_community_weight($user_id) * $weight_community;

        return $score;
    }

    private static function get_user_behavior_weight($user_id) {
        return count((array) get_user_meta($user_id, 'musicalbum_viewed_posts', true));
    }

    private static function get_community_weight($user_id) {
        if (!class_exists('Musicalbum_Community_Adapter')) {
            return 0;
        }
        return Musicalbum_Community_Adapter::get_user_activity_score($user_id);
    }

    public static function rest_get_recommendations() {
        return self::get_recommendations(get_current_user_id());
    }
}

