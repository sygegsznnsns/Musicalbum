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
 * 近期热门观演剧目（基于DeepSeek API获取）
 */
function musicalbum_recommend_trending( $limit = 6 ) {
    // 获取当前用户不感兴趣的剧目列表
    $not_interested = array();
    if ( is_user_logged_in() ) {
        $not_interested = musicalbum_get_not_interested( get_current_user_id() );
    }

    // 构造DeepSeek API请求的Prompt
    $prompt = '请推荐近期按热度排序的音乐剧热门演出，要求：
1. 按热度从高到低排序
2. 返回6条记录
3. 每条记录包含音乐剧名称和热度理由
4. 使用JSON格式返回，格式如下：
[
    {"title": "音乐剧名称1", "reason": "热度理由1"},
    {"title": "音乐剧名称2", "reason": "热度理由2"}
]
5. 只返回JSON数据，不要添加任何其他解释文字';

    // 调用DeepSeek API
    $raw_response = musicalbum_call_deepseek_api( $prompt );
    
    $results = array();
    
    if ( ! empty( $raw_response ) ) {
        // 解析API返回的JSON数据
        $api_results = json_decode( $raw_response, true );
        
        if ( is_array( $api_results ) && ! empty( $api_results ) ) {
            foreach ( $api_results as $item ) {
                if ( empty( $item['title'] ) || empty( $item['reason'] ) ) {
                    continue;
                }
                
                // 排除用户不感兴趣的剧目
                if ( in_array( $item['title'], $not_interested, true ) ) {
                    continue;
                }
                
                $results[] = array(
                    'musical' => sanitize_text_field( $item['title'] ),
                    'reason'  => sanitize_textarea_field( $item['reason'] ),
                );
                
                if ( count( $results ) >= $limit ) {
                    break;
                }
            }
        }
    }
    
    // 如果API返回的结果不足6个，从默认数据源补充
    if ( count( $results ) < $limit ) {
        // 加载CSV数据
        include_once plugin_dir_path(__FILE__) . 'page-recommend.php';
        $musical_csv_data = msr_load_musical_csv_data();
        
        // 获取已有的推荐标题
        $existing_titles = array_column( $results, 'musical' );
        
        // 从CSV数据中选择补充的音乐剧
        $additional_musicals = array_filter($musical_csv_data, function($musical) use ($existing_titles, $not_interested) {
            // 确保音乐剧有标题，且不在已有的推荐列表中，也不在不感兴趣列表中
            return !empty($musical['name']) && !in_array($musical['name'], $existing_titles) && !in_array($musical['name'], $not_interested);
        });
        
        // 补充推荐直到达到限制数量
        $additional_needed = $limit - count($results);
        foreach (array_slice($additional_musicals, 0, $additional_needed) as $musical) {
            $results[] = array(
                'musical' => sanitize_text_field($musical['name']),
                'reason'  => '近期热门音乐剧演出',
            );
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
 * 演员推荐特判函数
 * @param string $actor_name 演员名称
 * @return array|false 如果是特判演员则返回固定剧目，否则返回false
 */
function musicalbum_check_special_actor($actor_name) {
    // 特判：如果演员是张泽或叶麒圣，返回固定剧目
    if ($actor_name === '张泽') {
        // 张泽的固定剧目
        return ['锦衣卫之刀与花', '风声', '宝玉', '堕落天使', '道林格雷的画像', '阿波罗尼亚'];
    } elseif ($actor_name === '叶麒圣') {
        // 叶麒圣的固定剧目
        return ['锦衣卫之刀与花', '杨戬', '道林格雷的画像', '人间失格', '妈妈再爱我一次', '阿波罗尼亚'];
    }
    
    // 不是特判演员，返回false
    return false;
}

/**
 * 热门剧目特判函数
 * @param int $limit 返回数量限制
 * @return array 固定的热门剧目列表
 */
function musicalbum_check_special_trending($limit = 6) {
    // 获取当前用户不感兴趣的剧目列表
    $not_interested = array();
    if (is_user_logged_in()) {
        $not_interested = musicalbum_get_not_interested(get_current_user_id());
    }
    
    // 固定的热门剧目内容
    $fixed_trending = [
        ['musical' => '锦衣卫之刀与花', 'reason' => '武侠风与舞台剧的惊艳碰撞，快意恩仇。'],
        ['musical' => '人间失格', 'reason' => '舞台美学极致，沉浸式感受太宰治的灵魂挣扎。'],
        ['musical' => '道林格雷的画像', 'reason' => '王尔德金句频出，探讨美与堕落的视听盛宴。'],
        ['musical' => '悲惨世界', 'reason' => '经典永流传，一曲《Do You Hear the People Sing?》足以震撼心灵。'],
        ['musical' => '寻找李二狗', 'reason' => '本土原创喜剧，接地气的幽默与温暖直击人心。'],
        ['musical' => '六个说谎的大学生', 'reason' => '悬疑剧情层层反转，职场人性博弈的沉浸式体验。']
    ];
    
    $results = array();
    $processed_musicals = array();
    
    // 处理固定内容，排除用户不感兴趣的剧目
    foreach ($fixed_trending as $item) {
        $musical_title = $item['musical'];
        
        // 排除用户不感兴趣的剧目
        if (in_array($musical_title, $not_interested, true)) {
            continue;
        }
        
        // 确保不重复
        if (in_array($musical_title, $processed_musicals, true)) {
            continue;
        }
        
        $processed_musicals[] = $musical_title;
        
        $results[] = array(
            'musical' => sanitize_text_field($musical_title),
            'reason'  => sanitize_textarea_field($item['reason']),
        );

        if (count($results) >= $limit) {
            break;
        }
    }
    
    return $results;
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
 * 生成新的AI推荐（不依赖缓存）
 */
function generate_new_ai_recommendations( $user_id, $current_recommendations = array() ) {
    /**
     * Step 1：获取用户观演记录
     */
    $viewed_titles = musicalbum_get_user_viewing_history_titles( $user_id );

    if ( empty( $viewed_titles ) ) {
        return array();
    }

    /**
     * Step 2：构造 Prompt
     */
    $prompt  = "用户看过的音乐剧如下：\n";
    foreach ( $viewed_titles as $title ) {
        $prompt .= "- {$title}\n";
    }

    // 如果有当前推荐，添加排除要求
    if ( ! empty( $current_recommendations ) ) {
        $current_titles = array_column( $current_recommendations, 'title' );
        $prompt .= "\n请不要推荐以下音乐剧：\n";
        foreach ( $current_titles as $title ) {
            $prompt .= "- {$title}\n";
        }
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
        return array();
    }

    /**
     * Step 4：解析结果
     */
    $data = json_decode( $raw, true );

    if ( ! is_array( $data ) ) {
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
        if (!empty($current_recommendations)) {
            $existing_titles = array_merge($existing_titles, array_column($current_recommendations, 'title'));
        }
        
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
        
        // 在函数内部直接生成新的推荐，不依赖缓存
        $new_recommendations = generate_new_ai_recommendations( $user_id, $current_recommendations );
        
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
    }
    
    if ( empty( $new_recommendations ) ) {
        wp_send_json_error( array( 'message' => '无法获取新的推荐' ) );
        return;
    }
    
    wp_send_json_success( array( 'recommendations' => $new_recommendations ) );
}
