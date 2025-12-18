<?php
/*
Plugin Name: Viewing Records
Description: 观演记录管理插件，支持记录管理、数据统计和OCR识别功能。
Version: 0.1.0
Author: chen ziang
*/

defined('ABSPATH') || exit;

/**
 * 观演记录插件主类
 *
 * - 注册短码供页面/模板插入功能模块
 * - 注册自定义文章类型存储观演记录
 * - 注册 REST 路由（OCR 与 iCalendar 导出）
 * - 代码化声明 ACF 字段结构（非内容值）
 * - 入队前端资源并注入 REST 端点与安全 nonce
 */
final class Viewing_Records {
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
        
        // 在单篇文章页面显示观演记录详情
        add_filter('the_content', array(__CLASS__, 'display_viewing_record_details'));
        
        // 注册激活和停用钩子
        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
        
        // 示例：与第三方插件交互（替换为实际钩子）
        // add_filter('some_plugin_output', [__CLASS__, 'filter_some_plugin_output'], 10, 1);
    }
    
    /**
     * 插件激活时的处理
     */
    public static function activate() {
        // 检查是否需要数据迁移
        $migration_done = get_option('viewing_records_migration_done', false);
        if (!$migration_done) {
            // 在后台异步执行迁移，避免激活时超时
            add_action('admin_init', array(__CLASS__, 'maybe_migrate_data'));
        }
    }
    
    /**
     * 检查并执行数据迁移
     */
    public static function maybe_migrate_data() {
        $migration_done = get_option('viewing_records_migration_done', false);
        if ($migration_done) {
            return;
        }
        
        // 检查是否有旧数据需要迁移
        $old_posts = get_posts(array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        $has_old_options = get_option('musicalbum_baidu_api_key', false);
        
        if (!empty($old_posts) || $has_old_options) {
            // 有旧数据，执行迁移（使用简化版本，不返回结果）
            self::migrate_data_simple();
        } else {
            // 没有旧数据，标记为已完成
            update_option('viewing_records_migration_done', true);
        }
    }
    

    /**
     * 注册短码：
     * - [viewing_hello] / [musicalbum_hello] (兼容)
     * - [viewing_form] / [musicalbum_viewing_form] (兼容)
     * - [viewing_list] / [musicalbum_profile_viewings] (兼容)
     * - [viewing_statistics] / [musicalbum_statistics] (兼容)
     * - [viewing_manager] / [musicalbum_viewing_manager] (兼容)
     */
    public static function register_shortcodes() {
        // 新短码名称
        add_shortcode('viewing_hello', array(__CLASS__, 'shortcode_hello'));
        add_shortcode('viewing_form', array(__CLASS__, 'shortcode_viewing_form'));
        add_shortcode('viewing_list', array(__CLASS__, 'shortcode_profile_viewings'));
        add_shortcode('viewing_manager', array(__CLASS__, 'shortcode_viewing_manager'));
        add_shortcode('viewing_dashboard', array(__CLASS__, 'shortcode_dashboard'));
        
        // 兼容旧短码名称
        add_shortcode('musicalbum_hello', array(__CLASS__, 'shortcode_hello'));
        add_shortcode('musicalbum_viewing_form', array(__CLASS__, 'shortcode_viewing_form'));
        add_shortcode('musicalbum_profile_viewings', array(__CLASS__, 'shortcode_profile_viewings'));
        add_shortcode('musicalbum_statistics', array(__CLASS__, 'shortcode_statistics'));
        add_shortcode('musicalbum_custom_chart', array(__CLASS__, 'shortcode_custom_chart'));
        add_shortcode('musicalbum_viewing_manager', array(__CLASS__, 'shortcode_viewing_manager'));
        add_shortcode('musicalbum_dashboard', array(__CLASS__, 'shortcode_dashboard'));
    }

    /**
     * 示例短码：输出简单的欢迎块
     */
    public static function shortcode_hello($atts = array(), $content = '') {
        return '<div class="viewing-hello">Hello Viewing Records</div>';
    }

    /**
     * 前端资源入队：样式与脚本
     * 脚本通过 wp_localize_script 注入 REST 端点与 nonce
     */
    public static function enqueue_assets() {
        // 只在需要的地方加载资源（短码页面、观演管理页面、单篇文章页面）
        $load_assets = false;
        
        // 检查是否有短码
        global $post;
        if ($post && (
            has_shortcode($post->post_content, 'viewing_hello') ||
            has_shortcode($post->post_content, 'viewing_form') ||
            has_shortcode($post->post_content, 'viewing_list') ||
            has_shortcode($post->post_content, 'viewing_manager') ||
            has_shortcode($post->post_content, 'viewing_dashboard') ||
            has_shortcode($post->post_content, 'musicalbum_hello') ||
            has_shortcode($post->post_content, 'musicalbum_viewing_form') ||
            has_shortcode($post->post_content, 'musicalbum_profile_viewings') ||
            has_shortcode($post->post_content, 'musicalbum_statistics') ||
            has_shortcode($post->post_content, 'musicalbum_custom_chart') ||
            has_shortcode($post->post_content, 'musicalbum_viewing_manager') ||
            has_shortcode($post->post_content, 'musicalbum_dashboard')
        )) {
            $load_assets = true;
        }
        
        // 检查是否是观演记录单篇文章页面
        if (is_singular() && in_array(get_post_type(), array('viewing_record', 'musicalbum_viewing'))) {
            $load_assets = true;
        }
        
        if (!$load_assets) {
            return;
        }
        
        wp_register_style('viewing-records', plugins_url('assets/integrations.css', __FILE__), array(), '0.3.2');
        wp_enqueue_style('viewing-records');
        
        // 获取主题颜色并注入动态 CSS
        $theme_colors = self::get_theme_colors();
        $dynamic_css = self::generate_theme_colored_css($theme_colors);
        wp_add_inline_style('viewing-records', $dynamic_css);
        
        // 为仪表板添加额外的内联样式，确保样式优先级
        if (has_shortcode($post->post_content, 'musicalbum_dashboard') || has_shortcode($post->post_content, 'viewing_dashboard')) {
            $dashboard_css = '
                .musicalbum-dashboard-container .musicalbum-dashboard-card {
                    display: block !important;
                    padding: 2rem !important;
                    background: #fff !important;
                    border: 2px solid #e5e7eb !important;
                    border-radius: 12px !important;
                    text-decoration: none !important;
                    color: #1f2937 !important;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
                }
                .musicalbum-dashboard-container .musicalbum-dashboard-card:hover {
                    border-color: #3b82f6 !important;
                    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15) !important;
                }
                .musicalbum-dashboard-container .musicalbum-dashboard-card h3 {
                    color: #1f2937 !important;
                    text-decoration: none !important;
                    border: none !important;
                }
                .musicalbum-dashboard-container .musicalbum-dashboard-stat-card {
                    display: flex !important;
                    flex-direction: column !important;
                    justify-content: center !important;
                    align-items: center !important;
                }
            ';
            wp_add_inline_style('viewing-records', $dashboard_css);
        }
        
        // 引入 Chart.js 库
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        // 引入 FullCalendar 库（用于日历视图）
        wp_enqueue_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css', array(), '6.1.10');
        wp_enqueue_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js', array(), '6.1.10', true);
        // 引入 FullCalendar 中文语言包
        wp_enqueue_script('fullcalendar-locale', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/zh-cn.global.min.js', array('fullcalendar'), '6.1.10', true);
        wp_register_script('viewing-records', plugins_url('assets/integrations.js', __FILE__), array('jquery', 'chart-js', 'fullcalendar'), '0.3.0', true);
        wp_localize_script('viewing-records', 'ViewingRecords', array(
            'rest' => array(
                'ocr' => esc_url_raw(rest_url('viewing/v1/ocr')),
                'statistics' => esc_url_raw(rest_url('viewing/v1/statistics')),
                'statisticsDetails' => esc_url_raw(rest_url('viewing/v1/statistics/details')),
                'statisticsExport' => esc_url_raw(rest_url('viewing/v1/statistics/export')),
                'dashboard' => esc_url_raw(rest_url('viewing/v1/dashboard')),
                'viewings' => esc_url_raw(rest_url('viewing/v1/viewings')),
                'uploadImage' => esc_url_raw(rest_url('viewing/v1/upload-image')),
                'nonce' => wp_create_nonce('wp_rest')
            )
        ));
        wp_enqueue_script('viewing-records');
    }
    
    /**
     * 获取主题颜色
     */
    private static function get_theme_colors() {
        // 优先使用 CSS 变量（Astra 主题支持）
        $primary_color = 'var(--ast-global-color-0, var(--wp--preset--color--primary, #3b82f6))';
        $secondary_color = 'var(--ast-global-color-1, var(--wp--preset--color--secondary, #10b981))';
        $accent_color = 'var(--ast-global-color-2, var(--wp--preset--color--accent, #8b5cf6))';
        
        // 尝试从主题设置获取颜色（Astra 主题）
        $astra_primary = get_theme_mod('astra-color-palette-primary', '');
        $astra_secondary = get_theme_mod('astra-color-palette-secondary', '');
        $astra_accent = get_theme_mod('astra-color-palette-accent', '');
        
        // 如果获取到具体颜色值，使用具体值；否则使用 CSS 变量
        if (!empty($astra_primary) && strpos($astra_primary, '#') === 0) {
            $primary_color = $astra_primary;
        }
        if (!empty($astra_secondary) && strpos($astra_secondary, '#') === 0) {
            $secondary_color = $astra_secondary;
        }
        if (!empty($astra_accent) && strpos($astra_accent, '#') === 0) {
            $accent_color = $astra_accent;
        }
        
        // 计算悬停颜色
        // 如果是 CSS 变量，使用 filter: brightness() 或保持原样
        // 如果是具体颜色值，计算加深后的颜色
        $primary_hover = (strpos($primary_color, 'var(') !== false) 
            ? $primary_color 
            : self::darken_color($primary_color, 10);
        $secondary_hover = (strpos($secondary_color, 'var(') !== false) 
            ? $secondary_color 
            : self::darken_color($secondary_color, 10);
        $accent_hover = (strpos($accent_color, 'var(') !== false) 
            ? $accent_color 
            : self::darken_color($accent_color, 10);
        
        return array(
            'primary' => $primary_color,
            'primary_hover' => $primary_hover,
            'secondary' => $secondary_color,
            'secondary_hover' => $secondary_hover,
            'accent' => $accent_color,
            'accent_hover' => $accent_hover,
        );
    }
    
    /**
     * 生成使用主题颜色的动态 CSS
     */
    private static function generate_theme_colored_css($colors) {
        // 对于 CSS 变量，悬停时使用 filter: brightness()
        $primary_hover_style = (strpos($colors['primary'], 'var(') !== false) 
            ? 'filter: brightness(0.9);' 
            : 'background: ' . esc_attr($colors['primary_hover']) . ' !important;';
        $secondary_hover_style = (strpos($colors['secondary'], 'var(') !== false) 
            ? 'filter: brightness(0.9);' 
            : 'background: ' . esc_attr($colors['secondary_hover']) . ' !important;';
        $accent_hover_style = (strpos($colors['accent'], 'var(') !== false) 
            ? 'filter: brightness(0.9);' 
            : 'background: ' . esc_attr($colors['accent_hover']) . ' !important;';
        
        $css = '
        /* 主题颜色覆盖 - 使用主题颜色变量 */
        .musicalbum-btn {
            background: ' . esc_attr($colors['primary']) . ' !important;
        }
        .musicalbum-btn:hover {
            ' . $primary_hover_style . '
        }
        .musicalbum-btn-refresh {
            background: ' . esc_attr($colors['secondary']) . ' !important;
        }
        .musicalbum-btn-refresh:hover {
            ' . $secondary_hover_style . '
        }
        .musicalbum-btn-export {
            background: ' . esc_attr($colors['accent']) . ' !important;
        }
        .musicalbum-btn-export:hover {
            ' . $accent_hover_style . '
        }
        .musicalbum-btn-primary {
            background: ' . esc_attr($colors['primary']) . ' !important;
        }
        .musicalbum-btn-primary:hover {
            ' . $primary_hover_style . '
        }
        .musicalbum-details-item:hover {
            border-color: ' . esc_attr($colors['primary']) . ' !important;
        }
        .musicalbum-details-item h4 a {
            color: ' . esc_attr($colors['primary']) . ' !important;
        }
        .musicalbum-details-item h4 a:hover {
            ' . ((strpos($colors['primary'], 'var(') !== false) ? 'filter: brightness(0.85);' : 'color: ' . esc_attr($colors['primary_hover']) . ' !important;') . '
        }
        .musicalbum-tab-btn.active {
            color: ' . esc_attr($colors['primary']) . ' !important;
            border-bottom-color: ' . esc_attr($colors['primary']) . ' !important;
        }
        .musicalbum-form-group input:focus,
        .musicalbum-form-group select:focus,
        .musicalbum-form-group textarea:focus {
            border-color: ' . esc_attr($colors['primary']) . ' !important;
            box-shadow: 0 0 0 3px ' . esc_attr(self::hex_to_rgba($colors['primary'], 0.1)) . ' !important;
        }
        .musicalbum-view-btn.active {
            background: ' . esc_attr($colors['primary']) . ' !important;
            color: #fff !important;
        }
        .musicalbum-calendar-nav-label {
            color: ' . esc_attr($colors['primary']) . ' !important;
        }
        ';
        
        return $css;
    }
    
    /**
     * 将十六进制颜色转换为 rgba（用于 box-shadow）
     */
    private static function hex_to_rgba($hex, $alpha = 1) {
        // 如果是 CSS 变量，返回默认值
        if (strpos($hex, 'var(') !== false) {
            return 'rgba(59, 130, 246, ' . $alpha . ')';
        }
        
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ')';
    }
    
    /**
     * 加深颜色（用于悬停效果）
     */
    private static function darken_color($color, $percent) {
        // 如果是 CSS 变量，直接返回
        if (strpos($color, 'var(') !== false) {
            // 对于 CSS 变量，使用 filter: brightness() 或返回原色
            return $color;
        }
        
        // 移除 # 号
        $color = ltrim($color, '#');
        
        // 转换为 RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // 加深
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        // 转换回十六进制
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
                   str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
                   str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

    /**
     * 示例过滤器：用于演示与第三方插件输出交互
     */
    public static function filter_some_plugin_output($output) {
        return $output;
    }

    /**
     * 注册自定义文章类型：viewing_record（观演记录）
     */
    public static function register_viewing_post_type() {
        // 注册新的文章类型
        register_post_type('viewing_record', array(
            'labels' => array(
                'name' => '观演记录',
                'singular_name' => '观演记录',
                'add_new' => '添加新记录',
                'add_new_item' => '添加新观演记录',
                'edit_item' => '编辑观演记录',
                'new_item' => '新观演记录',
                'view_item' => '查看观演记录',
                'search_items' => '搜索观演记录',
                'not_found' => '未找到观演记录',
                'not_found_in_trash' => '回收站中未找到观演记录'
            ),
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => array('title'),
            'menu_position' => 20,
            'menu_icon' => 'dashicons-calendar-alt'
        ));
    }

    /**
     * 声明 ACF 本地字段组：仅结构，非数据
     * 在 ACF 激活时注册，便于字段随代码版本化
     */
    public static function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) { return; }
        acf_add_local_field_group(array(
            'key' => 'group_viewing_record',
            'title' => '观演字段',
            'fields' => array(
                array(
                    'key' => 'field_viewing_category',
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
                    'key' => 'field_viewing_theater',
                    'label' => '剧院',
                    'name' => 'theater',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_viewing_cast',
                    'label' => '卡司',
                    'name' => 'cast',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_viewing_price',
                    'label' => '票价',
                    'name' => 'price',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_viewing_date',
                    'label' => '观演日期',
                    'name' => 'view_date',
                    'type' => 'date_picker',
                    'display_format' => 'Y-m-d',
                    'return_format' => 'Y-m-d'
                ),
                array(
                    'key' => 'field_viewing_time_start',
                    'label' => '观演开始时间',
                    'name' => 'view_time_start',
                    'type' => 'time_picker',
                    'display_format' => 'H:i',
                    'return_format' => 'H:i'
                ),
                array(
                    'key' => 'field_viewing_time_end',
                    'label' => '观演结束时间',
                    'name' => 'view_time_end',
                    'type' => 'time_picker',
                    'display_format' => 'H:i',
                    'return_format' => 'H:i'
                ),
                array(
                    'key' => 'field_viewing_ticket',
                    'label' => '票面图片',
                    'name' => 'ticket_image',
                    'type' => 'image',
                    'return_format' => 'array'
                ),
                array(
                    'key' => 'field_viewing_notes',
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
                        'value' => 'viewing_record'
                    )
                ),
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
                'post_type' => 'viewing_record',
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
            'post_type' => array('viewing_record', 'musicalbum_viewing'), // 兼容旧数据
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
        register_rest_route('viewing/v1', '/ocr', array(
            'methods' => 'POST',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_ocr')
        ));
        register_rest_route('viewing/v1', '/viewings.ics', array(
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => array(__CLASS__, 'rest_ics')
        ));
        register_rest_route('viewing/v1', '/statistics', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics')
        ));
        register_rest_route('viewing/v1', '/dashboard', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_dashboard')
        ));
        register_rest_route('viewing/v1', '/statistics/details', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics_details')
        ));
        register_rest_route('viewing/v1', '/statistics/export', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_statistics_export')
        ));
        // 观演记录管理 API
        register_rest_route('viewing/v1', '/viewings', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_list')
        ));
        register_rest_route('viewing/v1', '/viewings', array(
            'methods' => 'POST',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_create')
        ));
        register_rest_route('viewing/v1', '/viewings/(?P<id>\d+)', array(
            'methods' => 'GET',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_get'),
            'args' => array('id' => array('type' => 'integer'))
        ));
        register_rest_route('viewing/v1', '/viewings/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_update'),
            'args' => array('id' => array('type' => 'integer'))
        ));
        register_rest_route('viewing/v1', '/viewings/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_viewings_delete'),
            'args' => array('id' => array('type' => 'integer'))
        ));
        
        // 图片上传端点
        register_rest_route('viewing/v1', '/upload-image', array(
            'methods' => 'POST',
            'permission_callback' => function($req){ return is_user_logged_in(); },
            'callback' => array(__CLASS__, 'rest_upload_image')
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
        
        $result = apply_filters('viewing_ocr_process', null, $data);
        if (!is_array($result)) {
            // 向后兼容：同时读取新旧选项名称
            $provider = get_option('viewing_ocr_provider') ?: get_option('musicalbum_ocr_provider');
            $baidu_api_key = get_option('viewing_baidu_api_key') ?: get_option('musicalbum_baidu_api_key');
            $baidu_secret_key = get_option('viewing_baidu_secret_key') ?: get_option('musicalbum_baidu_secret_key');
            $aliyun_api_key = get_option('viewing_aliyun_api_key') ?: get_option('musicalbum_aliyun_api_key');
            $aliyun_endpoint = get_option('viewing_aliyun_endpoint') ?: get_option('musicalbum_aliyun_endpoint');
            
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
        // 向后兼容：同时读取新旧选项名称
        $api_key = get_option('viewing_baidu_api_key') ?: get_option('musicalbum_baidu_api_key');
        $secret_key = get_option('viewing_baidu_secret_key') ?: get_option('musicalbum_baidu_secret_key');
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
        // 向后兼容：同时读取新旧选项名称
        $api_key = get_option('viewing_aliyun_api_key') ?: get_option('musicalbum_aliyun_api_key');
        $endpoint = get_option('viewing_aliyun_endpoint') ?: get_option('musicalbum_aliyun_endpoint');
        $mode = get_option('viewing_aliyun_mode') ?: get_option('musicalbum_aliyun_mode');
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
        $args = array('post_type' => array('viewing_record', 'musicalbum_viewing'), 'posts_per_page' => -1, 'post_status' => 'publish');
        $q = new WP_Query($args);
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//ViewingRecords//Viewing//CN'
        );
        while($q->have_posts()){ $q->the_post();
            $date = get_field('view_date', get_the_ID());
            if (!$date) { continue; }
            $dt = preg_replace('/-/', '', $date);
            $summary = get_the_title();
            $desc = trim('剧院: '.(get_field('theater', get_the_ID()) ?: '')."\n".'卡司: '.(get_field('cast', get_the_ID()) ?: '')."\n".'票价: '.(get_field('price', get_the_ID()) ?: ''));
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . get_the_ID() . '@viewing';
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
     * 使用 [viewing_statistics] 或 [musicalbum_statistics] 在页面中插入
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
            
            <!-- 固定图表显示区域 -->
            <div class="musicalbum-charts-grid" id="musicalbum-fixed-charts">
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
     * 自定义图表短码：显示可自定义的数据可视化图表
     * 使用 [musicalbum_custom_chart] 在页面中插入
     */
    public static function shortcode_custom_chart($atts = array(), $content = '') {
        if (!is_user_logged_in()) {
            return '<div class="musicalbum-statistics-error">请先登录以查看统计数据</div>';
        }
        
        // 生成唯一ID，支持页面中多个实例
        $instance_id = 'custom-chart-' . wp_generate_uuid4();
        $instance_id = sanitize_html_class($instance_id);
        
        ob_start();
        ?>
        <div class="musicalbum-custom-charts-section" data-instance-id="<?php echo esc_attr($instance_id); ?>">
            <h2 class="musicalbum-custom-charts-title">自定义图表</h2>
            
            <!-- 图表配置面板 -->
            <div class="musicalbum-chart-config-panel">
                <div class="musicalbum-chart-config-item">
                    <label for="musicalbum-data-type-<?php echo esc_attr($instance_id); ?>">数据类型：</label>
                    <select id="musicalbum-data-type-<?php echo esc_attr($instance_id); ?>" class="musicalbum-select" data-instance-id="<?php echo esc_attr($instance_id); ?>">
                        <option value="category">剧目类别</option>
                        <option value="theater">剧院</option>
                        <option value="cast">演员出场频率</option>
                        <option value="price">票价区间</option>
                    </select>
                </div>
                <div class="musicalbum-chart-config-item">
                    <label for="musicalbum-chart-type-<?php echo esc_attr($instance_id); ?>">图表类型：</label>
                    <select id="musicalbum-chart-type-<?php echo esc_attr($instance_id); ?>" class="musicalbum-select" data-instance-id="<?php echo esc_attr($instance_id); ?>">
                        <option value="pie">饼图</option>
                        <option value="bar">柱状图</option>
                        <option value="line">折线图</option>
                        <option value="doughnut">环形图</option>
                    </select>
                </div>
                <button type="button" class="musicalbum-btn musicalbum-btn-primary musicalbum-generate-chart-btn" data-instance-id="<?php echo esc_attr($instance_id); ?>">生成图表</button>
            </div>
            
            <!-- 自定义图表显示区域 -->
            <div class="musicalbum-charts-grid" id="musicalbum-custom-charts-container-<?php echo esc_attr($instance_id); ?>">
                <div class="musicalbum-chart-wrapper" id="musicalbum-chart-wrapper-<?php echo esc_attr($instance_id); ?>">
                    <h3 id="musicalbum-chart-title-<?php echo esc_attr($instance_id); ?>">请选择数据类型和图表类型</h3>
                    <canvas id="musicalbum-chart-main-<?php echo esc_attr($instance_id); ?>"></canvas>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 观影点滴仪表板短码：父页面概览
     * 使用 [musicalbum_dashboard] 或 [viewing_dashboard] 在页面中插入
     */
    public static function shortcode_dashboard($atts = array(), $content = '') {
        if (!is_user_logged_in()) {
            return '<div class="musicalbum-statistics-error">请先登录以查看观影点滴</div>';
        }
        
        // 解析短码属性，允许自定义子页面链接
        $atts = shortcode_atts(array(
            'manager_url' => 'https://musicalbum.chenpan.icu/我的观演管理/',
            'statistics_url' => 'https://musicalbum.chenpan.icu/我的观演统计/'
        ), $atts);
        
        $manager_url = esc_url($atts['manager_url']);
        $statistics_url = esc_url($atts['statistics_url']);
        
        ob_start();
        ?>
        <div class="musicalbum-dashboard-container" style="max-width: 1200px !important; margin: 2rem auto !important; padding: 2rem 1.5rem !important; background: #f8fafc !important; border-radius: 16px !important; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important; width: 100% !important; box-sizing: border-box !important;">
            <div class="musicalbum-dashboard-header" style="text-align: center !important; margin-bottom: 3rem !important; padding-bottom: 2rem !important; border-bottom: 2px solid #e5e7eb !important; width: 100% !important;">
                <h1 class="musicalbum-dashboard-title" style="font-size: 3rem !important; font-weight: 800 !important; margin: 0 0 0.75rem 0 !important; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; -webkit-background-clip: text !important; -webkit-text-fill-color: transparent !important; background-clip: text !important; letter-spacing: -0.02em !important; color: transparent !important; display: block !important;">观影点滴</h1>
                <p class="musicalbum-dashboard-subtitle" style="font-size: 1.25rem !important; color: #64748b !important; margin: 0 !important; font-weight: 400 !important; display: block !important;">记录每一次观演的美好时光</p>
            </div>
            
            <!-- 快速导航卡片 -->
            <div class="musicalbum-dashboard-nav" style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important; gap: 1.5rem !important; margin-bottom: 3rem !important; width: 100% !important; box-sizing: border-box !important;">
                <a href="<?php echo esc_url($manager_url); ?>" class="musicalbum-dashboard-card" style="display: block !important; padding: 2.5rem 2rem !important; background: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; text-decoration: none !important; color: #1f2937 !important; text-align: center !important; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important; width: 100% !important; box-sizing: border-box !important;">
                    <div class="musicalbum-dashboard-card-icon" style="font-size: 3.5rem !important; margin-bottom: 1.25rem !important; display: block !important;">📝</div>
                    <h3 style="font-size: 1.5rem !important; font-weight: 700 !important; margin: 0 0 0.75rem 0 !important; color: #1e293b !important; text-decoration: none !important; border: none !important; padding: 0 !important;">记录管理</h3>
                    <p style="font-size: 0.9375rem !important; color: #64748b !important; margin: 0 !important; text-decoration: none !important; line-height: 1.6 !important;">管理您的观演记录，添加、编辑或删除记录</p>
                </a>
                <a href="<?php echo esc_url($statistics_url); ?>" class="musicalbum-dashboard-card" style="display: block !important; padding: 2.5rem 2rem !important; background: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; text-decoration: none !important; color: #1f2937 !important; text-align: center !important; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important; width: 100% !important; box-sizing: border-box !important;">
                    <div class="musicalbum-dashboard-card-icon" style="font-size: 3.5rem !important; margin-bottom: 1.25rem !important; display: block !important;">📊</div>
                    <h3 style="font-size: 1.5rem !important; font-weight: 700 !important; margin: 0 0 0.75rem 0 !important; color: #1e293b !important; text-decoration: none !important; border: none !important; padding: 0 !important;">数据统计</h3>
                    <p style="font-size: 0.9375rem !important; color: #64748b !important; margin: 0 !important; text-decoration: none !important; line-height: 1.6 !important;">查看观演数据可视化图表和统计分析</p>
                </a>
            </div>
            
            <!-- 数据概览 -->
            <div class="musicalbum-dashboard-overview" id="musicalbum-dashboard-overview" style="margin-bottom: 3rem !important; width: 100% !important;">
                <h2 class="musicalbum-dashboard-section-title" style="font-size: 1.75rem !important; font-weight: 700 !important; margin: 0 0 1.5rem 0 !important; color: #1e293b !important; position: relative !important; padding-bottom: 0.75rem !important; display: block !important; width: 100% !important;">数据概览</h2>
                <div class="musicalbum-dashboard-stats-grid" style="display: grid !important; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important; gap: 1.5rem !important; width: 100% !important;">
                    <div class="musicalbum-dashboard-stat-card" style="padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 120px;">
                        <div class="stat-value" id="stat-total-count" style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff; display: block;">-</div>
                        <div class="stat-label" style="font-size: 0.875rem; opacity: 0.9; color: #fff; display: block;">总记录数</div>
                    </div>
                    <div class="musicalbum-dashboard-stat-card" style="padding: 1.5rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 120px;">
                        <div class="stat-value" id="stat-this-month" style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff; display: block;">-</div>
                        <div class="stat-label" style="font-size: 0.875rem; opacity: 0.9; color: #fff; display: block;">本月观演</div>
                    </div>
                    <div class="musicalbum-dashboard-stat-card" style="padding: 1.5rem; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 120px;">
                        <div class="stat-value" id="stat-total-spent" style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff; display: block;">-</div>
                        <div class="stat-label" style="font-size: 0.875rem; opacity: 0.9; color: #fff; display: block;">总花费</div>
                    </div>
                    <div class="musicalbum-dashboard-stat-card" style="padding: 1.5rem; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 12px; color: #fff; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 120px;">
                        <div class="stat-value" id="stat-favorite-category" style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff; display: block;">-</div>
                        <div class="stat-label" style="font-size: 0.875rem; opacity: 0.9; color: #fff; display: block;">最爱类别</div>
                    </div>
                </div>
            </div>
            
            <!-- 最近观演记录 -->
            <div class="musicalbum-dashboard-recent" id="musicalbum-dashboard-recent" style="margin-bottom: 3rem !important; width: 100% !important;">
                <h2 class="musicalbum-dashboard-section-title" style="font-size: 1.75rem !important; font-weight: 700 !important; margin: 0 0 1.5rem 0 !important; color: #1e293b !important; position: relative !important; padding-bottom: 0.75rem !important; display: block !important; width: 100% !important;">最近观演</h2>
                <div class="musicalbum-dashboard-recent-list" id="musicalbum-recent-list" style="background: #fff !important; border: 1px solid #e2e8f0 !important; border-radius: 16px !important; overflow: hidden !important; width: 100% !important; box-sizing: border-box !important; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;">
                    <div class="musicalbum-dashboard-loading">加载中...</div>
                </div>
            </div>
            <style>
                .musicalbum-dashboard-recent-item .recent-title,
                .musicalbum-dashboard-recent-item .recent-title:link,
                .musicalbum-dashboard-recent-item .recent-title:visited,
                .musicalbum-dashboard-recent-item .recent-title:active {
                    color: #1e293b !important;
                    text-decoration: none !important;
                    border: none !important;
                }
                .musicalbum-dashboard-recent-item .recent-title:hover {
                    color: #667eea !important;
                    text-decoration: none !important;
                }
            </style>
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
            'post_type' => array('viewing_record', 'musicalbum_viewing'), // 兼容旧数据
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
        $theater_data = array(); // 剧院分布

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $title = get_the_title();
            $cast = get_field('cast', $post_id);
            $price = get_field('price', $post_id);
            $theater = get_field('theater', $post_id);

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

            // 统计剧院分布
            if ($theater && trim($theater) !== '') {
                $theater_clean = trim($theater);
                $theater_data[$theater_clean] = isset($theater_data[$theater_clean]) ? $theater_data[$theater_clean] + 1 : 1;
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
        
        // 对剧院分布排序，取前10名
        arsort($theater_data);
        $theater_data = array_slice($theater_data, 0, 10, true);

        return rest_ensure_response(array(
            'category' => $category_data,
            'cast' => $cast_data,
            'price' => $price_ranges,
            'theater' => $theater_data
        ));
    }

    /**
     * 仪表板数据 REST API 端点
     * 返回概览统计数据（总记录数、本月观演、总花费、最爱类别等）
     */
    public static function rest_dashboard($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $args = array(
            'post_type' => array('viewing_record', 'musicalbum_viewing'),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );
        
        if (!current_user_can('manage_options')) {
            $args['author'] = $user_id;
        }
        
        $query = new WP_Query($args);
        
        $total_count = $query->post_count;
        $this_month_count = 0;
        $total_spent = 0;
        $category_data = array();
        $recent_viewings = array();
        $current_month = date('Y-m');
        
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $view_date = get_field('view_date', $post_id);
            $price = get_field('price', $post_id);
            $category = get_field('category', $post_id);
            
            // 统计本月观演
            if ($view_date && strpos($view_date, $current_month) === 0) {
                $this_month_count++;
            }
            
            // 统计总花费
            if ($price) {
                $price_num = floatval(preg_replace('/[^0-9.]/', '', $price));
                if ($price_num > 0) {
                    $total_spent += $price_num;
                }
            }
            
            // 统计类别
            if ($category) {
                $category_data[$category] = isset($category_data[$category]) ? $category_data[$category] + 1 : 1;
            }
            
            // 收集最近5条记录
            if (count($recent_viewings) < 5) {
                $recent_viewings[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'date' => $view_date ?: get_the_date('Y-m-d'),
                    'category' => $category ?: '未分类',
                    'theater' => get_field('theater', $post_id) ?: '',
                    'url' => get_permalink()
                );
            }
        }
        wp_reset_postdata();
        
        // 找出最爱类别
        $favorite_category = '暂无';
        if (!empty($category_data)) {
            arsort($category_data);
            $favorite_category = array_key_first($category_data);
        }
        
        return rest_ensure_response(array(
            'total_count' => $total_count,
            'this_month' => $this_month_count,
            'total_spent' => round($total_spent, 2),
            'favorite_category' => $favorite_category,
            'recent_viewings' => $recent_viewings
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
            'post_type' => array('viewing_record', 'musicalbum_viewing'), // 兼容旧数据
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
            'post_type' => array('viewing_record', 'musicalbum_viewing'), // 兼容旧数据
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
     * 使用 [viewing_manager] 或 [musicalbum_viewing_manager] 在页面中插入
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
            <!-- 编辑表单模态框 -->
            <div id="musicalbum-form-modal" class="musicalbum-modal" style="display: none;">
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
                                    <div class="musicalbum-calendar-input-wrapper">
                                        <input type="text" id="musicalbum-form-date" name="view_date" class="musicalbum-calendar-date-input" placeholder="YYYY-MM-DD或点击选择" autocomplete="off">
                                        <input type="date" id="musicalbum-form-date-picker" class="musicalbum-calendar-date-picker" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">
                                        <button type="button" class="musicalbum-calendar-icon-btn" title="选择日期">📅</button>
                                    </div>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label>观演时间</label>
                                    <div style="display:flex;gap:1rem;align-items:flex-end;">
                                        <div style="flex:1;">
                                            <label for="musicalbum-form-time-start" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">开始时间</label>
                                            <input type="time" id="musicalbum-form-time-start" name="view_time_start" placeholder="例如：19:30">
                                        </div>
                                        <div style="flex:1;">
                                            <label for="musicalbum-form-time-end" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">结束时间</label>
                                            <input type="time" id="musicalbum-form-time-end" name="view_time_end" placeholder="例如：22:00">
                                        </div>
                                    </div>
                                    <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，填写观演的开始和结束时间</p>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-notes">备注</label>
                                    <textarea id="musicalbum-form-notes" name="notes" rows="4"></textarea>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-form-ticket-image">票面图片</label>
                                    <input type="file" id="musicalbum-form-ticket-image" name="ticket_image" accept="image/*">
                                    <div id="musicalbum-form-ticket-preview" style="margin-top: 0.5rem;"></div>
                                    <input type="hidden" id="musicalbum-form-ticket-image-id" name="ticket_image_id" value="">
                                    <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，上传票面图片</p>
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
                                    <div class="musicalbum-calendar-input-wrapper">
                                        <input type="text" id="musicalbum-ocr-date" name="view_date" class="musicalbum-calendar-date-input" placeholder="输入日期（YYYY-MM-DD）或点击选择" autocomplete="off">
                                        <input type="date" id="musicalbum-ocr-date-picker" class="musicalbum-calendar-date-picker" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">
                                        <button type="button" class="musicalbum-calendar-icon-btn" title="选择日期">📅</button>
                                    </div>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label>观演时间</label>
                                    <div style="display:flex;gap:1rem;align-items:flex-end;">
                                        <div style="flex:1;">
                                            <label for="musicalbum-ocr-time-start" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">开始时间</label>
                                            <input type="time" id="musicalbum-ocr-time-start" name="view_time_start" placeholder="例如：19:30">
                                        </div>
                                        <div style="flex:1;">
                                            <label for="musicalbum-ocr-time-end" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">结束时间</label>
                                            <input type="time" id="musicalbum-ocr-time-end" name="view_time_end" placeholder="例如：22:00">
                                        </div>
                                    </div>
                                    <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，填写观演的开始和结束时间</p>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-notes">备注</label>
                                    <textarea id="musicalbum-ocr-notes" name="notes" rows="4"></textarea>
                                </div>
                                <div class="musicalbum-form-group">
                                    <label for="musicalbum-ocr-ticket-image">票面图片</label>
                                    <input type="file" id="musicalbum-ocr-ticket-image" name="ticket_image" accept="image/*">
                                    <div id="musicalbum-ocr-ticket-preview" style="margin-top: 0.5rem;"></div>
                                    <input type="hidden" id="musicalbum-ocr-ticket-image-id" name="ticket_image_id" value="">
                                    <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，上传票面图片（OCR识别的图片会自动保存）</p>
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
            'post_type' => array('viewing_record', 'musicalbum_viewing'), // 兼容旧数据
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
            
            // 处理票面图片数据
            $ticket_image_field = get_field('ticket_image', $post_id);
            $ticket_image_data = null;
            if ($ticket_image_field) {
                if (is_array($ticket_image_field)) {
                    $ticket_image_data = array(
                        'id' => isset($ticket_image_field['ID']) ? $ticket_image_field['ID'] : (isset($ticket_image_field['id']) ? $ticket_image_field['id'] : ''),
                        'url' => isset($ticket_image_field['url']) ? $ticket_image_field['url'] : ''
                    );
                } else {
                    // 如果是附件ID
                    $image_url = wp_get_attachment_image_url($ticket_image_field, 'full');
                    $ticket_image_data = array(
                        'id' => $ticket_image_field,
                        'url' => $image_url ? $image_url : ''
                    );
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
                'view_time_start' => get_field('view_time_start', $post_id),
                'view_time_end' => get_field('view_time_end', $post_id),
                'notes' => $notes,
                'ticket_image' => $ticket_image_data,
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
     * 获取单个观演记录 REST API
     */
    public static function rest_viewings_get($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }

        $post_id = intval($request->get_param('id'));
        $post = get_post($post_id);

        if (!$post || !in_array($post->post_type, array('viewing_record', 'musicalbum_viewing'))) {
            return new WP_Error('not_found', '记录不存在', array('status' => 404));
        }

        // 检查权限：只能查看自己的记录，除非是管理员
        if (!current_user_can('manage_options') && intval($post->post_author) !== $user_id) {
            return new WP_Error('forbidden', '无权查看此记录', array('status' => 403));
        }

        $ticket_image = get_field('ticket_image', $post_id);
        $ticket_image_data = null;
        if ($ticket_image) {
            if (is_array($ticket_image)) {
                $ticket_image_data = array(
                    'id' => isset($ticket_image['ID']) ? $ticket_image['ID'] : (isset($ticket_image['id']) ? $ticket_image['id'] : ''),
                    'url' => isset($ticket_image['url']) ? $ticket_image['url'] : ''
                );
            } else {
                // 如果是附件ID
                $image_url = wp_get_attachment_image_url($ticket_image, 'full');
                $ticket_image_data = array(
                    'id' => $ticket_image,
                    'url' => $image_url ? $image_url : ''
                );
            }
        }
        
        $result = array(
            'id' => $post_id,
            'title' => get_the_title($post_id),
            'category' => get_field('category', $post_id),
            'theater' => get_field('theater', $post_id),
            'cast' => get_field('cast', $post_id),
            'price' => get_field('price', $post_id),
            'view_date' => get_field('view_date', $post_id),
            'view_time_start' => get_field('view_time_start', $post_id),
            'view_time_end' => get_field('view_time_end', $post_id),
            'notes' => get_field('notes', $post_id),
            'ticket_image' => $ticket_image_data,
            'url' => get_permalink($post_id),
            'author' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
        );

        return rest_ensure_response($result);
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
            'post_type' => 'viewing_record',
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
        if (isset($params['view_time_start'])) {
            update_field('view_time_start', sanitize_text_field($params['view_time_start']), $post_id);
        }
        if (isset($params['view_time_end'])) {
            update_field('view_time_end', sanitize_text_field($params['view_time_end']), $post_id);
        }
        if (isset($params['notes'])) {
            update_field('notes', sanitize_textarea_field($params['notes']), $post_id);
        }
        if (isset($params['ticket_image_id']) && !empty($params['ticket_image_id'])) {
            update_field('ticket_image', intval($params['ticket_image_id']), $post_id);
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

        if (!$post || !in_array($post->post_type, array('viewing_record', 'musicalbum_viewing'))) {
            return new WP_Error('not_found', '记录不存在', array('status' => 404));
        }

        // 检查权限：只能编辑自己的记录，除非是管理员
        if (!current_user_can('manage_options') && intval($post->post_author) !== $user_id) {
            return new WP_Error('forbidden', '无权编辑此记录', array('status' => 403));
        }

        $params = $request->get_json_params();

        // 更新标题（即使为空也更新）
        if (array_key_exists('title', $params)) {
            $title = sanitize_text_field($params['title']);
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $title
            ));
        }

        // 更新ACF字段（使用 array_key_exists 确保即使值为空也能更新）
        if (array_key_exists('category', $params)) {
            update_field('category', sanitize_text_field($params['category']), $post_id);
        }
        if (array_key_exists('theater', $params)) {
            update_field('theater', sanitize_text_field($params['theater']), $post_id);
        }
        if (array_key_exists('cast', $params)) {
            update_field('cast', sanitize_text_field($params['cast']), $post_id);
        }
        if (array_key_exists('price', $params)) {
            update_field('price', sanitize_text_field($params['price']), $post_id);
        }
        if (array_key_exists('view_date', $params)) {
            update_field('view_date', sanitize_text_field($params['view_date']), $post_id);
        }
        if (array_key_exists('view_time_start', $params)) {
            update_field('view_time_start', sanitize_text_field($params['view_time_start']), $post_id);
        }
        if (array_key_exists('view_time_end', $params)) {
            update_field('view_time_end', sanitize_text_field($params['view_time_end']), $post_id);
        }
        if (array_key_exists('notes', $params)) {
            update_field('notes', sanitize_textarea_field($params['notes']), $post_id);
        }
        // 处理票面图片：优先使用新上传的图片ID，如果没有新图片则保留或删除
        if (isset($params['ticket_image_id'])) {
            if (!empty($params['ticket_image_id'])) {
                // 更新图片ID
                update_field('ticket_image', intval($params['ticket_image_id']), $post_id);
            } else {
                // 如果传递了空值，删除图片
                update_field('ticket_image', '', $post_id);
            }
        }

        return rest_ensure_response(array(
            'id' => $post_id,
            'message' => '记录更新成功',
            'updated' => true
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

        if (!$post || !in_array($post->post_type, array('viewing_record', 'musicalbum_viewing'))) {
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
     * 上传图片 REST API
     */
    public static function rest_upload_image($request) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return new WP_Error('unauthorized', '未授权', array('status' => 401));
        }
        
        // 检查文件上传
        if (empty($_FILES['file'])) {
            return new WP_Error('no_file', '未选择文件', array('status' => 400));
        }
        
        // 使用 WordPress 媒体库上传
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $file = $_FILES['file'];
        
        // 验证文件类型
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file['name']);
        if (!in_array($file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', '不支持的文件类型，请上传图片文件', array('status' => 400));
        }
        
        // 上传文件
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error'], array('status' => 500));
        }
        
        // 创建附件
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => $user_id
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }
        
        // 生成附件元数据
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // 返回图片信息
        $image_url = wp_get_attachment_image_url($attach_id, 'full');
        $image_data = array(
            'id' => $attach_id,
            'url' => $image_url,
            'thumbnail' => wp_get_attachment_image_url($attach_id, 'thumbnail')
        );
        
        return rest_ensure_response($image_data);
    }

    /**
     * 添加管理菜单：OCR API配置和数据迁移
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'OCR API 配置',
            'OCR API 配置',
            'manage_options',
            'viewing-ocr-config',
            array(__CLASS__, 'render_ocr_config_page')
        );
        
        add_submenu_page(
            'options-general.php',
            '数据迁移',
            '观演记录 - 数据迁移',
            'manage_options',
            'viewing-data-migration',
            array(__CLASS__, 'render_migration_page')
        );
    }
    
    /**
     * 执行数据迁移（简化版本，用于自动迁移，不返回结果）
     */
    public static function migrate_data_simple() {
        global $wpdb;
        
        // 1. 迁移自定义文章类型：musicalbum_viewing -> viewing_record
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
            'viewing_record',
            'musicalbum_viewing'
        ));
        
        // 2. 迁移选项名称
        $options_to_migrate = array(
            'musicalbum_ocr_provider' => 'viewing_ocr_provider',
            'musicalbum_baidu_api_key' => 'viewing_baidu_api_key',
            'musicalbum_baidu_secret_key' => 'viewing_baidu_secret_key',
            'musicalbum_aliyun_api_key' => 'viewing_aliyun_api_key',
            'musicalbum_aliyun_endpoint' => 'viewing_aliyun_endpoint',
            'musicalbum_aliyun_mode' => 'viewing_aliyun_mode'
        );
        
        foreach ($options_to_migrate as $old_key => $new_key) {
            $old_value = get_option($old_key, null);
            if ($old_value !== null) {
                // 如果新选项不存在，则迁移
                if (get_option($new_key, null) === null) {
                    update_option($new_key, $old_value);
                }
                // 迁移完成后，删除旧选项
                delete_option($old_key);
            }
        }
        
        // 3. 标记迁移完成
        update_option('viewing_records_migration_done', true);
    }
    
    /**
     * 执行数据迁移（完整版本，用于管理页面，返回详细结果）
     */
    public static function migrate_data() {
        global $wpdb;
        
        $results = array(
            'posts_migrated' => 0,
            'options_migrated' => 0,
            'errors' => array()
        );
        
        // 1. 迁移自定义文章类型：musicalbum_viewing -> viewing_record
        $posts_migrated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
            'viewing_record',
            'musicalbum_viewing'
        ));
        
        if ($posts_migrated === false) {
            $results['errors'][] = '迁移文章类型时出错：' . $wpdb->last_error;
        } else {
            $results['posts_migrated'] = $posts_migrated;
        }
        
        // 2. 迁移选项名称
        $options_to_migrate = array(
            'musicalbum_ocr_provider' => 'viewing_ocr_provider',
            'musicalbum_baidu_api_key' => 'viewing_baidu_api_key',
            'musicalbum_baidu_secret_key' => 'viewing_baidu_secret_key',
            'musicalbum_aliyun_api_key' => 'viewing_aliyun_api_key',
            'musicalbum_aliyun_endpoint' => 'viewing_aliyun_endpoint',
            'musicalbum_aliyun_mode' => 'viewing_aliyun_mode'
        );
        
        foreach ($options_to_migrate as $old_key => $new_key) {
            $old_value = get_option($old_key, null);
            if ($old_value !== null) {
                // 如果新选项不存在，则迁移；如果存在但为空，也迁移
                $new_value = get_option($new_key, null);
                if ($new_value === null || $new_value === '') {
                    update_option($new_key, $old_value);
                    $results['options_migrated']++;
                }
                // 迁移完成后，删除旧选项（无论是否成功迁移到新选项）
                delete_option($old_key);
            }
        }
        
        // 3. 迁移 post_meta 中的 ACF 字段引用（如果有的话）
        // 注意：ACF 字段通常通过字段名（name）存储，而不是 key，所以可能不需要迁移
        
        return $results;
    }
    
    /**
     * 渲染数据迁移页面
     */
    public static function render_migration_page() {
        $migration_done = false;
        $migration_results = null;
        
        // 处理迁移请求
        if (isset($_POST['viewing_migrate_data']) && check_admin_referer('viewing_migrate_data')) {
            $migration_results = self::migrate_data();
            $migration_done = true;
            update_option('viewing_records_migration_done', true);
        }
        
        // 检查是否有旧数据
        $old_posts_count = 0;
        $old_posts = get_posts(array(
            'post_type' => 'musicalbum_viewing',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        if ($old_posts) {
            $old_posts_count = count($old_posts);
        }
        
        $old_options = array();
        $old_option_keys = array(
            'musicalbum_ocr_provider',
            'musicalbum_baidu_api_key',
            'musicalbum_baidu_secret_key',
            'musicalbum_aliyun_api_key',
            'musicalbum_aliyun_endpoint',
            'musicalbum_aliyun_mode'
        );
        foreach ($old_option_keys as $key) {
            $value = get_option($key, null);
            if ($value !== null) {
                $old_options[$key] = $value;
            }
        }
        
        ?>
        <div class="wrap">
            <h1>数据迁移</h1>
            
            <?php if ($migration_done && $migration_results): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✓ 数据迁移完成！</strong></p>
                    <ul>
                        <li>迁移了 <strong><?php echo esc_html($migration_results['posts_migrated']); ?></strong> 条观演记录</li>
                        <li>迁移了 <strong><?php echo esc_html($migration_results['options_migrated']); ?></strong> 个配置选项</li>
                    </ul>
                    <?php if (!empty($migration_results['errors'])): ?>
                        <p><strong>错误：</strong></p>
                        <ul>
                            <?php foreach ($migration_results['errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>迁移说明</h2>
                <p>此工具将帮助您将旧的数据迁移到新的命名规范：</p>
                <ul>
                    <li><strong>自定义文章类型：</strong>将 <code>musicalbum_viewing</code> 迁移为 <code>viewing_record</code></li>
                    <li><strong>配置选项：</strong>将 <code>musicalbum_*</code> 选项迁移为 <code>viewing_*</code> 选项</li>
                </ul>
                <p><strong>注意：</strong>迁移操作会直接修改数据库，建议在执行前备份数据库。</p>
            </div>
            
            <div class="card">
                <h2>待迁移数据统计</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>数据类型</th>
                            <th>数量</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>旧观演记录 (musicalbum_viewing)</td>
                            <td><?php echo esc_html($old_posts_count); ?></td>
                            <td><?php echo $old_posts_count > 0 ? '<span style="color:orange;">待迁移</span>' : '<span style="color:green;">无数据</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>旧配置选项 (musicalbum_*)</td>
                            <td><?php echo esc_html(count($old_options)); ?></td>
                            <td><?php echo count($old_options) > 0 ? '<span style="color:orange;">待迁移</span>' : '<span style="color:green;">无数据</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if ($old_posts_count > 0 || count($old_options) > 0): ?>
                <div class="card">
                    <h2>执行迁移</h2>
                    <p>检测到有待迁移的数据。点击下方按钮开始迁移：</p>
                    <form method="post" action="">
                        <?php wp_nonce_field('viewing_migrate_data'); ?>
                        <p>
                            <button type="submit" name="viewing_migrate_data" class="button button-primary" 
                                    onclick="return confirm('确定要执行数据迁移吗？此操作将修改数据库，建议先备份。');">
                                开始迁移数据
                            </button>
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p>✓ 没有需要迁移的数据，所有数据已使用新的命名规范。</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($old_options)): ?>
                <div class="card">
                    <h2>旧配置选项详情</h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>选项名称</th>
                                <th>值（部分显示）</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($old_options as $key => $value): ?>
                                <tr>
                                    <td><code><?php echo esc_html($key); ?></code></td>
                                    <td>
                                        <?php 
                                        if (is_string($value) && strlen($value) > 50) {
                                            echo esc_html(substr($value, 0, 50)) . '...';
                                        } else {
                                            echo esc_html($value);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * 渲染OCR配置页面
     */
    public static function render_ocr_config_page() {
        // 处理表单提交
        if (isset($_POST['viewing_ocr_save']) && check_admin_referer('viewing_ocr_config')) {
            $api_key = sanitize_text_field($_POST['baidu_api_key']);
            $secret_key = sanitize_text_field($_POST['baidu_secret_key']);
            
            // 保存到新选项名称
            update_option('viewing_baidu_api_key', $api_key);
            update_option('viewing_baidu_secret_key', $secret_key);
            
            // 同时更新旧选项名称（向后兼容）
            update_option('musicalbum_baidu_api_key', $api_key);
            update_option('musicalbum_baidu_secret_key', $secret_key);
            
            echo '<div class="notice notice-success is-dismissible"><p>✓ OCR API配置已保存！</p></div>';
        }
        
        // 获取当前配置（向后兼容：优先读取新选项，如果不存在则读取旧选项）
        $current_api_key = get_option('viewing_baidu_api_key', '') ?: get_option('musicalbum_baidu_api_key', '');
        $current_secret_key = get_option('viewing_baidu_secret_key', '') ?: get_option('musicalbum_baidu_secret_key', '');
        
        ?>
        <div class="wrap">
            <h1>OCR API 配置</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('viewing_ocr_config'); ?>
                
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
    
    /**
     * 在单篇文章页面显示观演记录详情
     */
    public static function display_viewing_record_details($content) {
        // 只在单篇文章页面且是 viewing_record 或 musicalbum_viewing 类型时显示
        if (!is_singular() || !in_array(get_post_type(), array('viewing_record', 'musicalbum_viewing'))) {
            return $content;
        }
        
        $post_id = get_the_ID();
        
        // 获取所有字段
        $category = get_field('category', $post_id);
        $theater = get_field('theater', $post_id);
        $cast = get_field('cast', $post_id);
        $price = get_field('price', $post_id);
        $view_date = get_field('view_date', $post_id);
        $view_time_start = get_field('view_time_start', $post_id);
        $view_time_end = get_field('view_time_end', $post_id);
        $notes = get_field('notes', $post_id);
        $ticket_image = get_field('ticket_image', $post_id);
        
        // 如果没有字段数据，直接返回原内容
        if (!$category && !$theater && !$cast && !$price && !$view_date && !$notes && !$ticket_image) {
            return $content;
        }
        
        // 检查是否有编辑权限（记录所有者或管理员）
        $current_user_id = get_current_user_id();
        $post_author_id = get_post_field('post_author', $post_id);
        $can_edit = ($current_user_id && ($current_user_id == $post_author_id || current_user_can('manage_options')));
        
        // 构建详情HTML
        $details_html = '<div class="viewing-record-details" style="margin-top: 2rem; padding: 1.5rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">';
        $details_html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">';
        $details_html .= '<h2 style="margin: 0; font-size: 1.5rem; color: #111827;">观演记录详情</h2>';
        
        // 添加编辑按钮（如果有权限）
        if ($can_edit) {
            $details_html .= '<button type="button" class="musicalbum-btn musicalbum-btn-primary musicalbum-btn-edit" data-id="' . esc_attr($post_id) . '" style="padding: 0.5rem 1rem; font-size: 0.875rem;">编辑记录</button>';
        }
        
        $details_html .= '</div>';
        $details_html .= '<div class="viewing-record-meta" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">';
        
        if ($category) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">类别</strong>';
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . esc_html($category) . '</span>';
            $details_html .= '</div>';
        }
        
        if ($theater) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">剧院</strong>';
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . esc_html($theater) . '</span>';
            $details_html .= '</div>';
        }
        
        if ($cast) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">卡司</strong>';
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . esc_html($cast) . '</span>';
            $details_html .= '</div>';
        }
        
        if ($price) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">票价</strong>';
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . esc_html($price) . '</span>';
            $details_html .= '</div>';
        }
        
        if ($view_date) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">观演日期</strong>';
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . esc_html($view_date) . '</span>';
            $details_html .= '</div>';
        }
        
        if ($view_time_start || $view_time_end) {
            $details_html .= '<div class="viewing-meta-item">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.25rem; color: #6b7280; font-size: 0.875rem;">观演时间</strong>';
            $time_str = '';
            if ($view_time_start && $view_time_end) {
                $time_str = esc_html($view_time_start) . ' - ' . esc_html($view_time_end);
            } elseif ($view_time_start) {
                $time_str = esc_html($view_time_start) . ' 开始';
            } elseif ($view_time_end) {
                $time_str = esc_html($view_time_end) . ' 结束';
            }
            $details_html .= '<span style="color: #111827; font-size: 1rem;">' . $time_str . '</span>';
            $details_html .= '</div>';
        }
        
        $details_html .= '</div>'; // 结束 viewing-record-meta
        
        // 票面图片
        if ($ticket_image) {
            $image_url = is_array($ticket_image) ? $ticket_image['url'] : $ticket_image;
            $image_alt = is_array($ticket_image) && isset($ticket_image['alt']) ? $ticket_image['alt'] : '票面图片';
            $details_html .= '<div class="viewing-ticket-image" style="margin-bottom: 1.5rem;">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.5rem; color: #6b7280; font-size: 0.875rem;">票面图片</strong>';
            $details_html .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" style="max-width: 100%; height: auto; border-radius: 4px; border: 1px solid #e5e7eb;" />';
            $details_html .= '</div>';
        }
        
        // 备注
        if ($notes) {
            $details_html .= '<div class="viewing-notes" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">';
            $details_html .= '<strong style="display: block; margin-bottom: 0.5rem; color: #6b7280; font-size: 0.875rem;">备注</strong>';
            $details_html .= '<div style="color: #111827; line-height: 1.6; white-space: pre-wrap;">' . wp_kses_post(nl2br(esc_html($notes))) . '</div>';
            $details_html .= '</div>';
        }
        
        $details_html .= '</div>'; // 结束 viewing-record-details
        
        // 如果有编辑权限，确保模态框存在（如果页面中没有观演管理模块的模态框）
        if ($can_edit) {
            // 检查页面中是否已经有模态框（来自观演管理模块）
            if (!has_shortcode(get_post()->post_content, 'viewing_manager') && 
                !has_shortcode(get_post()->post_content, 'musicalbum_viewing_manager')) {
                // 如果没有模态框，添加一个简化版的编辑表单模态框
                $details_html .= self::get_edit_modal_html();
            }
        }
        
        // 将详情添加到内容后面
        return $content . $details_html;
    }
    
    /**
     * 获取编辑表单模态框的HTML（用于详情页）
     */
    private static function get_edit_modal_html() {
        ob_start();
        ?>
        <div id="musicalbum-form-modal" class="musicalbum-modal" style="display: none;">
            <div class="musicalbum-modal-content musicalbum-form-modal-content">
                <span class="musicalbum-modal-close">&times;</span>
                <h3 class="musicalbum-modal-title" id="musicalbum-form-title">编辑观演记录</h3>
                <div class="musicalbum-modal-body">
                    <div class="musicalbum-form-tabs">
                        <button type="button" class="musicalbum-tab-btn active" data-tab="manual">手动录入</button>
                    </div>
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
                                <div class="musicalbum-calendar-input-wrapper">
                                    <input type="text" id="musicalbum-form-date" name="view_date" class="musicalbum-calendar-date-input" placeholder="YYYY-MM-DD或点击选择" autocomplete="off">
                                    <input type="date" id="musicalbum-form-date-picker" class="musicalbum-calendar-date-picker" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">
                                    <button type="button" class="musicalbum-calendar-icon-btn" title="选择日期">📅</button>
                                </div>
                            </div>
                            <div class="musicalbum-form-group">
                                <label>观演时间</label>
                                <div style="display:flex;gap:1rem;align-items:flex-end;">
                                    <div style="flex:1;">
                                        <label for="musicalbum-form-time-start" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">开始时间</label>
                                        <input type="time" id="musicalbum-form-time-start" name="view_time_start" placeholder="例如：19:30">
                                    </div>
                                    <div style="flex:1;">
                                        <label for="musicalbum-form-time-end" style="display:block;margin-bottom:0.25rem;font-size:0.875rem;color:#374151;">结束时间</label>
                                        <input type="time" id="musicalbum-form-time-end" name="view_time_end" placeholder="例如：22:00">
                                    </div>
                                </div>
                                <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，填写观演的开始和结束时间</p>
                            </div>
                            <div class="musicalbum-form-group">
                                <label for="musicalbum-form-notes">备注</label>
                                <textarea id="musicalbum-form-notes" name="notes" rows="4"></textarea>
                            </div>
                            <div class="musicalbum-form-group">
                                <label for="musicalbum-form-ticket-image">票面图片</label>
                                <input type="file" id="musicalbum-form-ticket-image" name="ticket_image" accept="image/*">
                                <div id="musicalbum-form-ticket-preview" style="margin-top: 0.5rem;"></div>
                                <input type="hidden" id="musicalbum-form-ticket-image-id" name="ticket_image_id" value="">
                                <p class="description" style="margin-top:0.25rem;font-size:0.8125rem;color:#6b7280;">可选，上传票面图片</p>
                            </div>
                            <div class="musicalbum-form-actions">
                                <button type="button" class="musicalbum-btn musicalbum-btn-cancel" id="musicalbum-form-cancel">取消</button>
                                <button type="submit" class="musicalbum-btn musicalbum-btn-primary">保存</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
}

// 启动插件
Viewing_Records::init();
