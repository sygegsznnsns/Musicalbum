<?php
/**
 * 推荐核心逻辑
 */

/**
 * 100% 对齐你现有代码的观演记录读取方式
 * 返回：音乐剧 post_id 数组
 */
function musicalbum_get_user_viewing_history($user_id) {

    $args = array(
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $musical_ids = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // ⚠️ 关键：与你 viewing-records.php 一致的字段读取方式
            // 这里假设你用的是 musical 字段（ACF 或 meta）
            if (function_exists('musicalbum_get_field')) {
                $musical_id = musicalbum_get_field(get_the_ID(), 'musical');
            } else {
                $musical_id = get_post_meta(get_the_ID(), 'musical', true);
            }

            if ($musical_id) {
                $musical_ids[] = intval($musical_id);
            }
        }
        wp_reset_postdata();
    }

    return array_unique($musical_ids);
}

/**
 * 获取用户不感兴趣列表
 */
function musicalbum_get_not_interested($user_id) {
    $list = get_user_meta($user_id, 'musicalbum_not_interested', true);
    return is_array($list) ? $list : array();
}

/**
 * 推荐音乐剧（最终统一入口）
 */
function musicalbum_get_recommendations($user_id, $limit = 10) {

    $viewed = musicalbum_get_user_viewing_history($user_id);
    $excluded = musicalbum_get_not_interested($user_id);

    $exclude_ids = array_merge($viewed, $excluded);

    // 1. 基于演员关联
    $actor_terms = wp_get_object_terms($viewed, 'actor', array('fields' => 'ids'));

    $args = array(
        'post_type'      => 'musical',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'post__not_in'   => $exclude_ids,
        'tax_query'      => !empty($actor_terms) ? array(
            array(
                'taxonomy' => 'actor',
                'field'    => 'term_id',
                'terms'    => $actor_terms,
            )
        ) : array(),
        'orderby' => 'comment_count',
        'order'   => 'DESC',
    );

    return get_posts($args);
}
