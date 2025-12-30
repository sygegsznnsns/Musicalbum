<?php
/**
 * 功能：
 * 记录用户浏览文章行为
 * 用于后续推荐分析
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Musicalbum_User_Behavior_Tracker {

    public function __construct() {
        add_action( 'wp', array( $this, 'track_view' ) );
    }

    public function track_view() {
        if ( ! is_single() || ! is_user_logged_in() ) return;

        global $post;
        if ( $post->post_type !== 'post' ) return;

        $user_id = get_current_user_id();
        $views   = get_user_meta( $user_id, '_musicalbum_viewed_posts', true );

        if ( ! is_array( $views ) ) {
            $views = array();
        }

        if ( ! in_array( $post->ID, $views ) ) {
            $views[] = $post->ID;
            update_user_meta( $user_id, '_musicalbum_viewed_posts', $views );
        }
    }
}
