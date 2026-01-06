<?php
/**
 * feedback.php
 * 功能：处理用户对音乐剧“不感兴趣”的标记
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 获取用户不感兴趣列表
 */
function msr_get_not_interested_list( $user_id ) {
    $list = get_user_meta( $user_id, 'msr_not_interested', true );
    return is_array( $list ) ? $list : [];
}

/**
 * 添加不感兴趣音乐剧
 */
function msr_add_not_interested( $user_id, $musical_name ) {
    $list = msr_get_not_interested_list( $user_id );

    if ( ! in_array( $musical_name, $list, true ) ) {
        $list[] = $musical_name;
        update_user_meta( $user_id, 'msr_not_interested', $list );
    }
}


/**
 * AJAX 处理“不感兴趣”请求
 */
add_action('wp_ajax_musicalbum_not_interested', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    $user_id = get_current_user_id();
    $musical_title = sanitize_text_field($_POST['musical_title']);

    $list = get_user_meta($user_id, 'musicalbum_not_interested', true);
    if (!is_array($list)) {
        $list = array();
    }

    if (!in_array($musical_title, $list)) {
        $list[] = $musical_title;
        update_user_meta($user_id, 'musicalbum_not_interested', $list);
    }

    wp_send_json_success();
});
