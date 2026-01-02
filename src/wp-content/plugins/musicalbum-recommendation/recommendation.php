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
 * 基于其他用户的观演记录推荐剧目
 */
function musicalbum_recommend_by_crowd($user_id, $limit = 10) {
    $viewed_titles = musicalbum_get_user_viewing_history_titles($user_id);
    if (empty($viewed_titles)) {
        return array();
    }
    $args = array(
        'post_type' => 'viewing_record',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'author__not_in' => array($user_id),
    );
    $query = new WP_Query($args);
    $counter = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $title = get_the_title();
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
            'reason' => '有 ' . $count . ' 位用户也观看过该剧目',
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
function musicalbum_recommend_trending( $limit = 10 ) {

    $args = array(
        'post_type'      => 'viewing_record',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );

    $query   = new WP_Query( $args );
    $counter = array();

    if ( ! empty( $query->posts ) ) {
        foreach ( $query->posts as $post_id ) {

            // ✅ 与系统其余部分保持一致
            $title = get_the_title( $post_id );

            if ( empty( $title ) ) {
                continue;
            }

            if ( ! isset( $counter[ $title ] ) ) {
                $counter[ $title ] = 0;
            }

            $counter[ $title ]++;
        }
    }

    if ( empty( $counter ) ) {
        return array();
    }

    // 按出现次数排序
    arsort( $counter );

    $results = array();
    foreach ( $counter as $title => $count ) {
        $results[] = array(
            'musical' => $title,
            'reason'  => '近期被记录 ' . $count . ' 次观演',
        );

        if ( count( $results ) >= $limit ) {
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

/**
 * 基于用户观演记录，获取 AI 推荐音乐剧（固定返回 2 条）
 *
 * 返回格式：
 * [
 *   [
 *     'title' => '音乐剧A',
 *     'desc'  => '简介内容'
 *   ],
 *   [
 *     'title' => '音乐剧B',
 *     'desc'  => '简介内容'
 *   ]
 * ]
 */
/**
 * 基于用户观演记录，获取 AI 推荐音乐剧（带缓存）
 */
function musicalbum_get_ai_recommendations( $user_id ) {

    if ( ! $user_id ) {
        return array();
    }

    /**
     * Step 0：缓存（用户维度）
     */
    $cache_key = 'msr_ai_recommend_' . intval( $user_id );
    $cached = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    /**
     * Step 1：获取用户观演记录
     */
    $viewed_titles = musicalbum_get_user_viewing_history_titles( $user_id );

    if ( empty( $viewed_titles ) ) {
        set_transient( $cache_key, array(), 6 * HOUR_IN_SECONDS );
        return array();
    }

    /**
     * Step 2：构造 Prompt
     */
    $prompt  = "用户看过的音乐剧如下：\n";
    foreach ( $viewed_titles as $title ) {
        $prompt .= "- {$title}\n";
    }

    $prompt .= "\n请推荐 2 部风格或主题相近的音乐剧。\n";
    $prompt .= "要求：\n";
    $prompt .= "1. 每部包含 title 和 desc\n";
    $prompt .= "2. desc 不超过 50 字\n";
    $prompt .= "3. 只返回 JSON 数组，不要解释\n";
    $prompt .= "[{\"title\":\"音乐剧A\",\"desc\":\"简介\"},{\"title\":\"音乐剧B\",\"desc\":\"简介\"}]";

    /**
     * Step 3：调用 DeepSeek
     */
    $raw = musicalbum_call_deepseek_api( $prompt );

    if ( empty( $raw ) ) {
        set_transient( $cache_key, array(), 3 * HOUR_IN_SECONDS );
        return array();
    }

    /**
     * Step 4：解析结果
     */
    $data = json_decode( $raw, true );

    if ( ! is_array( $data ) ) {
        set_transient( $cache_key, array(), 3 * HOUR_IN_SECONDS );
        return array();
    }

    $results = array();

    foreach ( array_slice( $data, 0, 2 ) as $item ) {
        if ( empty( $item['title'] ) || empty( $item['desc'] ) ) {
            continue;
        }

        $results[] = array(
            'title' => sanitize_text_field( $item['title'] ),
            'desc'  => sanitize_textarea_field( $item['desc'] ),
        );
    }

    /**
     * Step 5：写缓存
     */
    set_transient( $cache_key, $results, 6 * HOUR_IN_SECONDS );

    return $results;
}
