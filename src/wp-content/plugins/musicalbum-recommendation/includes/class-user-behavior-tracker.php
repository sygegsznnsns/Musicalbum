<?php
/**
 * class-user-behavior-tracker.php
 * 
 * 功能：该文件负责追踪用户的行为，包括浏览文章、发表评论和收藏帖子。
 */


defined('ABSPATH') || exit;

class Musicalbum_User_Behavior_Tracker {

    public static function init() {
        add_action('wp', [self::class, 'track_view']);
    }

    public static function track_view() {
        if (!is_singular('post') || !is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $post_id = get_the_ID();

        $views = get_user_meta($user_id, 'musicalbum_viewed_posts', true);
        if (!is_array($views)) {
            $views = [];
        }

        $views[$post_id] = ($views[$post_id] ?? 0) + 1;
        update_user_meta($user_id, 'musicalbum_viewed_posts', $views);
    }
}
