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
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        // 示例：与第三方插件交互（替换为实际钩子）
        // add_filter('some_plugin_output', [__CLASS__, 'filter_some_plugin_output'], 10, 1);
    }

    /**
     * 注册短码：
     * - [musicalbum_hello]
     * - [musicalbum_viewing_form]
     * - [musicalbum_profile_viewings]
     * - [musicalbum_statistics]
     * - [musicalbum_viewing_manager]
     */
    public static function register_shortcodes() {
        add_shortcode('musicalbum_hello', array(__CLASS__, 'shortcode_musicalbum_hello'));
        add_shortcode('musicalbum_viewing_form', array(__CLASS__, 'shortcode_viewing_form'));
        add_shortcode('musicalbum_profile_viewings', array(__CLASS__, 'shortcode_profile_viewings'));
        add_shortcode('musicalbum_statistics', array(__CLASS__, 'shortcode_statistics'));
        add_shortcode('musicalbum_viewing_manager', array(__CLASS__, 'shortcode_viewing_manager'));
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
        wp_register_style('musicalbum-integrations', plugins_url('assets/integrations.css', __FILE__), array(), '0.3.0');
        wp_enqueue_style('musicalbum-integrations');
        // 引入 Chart.js 库
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        // 引入 FullCalendar 库（用于日历视图）
        wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css', array(), '6.1.10');
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', array(), '6.1.10', true);
        // 引入 FullCalendar 中文语言包
        wp_enqueue_script('fullcalendar-locale', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/zh-cn.global.min.js', array('fullcalendar'), '6.1.10', true);
        wp_register_script('musicalbum-integrations', plugins_url('assets/integrations.js', __FILE__), array('jquery', 'chart-js', 'fullcalendar'), '0.3.0', true);
        wp_localize_script('musicalbum-integrations', 'MusicalbumIntegrations', array(
            'rest' => array(
                'ocr' => esc_url_raw(rest_url('musicalbum/v1/ocr')),
                'statistics' => esc_url_raw(rest_url('musicalbum/v1/statistics')),
                'statisticsDetails' => esc_url_raw(rest_url('musicalbum/v1/statistics/details')),
                'statisticsExport' => esc_url_raw(rest_url('musicalbum/v1/statistics/export')),
                'viewings' => esc_url_raw(rest_url('musicalbum/v1/viewings')),
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
                    'key' => 'field_malbum_category',
                    'label' => '剧目类别',
                    'name' => 'category',
                    'type' => 'select',
                    'choices' => array(
                        '音乐剧' => '音乐剧',
                        '话剧' => '话剧',
                        '歌剧' => '歌剧',
                        '舞剧' => '舞剧',
                        '音乐会' => '音乐会',
                        '戏曲' => '戏曲',
                        '其他' => '其他'
                    ),
                    'default_value' => '',
                    'allow_null' => 1,
                    'multiple' => 0,
                    'ui' => 1,
                    'return_format' => 'value'
                ),
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
        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        // 如果不是管理员，只显示当前用户的记录
        if (!current_user_can('manage_options')) {
            $args['author'] = get_current_user_id();
        }
        $q = new WP_Query($args);
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
        register_rest_route('musicalbum/v1', '/statistics/details', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics_details')
        ));
        register_rest_route('musicalbum/v1', '/statistics/export', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics_export')
        ));
        // 观演记录管理 API
        register_rest_route('musicalbum/v1', '/viewings', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_list')
        ));
        register_rest_route('musicalbum/v1', '/viewings', array(
            'methods' => 'POST',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_create')
        ));
        register_rest_route('musicalbum/v1', '/viewings/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_update'),
            'args' => array('id' => array('type' => 'integer'))
        ));
        register_rest_route('musicalbum/v1', '/viewings/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_delete'),
            'args' => array('id' => array('type' => 'integer'))
        ));
    }

    /**
     * OCR 接口：接收图片文件并返回识别结果
     * 优先使用外部过滤器；否则根据设置走默认提供商
     */
    public static function rest_ocr($request) {
        $files = $request->get_file_params();
        if (empty($files['image'])) { 
            return new WP_Error('no_image', '缺少图片', array('status' => 400)); 
        }
        $path = $files['image']['tmp_name'];
        $data = file_get_contents($path);
        if (!$data) { 
            return new WP_Error('bad_image', '读取图片失败', array('status' => 400)); 
        }
        
        $result = apply_filters('musicalbum_ocr_process', null, $data);
        if (!is_array($result)) {
            $provider = get_option('musicalbum_ocr_provider');
            $baidu_api_key = get_option('musicalbum_baidu_api_key');
            $baidu_secret_key = get_option('musicalbum_baidu_secret_key');
            $aliyun_api_key = get_option('musicalbum_aliyun_api_key');
            $aliyun_endpoint = get_option('musicalbum_aliyun_endpoint');
            
            // 检查API配置
            $has_baidu = !empty($baidu_api_key) && !empty($baidu_secret_key);
            $has_aliyun = !empty($aliyun_api_key) && !empty($aliyun_endpoint);
            
            if ($provider === 'aliyun' || ($has_aliyun && !$has_baidu)) {
                $result = self::default_aliyun_ocr($data);
                if (empty($result) && !$has_aliyun) {
                    $result = array('_debug_message' => '阿里云OCR API未配置（需要API密钥和端点）');
                }
            } else if ($has_baidu) {
                $result = self::default_baidu_ocr($data);
                if (empty($result) && !$has_baidu) {
                    $result = array('_debug_message' => '百度OCR API未配置（需要API密钥和Secret密钥）');
                }
            } else {
                // 没有任何OCR API配置
                $result = array(
                    'title' => '',
                    'theater' => '',
                    'cast' => '',
                    'price' => '',
                    'view_date' => '',
                    '_debug_message' => 'OCR API未配置。请配置百度OCR（API密钥和Secret密钥）或阿里云OCR（API密钥和端点）'
                );
            }
        }
        
        // 如果OCR API没有配置或返回空结果，确保返回完整的字段结构
        if (empty($result) || !is_array($result)) {
            $result = array(
                'title' => '',
                'theater' => '',
                'cast' => '',
                'price' => '',
                'view_date' => '',
                '_debug_message' => isset($result['_debug_message']) ? $result['_debug_message'] : 'OCR API返回空结果'
            );
        } else {
            // 确保所有字段都存在，即使API返回的结果中缺少某些字段
            if (!isset($result['title'])) $result['title'] = '';
            if (!isset($result['theater'])) $result['theater'] = '';
            if (!isset($result['cast'])) $result['cast'] = '';
            if (!isset($result['price'])) $result['price'] = '';
            if (!isset($result['view_date'])) $result['view_date'] = '';
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
        if (!$api_key || !$secret_key) { 
            return array('_debug_message' => '百度OCR API密钥未配置');
        }
        $token = self::baidu_token($api_key, $secret_key);
        if (!$token) { 
            return array('_debug_message' => '百度OCR获取访问令牌失败，请检查API密钥和Secret密钥是否正确');
        }
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=' . urlencode($token);
        $body = http_build_query(array('image' => base64_encode($bytes)));
        $resp = wp_remote_post($url, array('headers' => array('Content-Type' => 'application/x-www-form-urlencoded'), 'body' => $body, 'timeout' => 20));
        if (is_wp_error($resp)) { 
            return array('_debug_message' => '百度OCR API请求失败: ' . $resp->get_error_message());
        }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        
        // 检查API返回是否有错误
        if (isset($json['error_code']) || isset($json['error_msg'])) {
            $error_msg = isset($json['error_msg']) ? $json['error_msg'] : '未知错误';
            $error_code = isset($json['error_code']) ? $json['error_code'] : '未知';
            return array('_debug_message' => '百度OCR API错误: ' . $error_msg . ' (错误码: ' . $error_code . ')', '_debug_json' => $json);
        }
        
        $lines = array();
        if (isset($json['words_result'])) {
            foreach($json['words_result'] as $w){ 
                if (isset($w['words'])) {
                    $lines[] = $w['words']; 
                }
            }
        }
        $text = implode("\n", $lines);
        
        // 如果没有识别到文本，返回空结果（但包含调试信息）
        $result = array();
        if (empty($text)) {
            // 即使没有文本，也返回调试信息（始终可用，方便排查问题）
            $result['_debug_text'] = 'OCR API未返回文本内容';
            $result['_debug_json'] = $json;
            return $result;
        }
        
        $title = self::extract_title($text);
        $theater = self::extract_theater($text);
        $cast = self::extract_cast($text);
        $price = self::extract_price($text);
        $date = self::extract_date($text);
        
        // 添加调试信息（始终可用，方便排查问题）
        $result = array(
            'title' => $title, 
            'theater' => $theater, 
            'cast' => $cast, 
            'price' => $price, 
            'view_date' => $date,
            '_debug_text' => $text  // 始终包含原始文本，方便调试
        );
        
        return $result;
    }

    /**
     * 默认阿里云 OCR：根据模式发送二进制或 JSON
     */
    private static function default_aliyun_ocr($bytes) {
        $api_key = get_option('musicalbum_aliyun_api_key');
        $endpoint = get_option('musicalbum_aliyun_endpoint');
        $mode = get_option('musicalbum_aliyun_mode');
        if (!$api_key || !$endpoint) { 
            return array('_debug_message' => '阿里云OCR API未配置（需要API密钥和端点）');
        }
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
        if (is_wp_error($resp)) { 
            return array('_debug_message' => '阿里云OCR API请求失败: ' . $resp->get_error_message());
        }
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
        // 如果没有识别到文本，返回空结果（但包含调试信息）
        $result = array();
        if (empty($text)) {
            // 即使没有文本，也返回调试信息（始终可用，方便排查问题）
            $result['_debug_text'] = 'OCR API未返回文本内容';
            $result['_debug_json'] = $json;
            return $result;
        }
        
        $title = self::extract_title($text);
        $theater = self::extract_theater($text);
        $cast = self::extract_cast($text);
        $price = self::extract_price($text);
        $date = self::extract_date($text);
        
        // 添加调试信息（始终可用，方便排查问题）
        $result = array(
            'title' => $title, 
            'theater' => $theater, 
            'cast' => $cast, 
            'price' => $price, 
            'view_date' => $date,
            '_debug_text' => $text  // 始终包含原始文本，方便调试
        );
        
        return $result;
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
     * 从 OCR 文本中提取标题
     * 支持格式：1) "标题：xxx" 2) 首行文本
     */
    private static function extract_title($text) {
        // 先尝试提取"标题："格式（支持中英文冒号）
        if (preg_match('/标题[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 尝试提取"标题"关键词后的内容（更宽松的匹配）
        if (preg_match('/标题\s*[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 否则返回首行（排除空行）
        $lines = preg_split('/\r?\n/', $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !preg_match('/^(标题|日期|剧院|卡司|票价)[:：]/u', $line)) {
                return $line;
            }
        }
        return '';
    }
    
    /** 提取剧院行 */
    private static function extract_theater($text) {
        // 优先提取"剧院："格式（支持多行匹配）
        if (preg_match('/剧院[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 尝试更宽松的匹配
        if (preg_match('/剧院\s*[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 否则使用原有逻辑
        if (preg_match('/(剧院|剧场|大剧院)[:：]?\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = isset($m[2]) ? trim($m[2]) : trim($m[0]);
            // 移除"剧院"等关键词，只返回名称
            $result = preg_replace('/^(剧院|剧场|大剧院)[:：]?\s*/u', '', $result);
            return trim($result);
        }
        return '';
    }
    
    /** 提取卡司行 */
    private static function extract_cast($text) {
        // 优先提取"卡司："格式（支持多行匹配）
        if (preg_match('/卡司[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 尝试更宽松的匹配
        if (preg_match('/卡司\s*[:：]\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 否则使用原有逻辑
        if (preg_match('/(主演|卡司|演出人员)[:：]?\s*(.+?)(?:\n|$)/um', $text, $m)) {
            $result = isset($m[2]) ? trim($m[2]) : trim($m[0]);
            if (!empty($result)) return $result;
        }
        return '';
    }
    
    /** 提取票价数值 */
    private static function extract_price($text) {
        // 优先提取"票价："格式（支持多行匹配）
        if (preg_match('/票价[:：]\s*([0-9]+(?:\.[0-9]+)?)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 尝试更宽松的匹配
        if (preg_match('/票价\s*[:：]\s*([0-9]+(?:\.[0-9]+)?)/um', $text, $m)) {
            $result = trim($m[1]);
            if (!empty($result)) return $result;
        }
        // 原有逻辑
        if (preg_match('/(票价|Price)[:：]?\s*([0-9]+(\.[0-9]+)?)/um', $text, $m)) {
            return $m[2];
        }
        if (preg_match('/([0-9]+)[元¥]/u', $text, $m)) {
            return $m[1];
        }
        return '';
    }
    
    /** 提取日期并格式化为 YYYY-MM-DD */
    private static function extract_date($text) {
        // 优先提取"日期："格式（支持多行匹配）
        if (preg_match('/日期[:：]\s*([0-9]{4}[-年\.\/][0-9]{1,2}[-月\.\/][0-9]{1,2})/um', $text, $m)) {
            $date_str = $m[1];
        } else if (preg_match('/日期\s*[:：]\s*([0-9]{4}[-年\.\/][0-9]{1,2}[-月\.\/][0-9]{1,2})/um', $text, $m)) {
            $date_str = $m[1];
        } else {
            // 原有逻辑：查找任何日期格式
            if (!preg_match('/(20[0-9]{2})[-年\.\/](0?[1-9]|1[0-2])[-月\.\/](0?[1-9]|[12][0-9]|3[01])/um', $text, $m)) {
                return '';
            }
            $date_str = $m[0];
        }
        
        // 统一格式化
        if (preg_match('/(20[0-9]{2})[-年\.\/](0?[1-9]|1[0-2])[-月\.\/](0?[1-9]|[12][0-9]|3[01])/u', $date_str, $m)) {
            $y = $m[1];
            $mth = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $d = str_pad($m[3], 2, '0', STR_PAD_LEFT);
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
            <div class="musicalbum-statistics-header">
                <h2 class="musicalbum-statistics-title">观演数据统计</h2>
                <div class="musicalbum-statistics-actions">
                    <button type="button" class="musicalbum-btn musicalbum-btn-refresh" id="musicalbum-refresh-btn" title="刷新数据">
                        <span class="musicalbum-icon-refresh">↻</span> 刷新
                    </button>
                    <button type="button" class="musicalbum-btn musicalbum-btn-export" id="musicalbum-export-btn" title="导出数据">
                        <span class="musicalbum-icon-export">↓</span> 导出
                    </button>
                </div>
            </div>
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
     * 返回当前用户的观演数据统计（管理员可查看所有数据）
     */
    public static function rest_statistics($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        // 查询观演记录：管理员查看所有，普通用户只看自己的
        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        
        // 如果不是管理员，只查询当前用户的记录
        if (!current_user_can('manage_options')) {
            $args['author'] = $user_id;
        }
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

            // 统计剧目类别：优先使用category字段，如果没有则从标题中提取
            $category = get_field('category', $post_id);
            if (!$category || $category === '') {
                $category = self::extract_category_from_title($title);
            }
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

    /**
     * 统计数据详情 REST API 端点
     * 根据筛选条件返回具体的观演记录列表（管理员可查看所有数据）
     */
    public static function rest_statistics_details($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $type = $request->get_param('type'); // category, cast, price
        $value = $request->get_param('value'); // 具体的类别、演员名或票价区间
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = absint($request->get_param('per_page')) ?: 20;

        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // 如果不是管理员，只查询当前用户的记录
        if (!current_user_can('manage_options')) {
            $args['author'] = $user_id;
        }

        // 根据类型添加meta查询
        if ($type === 'category') {
            $args['meta_query'] = array(
                array(
                    'key' => 'category',
                    'value' => $value,
                    'compare' => '='
                )
            );
        } elseif ($type === 'cast') {
            $args['meta_query'] = array(
                array(
                    'key' => 'cast',
                    'value' => $value,
                    'compare' => 'LIKE'
                )
            );
        } elseif ($type === 'price') {
            // 解析票价区间，例如 "100-150元"
            if (preg_match('/(\d+)-(\d+)/', $value, $matches)) {
                $min_price = floatval($matches[1]);
                $max_price = floatval($matches[2]);
                // 票价字段可能包含文字，需要先获取所有记录再过滤
                // 暂时不设置meta_query，在循环中过滤
            }
        }

        // 如果是票价区间筛选，需要先获取所有记录再过滤和分页
        if ($type === 'price' && isset($min_price) && isset($max_price)) {
            // 获取所有记录（不分页）
            $args['posts_per_page'] = -1;
            $args['paged'] = 1;
        }

        $query = new WP_Query($args);
        $all_results = array();

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $price_field = get_field('price', $post_id);
            
            // 如果是票价区间筛选，需要验证票价是否在区间内
            if ($type === 'price' && isset($min_price) && isset($max_price)) {
                // 从票价字段中提取数字
                $price_num = 0;
                if ($price_field) {
                    // 移除所有非数字字符（除了小数点），提取数字
                    $price_clean = preg_replace('/[^0-9.]/', '', $price_field);
                    $price_num = floatval($price_clean);
                }
                
                // 检查票价是否在区间内（包含最小值，不包含最大值，与统计逻辑一致）
                if ($price_num < $min_price || $price_num >= $max_price) {
                    continue; // 跳过不在区间内的记录
                }
            }
            
            $all_results[] = array(
                'id' => $post_id,
                'title' => get_the_title(),
                'category' => get_field('category', $post_id),
                'theater' => get_field('theater', $post_id),
                'cast' => get_field('cast', $post_id),
                'price' => $price_field,
                'view_date' => get_field('view_date', $post_id),
                'url' => get_permalink($post_id)
            );
        }
        wp_reset_postdata();
        
        // 如果是票价区间筛选，需要手动分页
        if ($type === 'price' && isset($min_price) && isset($max_price)) {
            $total_count = count($all_results);
            $total_pages = ceil($total_count / $per_page);
            $offset = ($page - 1) * $per_page;
            $results = array_slice($all_results, $offset, $per_page);
            
            return rest_ensure_response(array(
                'data' => $results,
                'total' => $total_count,
                'pages' => $total_pages,
                'current_page' => $page
            ));
        }
        
        // 其他类型的查询直接返回
        $results = $all_results;

        return rest_ensure_response(array(
            'data' => $results,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page
        ));
    }

    /**
     * 统计数据导出 REST API 端点
     * 导出为CSV格式（管理员可导出所有数据）
     */
    public static function rest_statistics_export($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $format = $request->get_param('format') ?: 'csv'; // csv, json

        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // 如果不是管理员，只导出当前用户的记录
        if (!current_user_can('manage_options')) {
            $args['author'] = $user_id;
        }
        $query = new WP_Query($args);

        if ($format === 'csv') {
            // 输出CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="观演统计_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            // 添加BOM以支持中文
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV头部
            fputcsv($output, array('标题', '类别', '剧院', '卡司', '票价', '观演日期'), ',');
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                fputcsv($output, array(
                    get_the_title(),
                    get_field('category', $post_id) ?: '',
                    get_field('theater', $post_id) ?: '',
                    get_field('cast', $post_id) ?: '',
                    get_field('price', $post_id) ?: '',
                    get_field('view_date', $post_id) ?: ''
                ), ',');
            }
            wp_reset_postdata();
            fclose($output);
            exit;
        } else {
            // 输出JSON
            $results = array();
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $results[] = array(
                    'title' => get_the_title(),
                    'category' => get_field('category', $post_id),
                    'theater' => get_field('theater', $post_id),
                    'cast' => get_field('cast', $post_id),
                    'price' => get_field('price', $post_id),
                    'view_date' => get_field('view_date', $post_id)
                );
            }
            wp_reset_postdata();
            
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="观演统计_' . date('Y-m-d') . '.json"');
            echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    }

    /**
     * 观演记录管理短码：提供完整的记录管理界面
     * 使用 [musicalbum_viewing_manager] 在页面中插入
     */
    public static function shortcode_viewing_manager($atts = array(), $content = '') {
        if (!is_user_logged_in()) {
            return '<div class="musicalbum-statistics-error">请先登录以管理观演记录</div>';
        }
        ob_start();
        ?>
        <div class="musicalbum-manager-container">
            <div class="musicalbum-manager-header">
                <h2 class="musicalbum-manager-title">观演记录管理</h2>
                <div class="musicalbum-manager-actions">
                    <button type="button" class="musicalbum-btn musicalbum-btn-primary" id="musicalbum-add-btn">
                        <span>+</span> 新增记录
                    </button>
                    <div class="musicalbum-view-toggle">
                        <button type="button" class="musicalbum-view-btn active" data-view="list">列表</button>
                        <button type="button" class="musicalbum-view-btn" data-view="calendar">日历</button>
                    </div>
                </div>
            </div>

            <!-- 录入表单模态框 -->
            <div id="musicalbum-form-modal" class="musicalbum-modal">
                <div class="musicalbum-modal-content musicalbum-form-modal-content">
                    <span class="musicalbum-modal-close">&times;</span>
                    <h3 class="musicalbum-modal-title" id="musicalbum-form-title">新增观演记录</h3>
                    <div class="musicalbum-modal-body">
                        <div class="musicalbum-form-tabs">
                            <button type="button" class="musicalbum-tab-btn active" data-tab="manual">手动录入</button>
                            <button type="button" class="musicalbum-tab-btn" data-tab="ocr">OCR识别</button>
                        </div>
                        
                        <!-- 手动录入表单 -->
                        <div id="musicalbum-tab-manual" class="musicalbum-tab-content active">
                            <form id="musicalbum-manual-form" class="musicalbum-viewing-form">
                                <input type="hidden" id="musicalbum-edit-id" name="id" value="">
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-title-input">标题 <span class="required">*</span></label>
                                    <input type="text" id="musicalbum-form-title-input" name="title" required>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-category">剧目类别</label>
                                    <select id="musicalbum-form-category" name="category">
                                        <option value="">请选择</option>
                                        <option value="音乐剧">音乐剧</option>
                                        <option value="话剧">话剧</option>
                                        <option value="歌剧">歌剧</option>
                                        <option value="舞剧">舞剧</option>
                                        <option value="音乐会">音乐会</option>
                                        <option value="戏曲">戏曲</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-theater">剧院</label>
                                    <input type="text" id="musicalbum-form-theater" name="theater">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-cast">卡司</label>
                                    <input type="text" id="musicalbum-form-cast" name="cast" placeholder="多个演员用逗号分隔">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-price">票价</label>
                                    <input type="text" id="musicalbum-form-price" name="price" placeholder="例如：280 或 280元">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-date">观演日期</label>
                                    <input type="date" id="musicalbum-form-date" name="view_date">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-notes">备注</label>
                                    <textarea id="musicalbum-form-notes" name="notes" rows="4"></textarea>
                                </div>
                                <div class="musicalbum-form-actions">
                                    <button type="button" class="musicalbum-btn musicalbum-btn-cancel" id="musicalbum-form-cancel">取消</button>
                                    <button type="submit" class="musicalbum-btn musicalbum-btn-primary">保存</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- OCR识别表单 -->
                        <div id="musicalbum-tab-ocr" class="musicalbum-tab-content">
                            <div class="musicalbum-ocr-upload">
                                <input type="file" id="musicalbum-ocr-manager-file" accept="image/*">
                                <button type="button" class="musicalbum-btn musicalbum-btn-primary" id="musicalbum-ocr-manager-button">识别票面</button>
                                <div id="musicalbum-ocr-preview" class="musicalbum-ocr-preview"></div>
                            </div>
                            <form id="musicalbum-ocr-form" class="musicalbum-viewing-form" style="display:none;">
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-title">标题 <span class="required">*</span></label>
                                    <input type="text" id="musicalbum-ocr-title" name="title" required>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-category">剧目类别</label>
                                    <select id="musicalbum-ocr-category" name="category">
                                        <option value="">请选择</option>
                                        <option value="音乐剧">音乐剧</option>
                                        <option value="话剧">话剧</option>
                                        <option value="歌剧">歌剧</option>
                                        <option value="舞剧">舞剧</option>
                                        <option value="音乐会">音乐会</option>
                                        <option value="戏曲">戏曲</option>
                                        <option value="其他">其他</option>
                                    </select>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-theater">剧院</label>
                                    <input type="text" id="musicalbum-ocr-theater" name="theater">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-cast">卡司</label>
                                    <input type="text" id="musicalbum-ocr-cast" name="cast">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-price">票价</label>
                                    <input type="text" id="musicalbum-ocr-price" name="price">
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-date">观演日期</label>
                                    <input type="date" id="musicalbum-ocr-date" name="view_date">
                                </div>
                                <div class="musicalbum-form-actions">
                                    <button type="button" class="musicalbum-btn musicalbum-btn-cancel" id="musicalbum-ocr-cancel">取消</button>
                                    <button type="submit" class="musicalbum-btn musicalbum-btn-primary">保存</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 列表视图 -->
            <div id="musicalbum-list-view" class="musicalbum-view-content active">
                <div class="musicalbum-list-filters">
                    <input type="text" id="musicalbum-search-input" placeholder="搜索标题、剧院、卡司...">
                    <select id="musicalbum-filter-category">
                        <option value="">所有类别</option>
                        <option value="音乐剧">音乐剧</option>
                        <option value="话剧">话剧</option>
                        <option value="歌剧">歌剧</option>
                        <option value="舞剧">舞剧</option>
                        <option value="音乐会">音乐会</option>
                        <option value="戏曲">戏曲</option>
                        <option value="其他">其他</option>
                    </select>
                    <select id="musicalbum-sort-by">
                        <option value="date_desc">日期（最新）</option>
                        <option value="date_asc">日期（最早）</option>
                        <option value="title_asc">标题（A-Z）</option>
                        <option value="title_desc">标题（Z-A）</option>
                    </select>
                </div>
                <div id="musicalbum-list-container" class="musicalbum-list-container">
                    <div class="musicalbum-loading">加载中...</div>
                </div>
            </div>

            <!-- 日历视图 -->
            <div id="musicalbum-calendar-view" class="musicalbum-view-content">
                <div id="musicalbum-calendar-container"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 获取观演记录列表 REST API
     */
    public static function rest_viewings_list($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $args = array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => -1,
            'post_status' => 'publish'
            // 不在这里使用orderby，因为要按view_date（观演日期）排序，而不是post_date（记录创建日期）
            // 排序将在PHP端根据view_date字段进行
        );

        // 如果不是管理员，只查询当前用户的记录
        if (!current_user_can('manage_options')) {
            $args['author'] = $user_id;
        }

        // 类别过滤
        $category = $request->get_param('category');
        if ($category) {
            $args['meta_query'] = array(
                array(
                    'key' => 'category',
                    'value' => $category,
                    'compare' => '='
                )
            );
        }

        // 先获取所有符合条件的记录（不考虑搜索）
        $query = new WP_Query($args);
        $results = array();
        $search = $request->get_param('search');
        $search_lower = $search ? mb_strtolower(trim($search), 'UTF-8') : '';

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // 获取所有字段
            $title = get_the_title();
            $theater = get_field('theater', $post_id);
            $cast = get_field('cast', $post_id);
            $category_field = get_field('category', $post_id);
            $price = get_field('price', $post_id);
            $view_date = get_field('view_date', $post_id);
            $notes = get_field('notes', $post_id);
            
            // 如果有搜索关键词，检查是否匹配
            if ($search_lower) {
                $matched = false;
                
                // 搜索标题
                if (mb_stripos($title, $search_lower) !== false) {
                    $matched = true;
                }
                
                // 搜索剧院
                if ($theater && mb_stripos(mb_strtolower($theater, 'UTF-8'), $search_lower) !== false) {
                    $matched = true;
                }
                
                // 搜索卡司
                if ($cast && mb_stripos(mb_strtolower($cast, 'UTF-8'), $search_lower) !== false) {
                    $matched = true;
                }
                
                // 搜索类别
                if ($category_field && mb_stripos(mb_strtolower($category_field, 'UTF-8'), $search_lower) !== false) {
                    $matched = true;
                }
                
                // 搜索备注
                if ($notes && mb_stripos(mb_strtolower($notes, 'UTF-8'), $search_lower) !== false) {
                    $matched = true;
                }
                
                // 如果不匹配，跳过这条记录
                if (!$matched) {
                    continue;
                }
            }
            
            $results[] = array(
                'id' => $post_id,
                'title' => $title,
                'category' => $category_field,
                'theater' => $theater,
                'cast' => $cast,
                'price' => $price,
                'view_date' => $view_date,
                'notes' => $notes,
                'ticket_image' => get_field('ticket_image', $post_id),
                'url' => get_permalink($post_id),
                'author' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            );
        }
        wp_reset_postdata();
        
        // 排序（在过滤后进行，按观演日期view_date排序）
        $sort = $request->get_param('sort');
        if ($sort === 'date_asc') {
            // 按观演日期升序（最早在前）
            usort($results, function($a, $b) {
                $date_a = $a['view_date'] ? strtotime($a['view_date']) : 0;
                $date_b = $b['view_date'] ? strtotime($b['view_date']) : 0;
                // 没有日期的排在最后
                if ($date_a === 0 && $date_b === 0) return 0;
                if ($date_a === 0) return 1;
                if ($date_b === 0) return -1;
                return $date_a - $date_b;
            });
        } elseif ($sort === 'date_desc' || !$sort) {
            // 按观演日期降序（最新在前），默认排序
            usort($results, function($a, $b) {
                $date_a = $a['view_date'] ? strtotime($a['view_date']) : 0;
                $date_b = $b['view_date'] ? strtotime($b['view_date']) : 0;
                // 没有日期的排在最后
                if ($date_a === 0 && $date_b === 0) return 0;
                if ($date_a === 0) return 1;
                if ($date_b === 0) return -1;
                return $date_b - $date_a;
            });
        } elseif ($sort === 'title_asc') {
            usort($results, function($a, $b) {
                return strcmp($a['title'], $b['title']);
            });
        } elseif ($sort === 'title_desc') {
            usort($results, function($a, $b) {
                return strcmp($b['title'], $a['title']);
            });
        }

        return rest_ensure_response($results);
    }

    /**
     * 创建观演记录 REST API
     */
    public static function rest_viewings_create($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $params = $request->get_json_params();
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        
        if (empty($title)) {
            return new WP_Error('missing_title', '标题不能为空', array('status' => 400));
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'musicalbum_viewing',
            'post_title' => $title,
            'post_status' => 'publish',
            'post_author' => $user_id
        ));

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // 保存ACF字段
        if (isset($params['category'])) {
            update_field('category', sanitize_text_field($params['category']), $post_id);
        }
        if (isset($params['theater'])) {
            update_field('theater', sanitize_text_field($params['theater']), $post_id);
        }
        if (isset($params['cast'])) {
            update_field('cast', sanitize_text_field($params['cast']), $post_id);
        }
        if (isset($params['price'])) {
            update_field('price', sanitize_text_field($params['price']), $post_id);
        }
        if (isset($params['view_date'])) {
            update_field('view_date', sanitize_text_field($params['view_date']), $post_id);
        }
        if (isset($params['notes'])) {
            update_field('notes', sanitize_textarea_field($params['notes']), $post_id);
        }

        return rest_ensure_response(array(
            'id' => $post_id,
            'message' => '记录创建成功'
        ));
    }

    /**
     * 更新观演记录 REST API
     */
    public static function rest_viewings_update($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'musicalbum_viewing') {
            return new WP_Error('not_found', '记录不存在', array('status' => 404));
        }

        // 检查权限：只能编辑自己的记录，除非是管理员
        if (!current_user_can('manage_options') && intval($post->post_author) !== $user_id) {
            return new WP_Error('forbidden', '无权编辑此记录', array('status' => 403));
        }

        $params = $request->get_json_params();

        // 更新标题
        if (isset($params['title'])) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => sanitize_text_field($params['title'])
            ));
        }

        // 更新ACF字段
        if (isset($params['category'])) {
            update_field('category', sanitize_text_field($params['category']), $post_id);
        }
        if (isset($params['theater'])) {
            update_field('theater', sanitize_text_field($params['theater']), $post_id);
        }
        if (isset($params['cast'])) {
            update_field('cast', sanitize_text_field($params['cast']), $post_id);
        }
        if (isset($params['price'])) {
            update_field('price', sanitize_text_field($params['price']), $post_id);
        }
        if (isset($params['view_date'])) {
            update_field('view_date', sanitize_text_field($params['view_date']), $post_id);
        }
        if (isset($params['notes'])) {
            update_field('notes', sanitize_textarea_field($params['notes']), $post_id);
        }

        return rest_ensure_response(array(
            'id' => $post_id,
            'message' => '记录更新成功'
        ));
    }

    /**
     * 删除观演记录 REST API
     */
    public static function rest_viewings_delete($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'musicalbum_viewing') {
            return new WP_Error('not_found', '记录不存在', array('status' => 404));
        }

        // 检查权限：只能删除自己的记录，除非是管理员
        if (!current_user_can('manage_options') && intval($post->post_author) !== $user_id) {
            return new WP_Error('forbidden', '无权删除此记录', array('status' => 403));
        }

        $result = wp_delete_post($post_id, true);

        if (!$result) {
            return new WP_Error('delete_failed', '删除失败', array('status' => 500));
        }

        return rest_ensure_response(array(
            'message' => '记录删除成功'
        ));
    }

    /**
     * 添加管理菜单：OCR API配置
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'OCR API 配置',
            'OCR API 配置',
            'manage_options',
            'musicalbum-ocr-config',
            array(__CLASS__, 'render_ocr_config_page')
        );
    }

    /**
     * 渲染OCR配置页面
     */
    public static function render_ocr_config_page() {
        // 处理表单提交
        if (isset($_POST['musicalbum_ocr_save']) && check_admin_referer('musicalbum_ocr_config')) {
            $api_key = sanitize_text_field($_POST['baidu_api_key']);
            $secret_key = sanitize_text_field($_POST['baidu_secret_key']);
            
            update_option('musicalbum_baidu_api_key', $api_key);
            update_option('musicalbum_baidu_secret_key', $secret_key);
            
            echo '<div class="notice notice-success is-dismissible"><p>✓ OCR API配置已保存！</p></div>';
        }
        
        // 获取当前配置
        $current_api_key = get_option('musicalbum_baidu_api_key', '');
        $current_secret_key = get_option('musicalbum_baidu_secret_key', '');
        
        ?>
        <div class="wrap">
            <h1>OCR API 配置</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('musicalbum_ocr_config'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="baidu_api_key">百度OCR API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="baidu_api_key" 
                                   name="baidu_api_key" 
                                   value="<?php echo esc_attr($current_api_key); ?>" 
                                   class="regular-text"
                                   placeholder="请输入百度OCR API Key">
                            <p class="description">从百度智能云控制台获取</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="baidu_secret_key">百度OCR Secret Key</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="baidu_secret_key" 
                                   name="baidu_secret_key" 
                                   value="<?php echo esc_attr($current_secret_key); ?>" 
                                   class="regular-text"
                                   placeholder="请输入百度OCR Secret Key">
                            <p class="description">从百度智能云控制台获取</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="musicalbum_ocr_save" 
                           class="button button-primary" 
                           value="保存配置">
                </p>
            </form>
            
            <?php if (!empty($current_api_key) && !empty($current_secret_key)): ?>
                <div class="notice notice-info">
                    <p><strong>当前配置状态：</strong>已配置</p>
                    <p>API Key: <code><?php echo esc_html($current_api_key); ?></code></p>
                    <p>Secret Key: <code><?php echo !empty($current_secret_key) ? '已配置（已隐藏）' : '未配置'; ?></code></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><strong>当前配置状态：</strong>未配置</p>
                    <p>请填写API Key和Secret Key后点击"保存配置"。</p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>快速配置（使用你的密钥）</h2>
                <p>你的百度OCR API密钥信息：</p>
                <ul>
                    <li><strong>API Key:</strong> <code>8vPJwV02JbdApar643L2J8ft</code></li>
                    <li><strong>Secret Key:</strong> <code>gt4sMnjFvHlIyk3qLUTCiXz93KaK1PhV</code></li>
                </ul>
                <p>请将上述密钥填入上方表单并保存。</p>
            </div>
        </div>
        <?php
    }
}

// 启动插件
Musicalbum_Integrations::init();
