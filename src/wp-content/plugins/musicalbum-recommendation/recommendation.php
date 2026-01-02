<?php
/**
 * 推荐核心逻辑
 */

/**
 * 获取用户观演过的剧目标题列表
 */
function musicalbum_get_user_viewing_history_titles($user_id) {
    $args = array(
        'post_type' => 'viewing_record',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
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
 * 基于其他用户的观演记录推荐剧目（协同过滤）
 */
function musicalbum_recommend_by_crowd( $user_id, $limit = 10 ) {

    // 1. 当前用户已看过的音乐剧
    $viewed_titles = musicalbum_get_user_viewing_history_titles( $user_id );
    if ( empty( $viewed_titles ) ) {
        return [];
    }

    // 2. 查询其他用户的观演记录
    $args = [
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'author__not_in' => [ $user_id ],
    ];

    $query   = new WP_Query( $args );
    $counter = [];

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            // ✅ 正确做法：从 meta 里取音乐剧名
            $musical_title = get_post_meta( get_the_ID(), 'musical_title', true );

            if ( empty( $musical_title ) ) {
                continue;
            }

            // 如果你已经看过，跳过
            if ( in_array( $musical_title, $viewed_titles, true ) ) {
                continue;
            }

            if ( ! isset( $counter[ $musical_title ] ) ) {
                $counter[ $musical_title ] = 0;
            }

            $counter[ $musical_title ]++;
        }
        wp_reset_postdata();
    }

    // 3. 按“被多少人看过”排序
    arsort( $counter );

    // 4. 生成推荐结果
    $results = [];
    foreach ( $counter as $title => $count ) {
        $results[] = [
            'musical' => $title,
            'reason'  => '有 ' . $count . ' 位与你兴趣相似的用户观看过',
        ];

        if ( count( $results ) >= $limit ) {
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
        'post_type' => 'viewing_record',
        'post_status' => 'publish',
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
            'reason' => '近期被记录 ' . $count . ' 次观演',
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
    $actor_terms = wp_get_object_terms($viewed, 'actor', array('fields' => 'ids'));
    $args = array(
        'post_type' => 'musical',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'post__not_in' => $exclude_ids,
        'tax_query' => !empty($actor_terms) ? array(
            array(
                'taxonomy' => 'actor',
                'field' => 'term_id',
                'terms' => $actor_terms,
            )
        ) : array(),
        'orderby' => 'comment_count',
        'order' => 'DESC',
    );
    return get_posts($args);
}

/**
 * 基于用户关注演员，通过 saoju API 推荐相关音乐剧
 * 数据链路：演员 → 卡司 → 角色 → 音乐剧
 *
 * @param int $user_id
 * @param int $per_actor_limit 每个演员最多推荐数量
 * @return array
 */
function musicalbum_recommend_by_favorite_actors( $user_id, $per_actor_limit = 3 ) {

    $cache_key = 'msr_actor_recommend_' . $user_id;
    $cached = get_transient( $cache_key );

    if ( $cached !== false && is_array( $cached ) ) {
        return $cached;
    }

    $favorite_actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    if ( empty( $favorite_actors ) || ! is_array( $favorite_actors ) ) {
        return [];
    }

    /**
     * =========
     * Step 0：请求全部 API（每个只请求一次）
     * =========
     */
    $artist_data       = wp_remote_retrieve_body( wp_remote_get( 'https://y.saoju.net/yyj/api/artist/' ) );
    $musicalcast_data  = wp_remote_retrieve_body( wp_remote_get( 'https://y.saoju.net/yyj/api/musicalcast/' ) );
    $role_data         = wp_remote_retrieve_body( wp_remote_get( 'https://y.saoju.net/yyj/api/role/' ) );
    $musical_data      = wp_remote_retrieve_body( wp_remote_get( 'https://y.saoju.net/yyj/api/musical/' ) );

    $artists      = json_decode( $artist_data, true );
    $musicalcasts = json_decode( $musicalcast_data, true );
    $roles        = json_decode( $role_data, true );
    $musicals     = json_decode( $musical_data, true );

    if ( ! is_array( $artists ) || ! is_array( $musicalcasts ) || ! is_array( $roles ) || ! is_array( $musicals ) ) {
        return [];
    }

    /**
     * =========
     * Step 1：建立索引表（提升查找效率）
     * =========
     */

    // artist_name => artist_id
    $artist_index = [];
    foreach ( $artists as $item ) {
        $artist_index[ $item['fields']['name'] ] = $item['pk'];
    }

    // role_id => musical_id
    $role_to_musical = [];
    foreach ( $roles as $item ) {
        $role_to_musical[ $item['pk'] ] = $item['fields']['musical'];
    }

    // musical_id => musical_name
    $musical_index = [];
    foreach ( $musicals as $item ) {
        $musical_index[ $item['pk'] ] = $item['fields']['name'];
    }

    /**
     * =========
     * Step 2：按演员生成推荐结果
     * =========
     */
    $results = [];

    foreach ( $favorite_actors as $actor_name ) {

        if ( ! isset( $artist_index[ $actor_name ] ) ) {
            continue;
        }

        $actor_id = $artist_index[ $actor_name ];

        // 找到该演员参与的角色 ID
        $role_ids = [];
        foreach ( $musicalcasts as $cast ) {
            if ( intval( $cast['fields']['artist'] ) === intval( $actor_id ) ) {
                $role_ids[] = $cast['fields']['role'];
            }
        }

        if ( empty( $role_ids ) ) {
            continue;
        }

        // 通过角色找到音乐剧 ID
        $musical_ids = [];
        foreach ( $role_ids as $role_id ) {
            if ( isset( $role_to_musical[ $role_id ] ) ) {
                $musical_ids[] = $role_to_musical[ $role_id ];
            }
        }

        $musical_ids = array_unique( $musical_ids );
        if ( empty( $musical_ids ) ) {
            continue;
        }

        $results[ $actor_name ] = [];

        foreach ( $musical_ids as $musical_id ) {

            if ( ! isset( $musical_index[ $musical_id ] ) ) {
                continue;
            }

            $results[ $actor_name ][] = [
                'musical_id' => $musical_id,
                'musical'    => $musical_index[ $musical_id ],
                'reason'     => '该音乐剧包含你关注的演员：' . $actor_name,
            ];

            if ( count( $results[ $actor_name ] ) >= $per_actor_limit ) {
                break;
            }
        }
    }

    set_transient( $cache_key, $results, DAY_IN_SECONDS );
    return $results;

}
