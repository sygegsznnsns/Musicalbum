<?php
/**
 * class-recommendation-engine.php
 * 
 * 功能：该文件实现推荐引擎，基于用户行为生成个性化推荐内容。
 * 推荐逻辑：
 * 1. 根据用户行为（浏览、评论、收藏）推荐文章。
 * 2. 如果没有推荐结果，推荐最新发布的文章。
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // 禁止直接访问
}

class Musicalbum_Recommendation_Engine {

    // 获取推荐内容
    public function get_recommendations($user_id, $limit = 5) {
        // 获取用户的浏览记录、评论记录和收藏记录
        $viewed_posts = (new Musicalbum_User_Behavior_Tracker())->get_user_viewed_posts($user_id);
        $commented_posts = (new Musicalbum_User_Behavior_Tracker())->get_user_commented_posts($user_id);
        $favorites = (new Musicalbum_User_Behavior_Tracker())->get_user_favorites($user_id);

        // 合并所有推荐的帖子
        $recommended_posts = array_merge($viewed_posts, $commented_posts, $favorites);
        $recommended_posts = array_unique($recommended_posts);

        // 如果没有推荐内容，推荐最新的帖子
        if ( empty($recommended_posts) ) {
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => $limit, // 默认显示最新 5 篇
                'orderby' => 'date', // 按日期排序
                'order' => 'DESC'  // 从最新到最旧
            );
            return new WP_Query($args);
        }

        // 查询推荐的帖子
        $args = array(
            'post_type' => 'post',
            'post__in' => $recommended_posts,
            'posts_per_page' => $limit, // 使用传入的 limit
            'orderby' => 'post_date', // 按日期排序
            'order' => 'DESC' // 按降序显示
        );
        return new WP_Query($args);
    }
}
