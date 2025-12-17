<?php
/*
Plugin Name: Musicalbum Integrations
Description: 自定义集成层，用于与第三方主题与插件协作。
Version: 0.1.0
Author: chen pan
*/

defined('ABSPATH') || exit;

/**
 * 集成插件主类
 *
 * - 注册短码供页面/模板插入功能模块
 * - 注册自定义文章类型存储观演记录
 * - 注册 REST 路由（OCR 与 iCalendar 导出）
 * - 代码化声明 ACF 字段结构（非内容值）
 * - 入队前端资源并注入 REST 端点与安全 nonce
 */
final class Musicalbum_Integrations {
    /**
     * 插件初始化：挂载所有必要钩子
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('init', array(__CLASS__, 'register_viewing_post_type'));
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
        add_action('acf/init', array(__CLASS__, 'register_acf_fields'));
        // 示例：与第三方插件交互（替换为实际钩子）
        // add_filter('some_plugin_output', [__CLASS__, 'filter_some_plugin_output'], 10, 1);
    }

    /**
     * 注册短码：
     * - [musicalbum_hello]
     * - [musicalbum_viewing_form]
     * - [musicalbum_profile_viewings]
     * - [musicalbum_statistics]
     */
    public static function register_shortcodes() {
        add_shortcode('musicalbum_hello', array(__CLASS__, 'shortcode_musicalbum_hello'));
        add_shortcode('musicalbum_viewing_form', array(__CLASS__, 'shortcode_viewing_form'));
        add_shortcode('musicalbum_profile_viewings', array(__CLASS__, 'shortcode_profile_viewings'));
        add_shortcode('musicalbum_statistics', array(__CLASS__, 'shortcode_statistics'));
    }

    /**
     * 示例短码：输出简单的欢迎块
     */
    public static function shortcode_musicalbum_hello($atts = array(), $content = '') {
        return '<div class="musicalbum-hello">Hello Musicalbum</div>';
    }

    /**
     * 前端资源入队：样式与脚本
     * 脚本通过 wp_localize_script 注入 REST 端点与 nonce
     */
    public static function enqueue_assets() {
        wp_register_style('musicalbum-integrations', plugins_url('assets/integrations.css', __FILE__), array(), '0.2.0');
        wp_enqueue_style('musicalbum-integrations');
        // 引入 Chart.js 库
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        wp_register_script('musicalbum-integrations', plugins_url('assets/integrations.js', __FILE__), array('jquery', 'chart-js'), '0.2.0', true);
        wp_localize_script('musicalbum-integrations', 'MusicalbumIntegrations', array(
            'rest' => array(
                'ocr' => esc_url_raw(rest_url('musicalbum/v1/ocr')),
                'statistics' => esc_url_raw(rest_url('musicalbum/v1/statistics')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        ));
        wp_enqueue_script('musicalbum-integrations');
    }

    /**
     * 示例过滤器：用于演示与第三方插件输出交互
     */
    public static function filter_some_plugin_output($output) {
        return $output;
    }

    /**
     * 注册自定义文章类型：musicalbum_viewing（观演记录）
     */
    public static function register_viewing_post_type() {
        register_post_type('musicalbum_viewing', array(
            'labels' => array(
                'name' => '观演记录',
                'singular_name' => '观演记录'
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => array('title'),
            'menu_position' => 20
        ));
    }

    /**
     * 声明 ACF 本地字段组：仅结构，非数据
     * 在 ACF 激活时注册，便于字段随代码版本化
     */
    public static function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) { return; }
        acf_add_local_field_group(array(
            'key' => 'group_malbum_viewing',
            'title' => '观演字段',
            'fields' => array(
                array(
                    'key' => 'field_malbum_theater',
                    'label' => '剧院',
                    'name' => 'theater',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_malbum_cast',
                    'label' => '卡司',
                    'name' => 'cast',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_malbum_price',
                    'label' => '票价',
                    'name' => 'price',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_malbum_date',
                    'label' => '观演日期',
                    'name' => 'view_date',
                    'type' => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format' => 'Y-m-d'
                ),
                array(
                    'key' => 'field_malbum_ticket',
                    'label' => '票面图片',
                    'name' => 'ticket_image',
                    'type' => 'image',
                    'return_format' => 'array'
                ),
                array(
                    'key' => 'field_malbum_notes',
                    'label' => '备注',
                    'name' => 'notes',
                    'type' => 'textarea'
                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'musicalbum_viewing'
                    )
                )
            ),
        ));
    }

    /**
     * 观演录入表单短码：基于 ACF 前端表单创建新记录
     * 返回 HTML 字符串用于页面渲染
     */
    public static function shortcode_viewing_form($atts = array(), $content = '') {
        if (!function_exists('acf_form')) { return ''; }
        ob_start();
        echo '<div class="musicalbum-viewing-form">';
        echo '<div class="musicalbum-ocr"><input type="file" id="musicalbum-ocr-file" accept="image/*" /><button type="button" id="musicalbum-ocr-button">识别票面</button></div>';
        acf_form(array(
            'post_id' => 'new_post',
            'new_post' => array(
                'post_type' => 'musicalbum_viewing',
                'post_status' => 'publish'
            ),
            'post_title' => true,
            'submit_value' => '保存观演记录'
        ));
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * 我的观演列表短码：查询当前用户的观演记录并输出列表
     */
    public static function shortcode_profile_viewings($atts = array(), $content = '') {
        if (!is_user_logged_in()) { return ''; }
        $q = new WP_Query(array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => 20,
            'author' => get_current_user_id(),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        ob_start();
        echo '<div class="musicalbum-viewings-list">';
        while ($q->have_posts()) { $q->the_post();
            $date = get_field('view_date', get_the_ID());
            echo '<div class="item"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a><span class="date">' . esc_html($date) . '</span></div>';
        }
        wp_reset_postdata();
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * 注册 REST 路由：OCR、iCalendar 导出与统计数据
     */
    public static function register_rest_routes() {
        register_rest_route('musicalbum/v1', '/ocr', array(
            'methods' => 'POST',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_ocr')
        ));
        register_rest_route('musicalbum/v1', '/viewings.ics', array(
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => array(__CLASS__, 'rest_ics')
        ));
        register_rest_route('musicalbum/v1', '/statistics', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics')
        ));
    }

    /**
     * OCR 接口：接收图片文件并返回识别结果
     * 优先使用外部过滤器；否则根据设置走默认提供商
     */
    public static function rest_ocr($request) {
        $files = $request->get_file_params();
        if (empty($files['image'])) { return new WP_Error('no_image', '缺少图片', array('status' => 400)); }
        $path = $files['image']['tmp_name'];
        $data = file_get_contents($path);
        if (!$data) { return new WP_Error('bad_image', '读取图片失败', array('status' => 400)); }
        $result = apply_filters('musicalbum_ocr_process', null, $data);
        if (!is_array($result)) {
            $provider = get_option('musicalbum_ocr_provider');
            if ($provider === 'aliyun' || get_option('musicalbum_aliyun_api_key')) {
                $result = self::default_aliyun_ocr($data);
            } else {
                $result = self::default_baidu_ocr($data);
            }
        }
        return rest_ensure_response($result);
    }

    /**
     * 默认百度 OCR：使用通用文字识别接口
     * 返回结构化字段（标题、剧院、卡司、票价、日期）
     */
    private static function default_baidu_ocr($bytes) {
        $api_key = get_option('musicalbum_baidu_api_key');
        $secret_key = get_option('musicalbum_baidu_secret_key');
        if (!$api_key || !$secret_key) { return array(); }
        $token = self::baidu_token($api_key, $secret_key);
        if (!$token) { return array(); }
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=' . urlencode($token);
        $body = http_build_query(array('image' => base64_encode($bytes)));
        $resp = wp_remote_post($url, array('headers' => array('Content-Type' => 'application/x-www-form-urlencoded'), 'body' => $body, 'timeout' => 20));
        if (is_wp_error($resp)) { return array(); }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        $lines = array();
        if (isset($json['words_result'])) {
            foreach($json['words_result'] as $w){ $lines[] = $w['words']; }
        }
        $text = implode("\n", $lines);
        $title = self::extract_title($text);
        $theater = self::extract_theater($text);
        $cast = self::extract_cast($text);
        $price = self::extract_price($text);
        $date = self::extract_date($text);
        return array('title' => $title, 'theater' => $theater, 'cast' => $cast, 'price' => $price, 'view_date' => $date);
    }

    /**
     * 默认阿里云 OCR：根据模式发送二进制或 JSON
     */
    private static function default_aliyun_ocr($bytes) {
        $api_key = get_option('musicalbum_aliyun_api_key');
        $endpoint = get_option('musicalbum_aliyun_endpoint');
        $mode = get_option('musicalbum_aliyun_mode');
        if (!$api_key || !$endpoint) { return array(); }
        $headers = array('Authorization' => 'Bearer ' . $api_key);
        $resp = null;
        if ($mode === 'octet') {
            $headers['Content-Type'] = 'application/octet-stream';
            $resp = wp_remote_post($endpoint, array('headers' => $headers, 'body' => $bytes, 'timeout' => 30));
        } else {
            $headers['Content-Type'] = 'application/json';
            $payload = json_encode(array('image' => base64_encode($bytes)));
            $resp = wp_remote_post($endpoint, array('headers' => $headers, 'body' => $payload, 'timeout' => 30));
        }
        if (is_wp_error($resp)) { return array(); }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        $text = '';
        if (is_string($json)) { $text = $json; }
        if (!$text && isset($json['content']) && is_string($json['content'])) { $text = $json['content']; }
        if (!$text && isset($json['result']) && is_string($json['result'])) { $text = $json['result']; }
        if (!$text && isset($json['data']['content']) && is_string($json['data']['content'])) { $text = $json['data']['content']; }
        if (!$text && isset($json['data']['text']) && is_string($json['data']['text'])) { $text = $json['data']['text']; }
        if (!$text && isset($json['data']['lines']) && is_array($json['data']['lines'])) {
            $lines = array();
            foreach($json['data']['lines'] as $ln){ if (isset($ln['text'])) { $lines[] = $ln['text']; } }
            $text = implode("\n", $lines);
        }
        if (!$text && isset($json['prism_wordsInfo']) && is_array($json['prism_wordsInfo'])) {
            $lines = array();
            foreach($json['prism_wordsInfo'] as $w){ if (isset($w['word'])) { $lines[] = $w['word']; } }
            $text = implode("\n", $lines);
        }
        $title = self::extract_title($text);
        $theater = self::extract_theater($text);
        $cast = self::extract_cast($text);
        $price = self::extract_price($text);
        $date = self::extract_date($text);
        return array('title' => $title, 'theater' => $theater, 'cast' => $cast, 'price' => $price, 'view_date' => $date);
    }

    /**
     * 获取百度 OCR 访问令牌
     */
    private static function baidu_token($api_key, $secret_key) {
        $resp = wp_remote_get('https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id='.urlencode($api_key).'&client_secret='.urlencode($secret_key));
        if (is_wp_error($resp)) { return null; }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        return isset($json['access_token']) ? $json['access_token'] : null;
    }

    /**
     * 从 OCR 文本中提取标题（首行）
     */
    private static function extract_title($text) {
        $lines = preg_split('/\r?\n/', $text);
        return isset($lines[0]) ? $lines[0] : '';
    }
    /** 提取剧院行 */
    private static function extract_theater($text) {
        if (preg_match('/(剧院|剧场|大剧院)[^\n]*/u', $text, $m)) return $m[0];
        return '';
    }
    /** 提取卡司行 */
    private static function extract_cast($text) {
        if (preg_match('/(主演|卡司|演出人员)[^\n]*/u', $text, $m)) return $m[0];
        return '';
    }
    /** 提取票价数值 */
    private static function extract_price($text) {
        if (preg_match('/(票价|Price)[:：]?\s*([0-9]+(\.[0-9]+)?)/u', $text, $m)) return $m[2];
        if (preg_match('/([0-9]+)[元¥]/u', $text, $m)) return $m[1];
        return '';
    }
    /** 提取日期并格式化为 YYYY-MM-DD */
    private static function extract_date($text) {
        if (preg_match('/(20[0-9]{2})[-年\.\/](0?[1-9]|1[0-2])[-月\.\/](0?[1-9]|[12][0-9]|3[01])/u', $text, $m)) {
            $y = $m[1]; $mth = str_pad($m[2], 2, '0', STR_PAD_LEFT); $d = str_pad($m[3], 2, '0', STR_PAD_LEFT);
            return $y.'-'.$mth.'-'.$d;
        }
        return '';
    }

    /**
     * iCalendar 导出接口：返回所有观演记录的日历条目
     */
    public static function rest_ics($request) {
        $args = array('post_type' => 'musicalbum_viewing', 'posts_per_page' => -1, 'post_status' => 'publish');
        $q = new WP_Query($args);
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Musicalbum//Viewing//CN'
        );
        while($q->have_posts()){ $q->the_post();
            $date = get_field('view_date', get_the_ID());
            if (!$date) { continue; }
            $dt = preg_replace('/-/', '', $date);
            $summary = get_the_title();
            $desc = trim('剧院: '.(get_field('theater', get_the_ID()) ?: '')."\n".'卡司: '.(get_field('cast', get_the_ID()) ?: '')."\n".'票价: '.(get_field('price', get_the_ID()) ?: ''));
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . get_the_ID() . '@musicalbum';
            $lines[] = 'DTSTART;VALUE=DATE:' . $dt;
            $lines[] = 'SUMMARY:' . self::escape_ics($summary);
            $lines[] = 'DESCRIPTION:' . self::escape_ics($desc);
            $lines[] = 'END:VEVENT';
        }
        wp_reset_postdata();
        $lines[] = 'END:VCALENDAR';
        $out = implode("\r\n", $lines);
        return new WP_REST_Response($out, 200, array('Content-Type' => 'text/calendar; charset=utf-8'));
    }

    /**
     * iCalendar 内容转义：逗号/分号与换行
     */
    private static function escape_ics($s){
        $s = preg_replace('/([,;])/', '\\$1', $s);
        $s = preg_replace('/\r?\n/', '\\n', $s);
        return $s;
    }

    /**
     * 统计数据短码：显示数据可视化图表
     * 使用 [musicalbum_statistics] 在页面中插入
     */
    public static function shortcode_statistics($atts = array(), $content = '') {
        if (!is_user_logged_in()) {
            return '<div class="musicalbum-statistics-error">请先登录以查看统计数据</div>';
        }
        ob_start();
        ?>
        <div class="musicalbum-statistics-container">
            <h2 class="musicalbum-statistics-title">观演数据统计</h2>
            <div class="musicalbum-charts-grid">
                <div class="musicalbum-chart-wrapper">
                    <h3>剧目类别分布</h3>
                    <canvas id="musicalbum-chart-category"></canvas>
                </div>
                <div class="musicalbum-chart-wrapper">
                    <h3>演员出场频率</h3>
                    <canvas id="musicalbum-chart-cast"></canvas>
                </div>
                <div class="musicalbum-chart-wrapper">
                    <h3>票价区间分布</h3>
                    <canvas id="musicalbum-chart-price"></canvas>
                </div>
            </div>
            <div class="musicalbum-statistics-loading" id="musicalbum-statistics-loading">正在加载数据...</div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 统计数据 REST API 端点
     * 返回当前用户的观演数据统计
     */
    public static function rest_statistics($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        // 查询当前用户的所有观演记录
        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => -1,
            'author' => $user_id,
            'post_status' => 'publish'
        );
        $query = new WP_Query($args);

        $category_data = array(); // 剧目类别分布
        $cast_data = array(); // 演员出场频率
        $price_data = array(); // 票价数据

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $cast = get_field('cast', $post_id);
            $price = get_field('price', $post_id);

            // 统计剧目类别（从标题中提取关键词）
            $category = self::extract_category_from_title($title);
            if ($category) {
                $category_data[$category] = isset($category_data[$category]) ? $category_data[$category] + 1 : 1;
            }

            // 统计演员出场频率（从卡司字段中提取演员姓名）
            if ($cast) {
                $actors = self::extract_actors_from_cast($cast);
                foreach ($actors as $actor) {
                    $cast_data[$actor] = isset($cast_data[$actor]) ? $cast_data[$actor] + 1 : 1;
                }
            }

            // 收集票价数据
            if ($price) {
                $price_num = floatval(preg_replace('/[^0-9.]/', '', $price));
                if ($price_num > 0) {
                    $price_data[] = $price_num;
                }
            }
        }
        wp_reset_postdata();

        // 处理票价区间分布
        $price_ranges = self::calculate_price_ranges($price_data);

        // 对演员出场频率排序，取前10名
        arsort($cast_data);
        $cast_data = array_slice($cast_data, 0, 10, true);

        return rest_ensure_response(array(
            'category' => $category_data,
            'cast' => $cast_data,
            'price' => $price_ranges
        ));
    }

    /**
     * 从标题中提取剧目类别
     * 根据常见剧目类型关键词进行分类
     */
    private static function extract_category_from_title($title) {
        $categories = array(
            '音乐剧' => array('音乐剧', 'Musical'),
            '话剧' => array('话剧', '戏剧', 'Drama'),
            '歌剧' => array('歌剧', 'Opera'),
            '舞剧' => array('舞剧', '芭蕾', 'Ballet'),
            '音乐会' => array('音乐会', 'Concert', '交响'),
            '戏曲' => array('京剧', '昆曲', '越剧', '黄梅戏', '豫剧'),
            '其他' => array()
        );

        foreach ($categories as $category => $keywords) {
            if ($category === '其他') continue;
            foreach ($keywords as $keyword) {
                if (stripos($title, $keyword) !== false) {
                    return $category;
                }
            }
        }
        return '其他';
    }

    /**
     * 从卡司字段中提取演员姓名
     * 支持多种分隔符：逗号、顿号、分号、换行等
     */
    private static function extract_actors_from_cast($cast) {
        // 清理文本，移除常见前缀
        $cast = preg_replace('/^(主演|卡司|演出人员|演员)[:：\s]*/u', '', $cast);
        // 按多种分隔符分割
        $actors = preg_split('/[,，;；、\n\r]+/u', $cast);
        $result = array();
        foreach ($actors as $actor) {
            $actor = trim($actor);
            // 过滤掉空值和过长的文本（可能是误识别）
            if ($actor && mb_strlen($actor) <= 20) {
                $result[] = $actor;
            }
        }
        return $result;
    }

    /**
     * 计算票价区间分布
     * 将票价分为多个区间并统计每个区间的数量
     */
    private static function calculate_price_ranges($prices) {
        if (empty($prices)) {
            return array();
        }

        sort($prices);
        $min = floor(min($prices));
        $max = ceil(max($prices));
        
        // 动态确定区间大小
        $range_size = max(50, ceil(($max - $min) / 10));
        
        $ranges = array();
        $current = $min;
        
        while ($current < $max) {
            $next = $current + $range_size;
            $label = $current . '-' . $next . '元';
            $count = 0;
            
            foreach ($prices as $price) {
                if ($price >= $current && $price < $next) {
                    $count++;
                }
            }
            
            if ($count > 0) {
                $ranges[$label] = $count;
            }
            
            $current = $next;
        }
        
        return $ranges;
    }
}

// 启动插件
Musicalbum_Integrations::init();
