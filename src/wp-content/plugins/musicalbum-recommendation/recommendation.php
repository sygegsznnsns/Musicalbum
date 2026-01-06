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
    $not_interested = musicalbum_get_not_interested($user_id);
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
            if (in_array($title, $viewed_titles, true) || in_array($title, $not_interested, true)) {
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

// 注册不感兴趣功能的AJAX处理函数
function musicalbum_not_interested_ajax_handler() {
    if ( isset( $_POST['musical_title'] ) ) {
        $musical_title = sanitize_text_field( $_POST['musical_title'] );
        $user_id = get_current_user_id();
        
        // 调用函数处理不感兴趣逻辑
        musicalbum_mark_not_interested( $user_id, $musical_title );
        
        // 返回成功响应
        wp_send_json_success( array( 'message' => '已标记为不感兴趣' ) );
    }
    
    // 返回失败响应
    wp_send_json_error( array( 'message' => '无效请求' ) );
}
add_action( 'wp_ajax_musicalbum_not_interested', 'musicalbum_not_interested_ajax_handler' );



/**
 * 将音乐剧添加到用户不感兴趣列表
 */
function musicalbum_mark_not_interested( $user_id, $musical_title ) {
    if ( ! $user_id || ! $musical_title ) {
        return;
    }
    
    $not_interested = musicalbum_get_not_interested( $user_id );
    if ( ! in_array( $musical_title, $not_interested, true ) ) {
        $not_interested[] = $musical_title;
        update_user_meta( $user_id, 'musicalbum_not_interested', $not_interested );
    }
}

/**
 * 近期热门观演剧目（基于观演记录数量）
 */
function musicalbum_recommend_trending( $limit = 10 ) {
    // 获取当前用户不感兴趣的剧目列表
    $not_interested = array();
    if ( is_user_logged_in() ) {
        $not_interested = musicalbum_get_not_interested( get_current_user_id() );
    }

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

            // 排除用户不感兴趣的剧目
            if ( in_array( $title, $not_interested, true ) ) {
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
 * 基于用户关注演员，通过 DeepSeek AI 推荐相关音乐剧
 * 数据链路：演员 → AI推荐 → 音乐剧
 *
 * @param int $user_id
 * @param int $per_actor_limit 每个演员最多推荐几部，默认3部
 * @return array
 */
function musicalbum_recommend_by_favorite_actors( $user_id, $per_actor_limit = 3 ) {

    if ( ! $user_id ) {
        return array();
    }

    /**
     * Step 0：缓存（用户维度）
     */
    $cache_key = 'msr_actor_recommend_' . $user_id;
    $cached = get_transient( $cache_key );

    if ( false !== $cached ) {
        return $cached;
    }

    /**
     * Step 1：获取用户关注的演员
     */
    $favorite_actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    if ( empty( $favorite_actors ) || ! is_array( $favorite_actors ) ) {
        set_transient( $cache_key, array(), 6 * HOUR_IN_SECONDS );
        return array();
    }

    // 获取用户不感兴趣的剧目列表
    $not_interested = musicalbum_get_not_interested( $user_id );

    /**
     * =========
     * Step 2：使用DeepSeek API 根据演员生成推荐列表
     * =========
     */
    $results = [];

    foreach ( $favorite_actors as $actor_name ) {
        // 构造Prompt，处理演员可能不存在的情况
        $prompt  = "请列出音乐剧演员 '$actor_name' 参演过的音乐剧，返回格式必须严格按照以下JSON格式：
";
        $prompt .= '{"musicals":["音乐剧名称1","音乐剧名称2","音乐剧名称3",...]}' . "
";
        $prompt .= "要求：
";
        $prompt .= "1. 只返回JSON格式，不要添加任何其他解释或说明
";
        $prompt .= "2. 如果 '$actor_name' 不存在或不是音乐剧演员，请返回 {\"musicals\":[]}
";
        $prompt .= "3. 最多返回6个音乐剧，按照时间从近到远排序
";
        $prompt .= "4. 确保音乐剧名称准确无误
";
        $prompt .= "5. 不要包含非音乐剧作品
";

        // 调用DeepSeek API
        $raw_response = musicalbum_call_deepseek_api( $prompt );

        if ( empty( $raw_response ) ) {
            continue;
        }

        // 解析AI返回的结果
        $ai_results = json_decode( $raw_response, true );

        // 严格检查AI返回格式
        if ( ! is_array( $ai_results ) || ! isset( $ai_results['musicals'] ) || ! is_array( $ai_results['musicals'] ) ) {
            continue;
        }

        $results[ $actor_name ] = [];

        // 处理推荐结果，确保不超过限制
        foreach ( $ai_results['musicals'] as $musical_title ) {
            // 跳过空的音乐剧名称
            if ( empty( $musical_title ) ) {
                continue;
            }
            
            // 排除用户不感兴趣的剧目
            if ( in_array( $musical_title, $not_interested, true ) ) {
                continue;
            }

            $results[ $actor_name ][] = [
                'musical_id' => 0, // 使用DeepSeek API时无法获取准确的musical_id
                'musical'    => sanitize_text_field( $musical_title ),
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
 * 基于用户观演记录，获取 AI 推荐音乐剧（固定返回 3 条）
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
 *   ],
 *   [
 *     'title' => '音乐剧C',
 *     'desc'  => '简介内容'
 *   ]
 * ]
 */
function musicalbum_get_ai_recommendations( $user_id ) {

    if ( ! $user_id ) {
        return array();
    }

    /**
     * Step 0：缓存（用户维度）
     */
    $cache_key = 'msr_ai_recommend_' . intval( $user_id );
    // 为了确保修改立即生效，先清除旧缓存
    delete_transient( $cache_key );
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

    $prompt .= "\n请推荐 3 部风格或主题相近的音乐剧。如果看过的音乐剧为空，就返回随机的 3 部音乐剧。\n";
    $prompt .= "要求：\n";
    $prompt .= "1. 每部包含 title 和 desc\n";
    $prompt .= "2. desc 不超过 50 字\n";
    $prompt .= "3. 只返回 JSON 数组，不要解释\n";
    $prompt .= "[{\"title\":\"音乐剧A\",\"desc\":\"简介\"},{\"title\":\"音乐剧B\",\"desc\":\"简介\"},{\"title\":\"音乐剧C\",\"desc\":\"简介\"}]";

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
    $count = 0;
    $index = 0;
    $total_items = count($data);
    
    // Process items until we have 3 valid results or no more items
    while ($count < 3 && $index < $total_items) {
        $item = $data[$index];
        $index++;
        
        if (empty($item['title']) || empty($item['desc'])) {
            continue;
        }
        
        $results[] = array(
            'title' => sanitize_text_field($item['title']),
            'desc'  => sanitize_textarea_field($item['desc']),
        );
        $count++;
    }

    // 如果API返回的结果不足3个，从CSV文件中补充
    if (count($results) < 3) {
        // 加载CSV数据
        include_once plugin_dir_path(__FILE__) . 'page-recommend.php';
        $musical_csv_data = msr_load_musical_csv_data();
        
        // 获取已有的推荐标题
        $existing_titles = array_column($results, 'title');
        
        // 从CSV数据中随机选择补充的音乐剧
        $additional_musicals = array_filter($musical_csv_data, function($musical) use ($existing_titles) {
            // 确保音乐剧有标题，且不在已有的推荐列表中
            return !empty($musical['name']) && !in_array($musical['name'], $existing_titles);
        });
        
        // 打乱顺序
        shuffle($additional_musicals);
        
        // 补充推荐直到有3个
        $additional_needed = 3 - count($results);
        foreach (array_slice($additional_musicals, 0, $additional_needed) as $musical) {
            $results[] = array(
                'title' => sanitize_text_field($musical['name']),
                'desc'  => sanitize_textarea_field('一部精彩的音乐剧'),
            );
        }
    }

    /**
     * Step 5：写缓存
     */
    set_transient( $cache_key, $results, 6 * HOUR_IN_SECONDS );

    return $results;
}

/**
 * AJAX处理函数：获取新的AI推荐（与当前推荐不同）
 */
function musicalbum_ajax_refresh_ai_recommendations() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => '请先登录' ) );
        return;
    }

    $user_id = get_current_user_id();
    $current_recommendations = isset( $_POST['current_recommendations'] ) ? $_POST['current_recommendations'] : array();
    
    // 尝试最多3次获取不同的推荐
    $max_attempts = 3;
    $attempt = 0;
    $new_recommendations = array();
    
    while ( $attempt < $max_attempts ) {
        // 临时禁用缓存获取新推荐
        $cache_key = 'msr_ai_recommend_' . intval( $user_id );
        delete_transient( $cache_key );
        
        $new_recommendations = musicalbum_get_ai_recommendations( $user_id );
        
        // 检查是否与当前推荐有重复
        $has_duplicates = false;
        if ( ! empty( $current_recommendations ) && ! empty( $new_recommendations ) ) {
            $current_titles = array_column( $current_recommendations, 'title' );
            $new_titles = array_column( $new_recommendations, 'title' );
            $intersection = array_intersect( $current_titles, $new_titles );
            $has_duplicates = ! empty( $intersection );
        }
        
        if ( ! $has_duplicates && count( $new_recommendations ) >= 3 ) {
            break;
        }
        
        $attempt++;
        
        // 稍微修改prompt以获取不同结果
        add_filter( 'musicalbum_ai_prompt_suffix', function( $suffix ) {
            return '?rand=' . rand( 1, 1000 ) . $suffix;
        } );
    }
    
    if ( empty( $new_recommendations ) ) {
        wp_send_json_error( array( 'message' => '无法获取新的推荐' ) );
        return;
    }
    
    wp_send_json_success( array( 'recommendations' => $new_recommendations ) );
}
