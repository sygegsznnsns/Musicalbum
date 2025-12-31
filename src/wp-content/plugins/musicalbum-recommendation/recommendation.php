<?php
/**
 * 推荐核心逻辑
 */

/**
 * 获取用户观演过的剧目标题列表
 */
function musicalbum_get_user_viewing_history_titles($user_id) {

    $args = array(
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $query = new WP_Query($args);
    $titles = array();

    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            $title = get_the_title($post_id);
            if ($title) {
                $titles[] = $title;
            }
        }
    }

    return array_unique($titles);
}

/**
 * 基于其他用户的观演记录推荐剧目
 */
function musicalbum_recommend_by_crowd($user_id, $limit = 10) {

    $viewed_titles = musicalbum_get_user_viewing_history_titles($user_id);

    if (empty($viewed_titles)) {
        return array();
    }

    // 查找其他用户的观演记录
    $args = array(
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author__not_in' => array($user_id),
    );

    $query = new WP_Query($args);
    $counter = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $title = get_the_title();

            // 排除自己已经看过的
            if (in_array($title, $viewed_titles, true)) {
                continue;
            }

            if (!isset($counter[$title])) {
                $counter[$title] = 0;
            }
            $counter[$title]++;
        }
        wp_reset_postdata();
    }

    arsort($counter);

    $results = array();
    foreach ($counter as $title => $count) {
        $results[] = array(
            'musical' => $title,
            'reason'  => '有 ' . $count . ' 位用户也观看过该剧目',
        );
        if (count($results) >= $limit) {
            break;
        }
    }

    return $results;
}

/**
 * 近期热门观演剧目（基于观演记录数量）
 */
function musicalbum_recommend_trending($limit = 10) {

    $args = array(
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );

    $query = new WP_Query($args);
    $counter = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $title = get_the_title();

            if (!isset($counter[$title])) {
                $counter[$title] = 0;
            }
            $counter[$title]++;
        }
        wp_reset_postdata();
    }

    arsort($counter);

    $results = array();
    foreach ($counter as $title => $count) {
        $results[] = array(
            'musical' => $title,
            'reason'  => '近期被记录 ' . $count . ' 次观演',
        );
        if (count($results) >= $limit) {
            break;
        }
    }

    return $results;
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


/**
 * 基于用户关注演员推荐音乐剧
 *
 * @param int $user_id
 * @param int $limit
 * @return array
 */
function musicalbum_recommend_by_favorite_actors( $user_id, $limit = 10 ) {

    $actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    if ( empty( $actors ) || ! is_array( $actors ) ) {
        return [];
    }

    $results = [];

    foreach ( $actors as $actor_name ) {

        // 👉 用 saoju API 查演员相关音乐剧
        $musicals = msr_saoju_get_musicals_by_actor_name( $actor_name );

        foreach ( $musicals as $musical_name ) {
            $results[] = [
                'musical' => $musical_name,
                'reason'  => '包含你关注的演员：' . $actor_name,
            ];
        }
    }

    // 去重
    $results = array_map(
        'unserialize',
        array_unique( array_map( 'serialize', $results ) )
    );

    return array_slice( $results, 0, $limit );
}
