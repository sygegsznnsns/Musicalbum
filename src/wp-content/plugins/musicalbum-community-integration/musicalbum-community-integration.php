<?php
/*
Plugin Name: Musicalbum Community Integration
Description: 社区交流与信息共享功能模块，集成 bbPress 和 BuddyPress，与 musicalbum 观演记录系统联动
Version: 1.0.0
Author: Chen Pan
*/

defined('ABSPATH') || exit;

/**
 * Musicalbum 社区集成插件主类
 * 
 * 核心功能：
 * - 检测并集成 bbPress 和 BuddyPress
 * - 与 musicalbum 观演记录系统联动
 * - 提供资源分享和知识库功能
 * - 统一的短码接口
 */
final class Musicalbum_Community_Integration {
    
    private static $instance = null;
    private $plugin_url;
    private $plugin_path;
    private $plugin_version = '1.0.0';
    
    // 依赖插件检测结果
    private $bbpress_active = false;
    private $buddypress_active = false;
    
    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // 检测依赖插件
        add_action('plugins_loaded', array($this, 'check_dependencies'), 5);
        
        // 初始化插件
        add_action('plugins_loaded', array($this, 'init'), 10);
        
        // 注册激活和停用钩子
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * 检测依赖插件
     */
    public function check_dependencies() {
        // 检测 bbPress
        $this->bbpress_active = class_exists('bbPress');
        
        // 检测 BuddyPress
        $this->buddypress_active = function_exists('buddypress');
        
        // 如果依赖插件未安装，显示管理员通知
        if (is_admin() && (!$this->bbpress_active || !$this->buddypress_active)) {
            add_action('admin_notices', array($this, 'dependency_notice'));
        }
    }
    
    /**
     * 依赖插件缺失通知
     */
    public function dependency_notice() {
        $missing = array();
        
        if (!$this->bbpress_active) {
            $missing[] = 'bbPress';
        }
        
        if (!$this->buddypress_active) {
            $missing[] = 'BuddyPress';
        }
        
        if (!empty($missing)) {
            $plugins = implode(' 和 ', $missing);
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Musicalbum Community Integration</strong> 需要以下插件：' . esc_html($plugins) . '。';
            echo '请先安装并激活这些插件。';
            echo '</p></div>';
        }
    }
    
    /**
     * 初始化插件
     */
    public function init() {
        // 加载必要的类文件（带错误处理）
        try {
            $this->load_includes();
        } catch (Exception $e) {
            // 如果加载失败，记录错误但不中断其他功能
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Musicalbum Community Integration: Failed to load includes - ' . $e->getMessage());
            }
            return;
        }
        
        // 入队前端资源（总是执行，因为资源分享和知识库不依赖其他插件）
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // 入队后台资源
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // 注册短码（总是执行）
        add_action('init', array($this, 'register_shortcodes'));
        
        // 注册 REST API（总是执行）
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // 添加后台菜单（总是执行）
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 注册设置（总是执行）
        add_action('admin_init', array($this, 'register_settings'));
        
        // 初始化集成类（只在依赖激活时执行）
        if ($this->bbpress_active && class_exists('Musicalbum_BBPress_Integration')) {
            try {
                Musicalbum_BBPress_Integration::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: bbPress integration failed - ' . $e->getMessage());
                }
            }
        }
        
        if ($this->buddypress_active && class_exists('Musicalbum_BuddyPress_Integration')) {
            try {
                Musicalbum_BuddyPress_Integration::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: BuddyPress integration failed - ' . $e->getMessage());
                }
            }
        }
        
        // 初始化其他功能类（总是执行，带错误处理）
        if (class_exists('Musicalbum_Viewing_Integration')) {
            try {
                Musicalbum_Viewing_Integration::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: Viewing integration failed - ' . $e->getMessage());
                }
            }
        }
        
        if (class_exists('Musicalbum_Resource_Sharing')) {
            try {
                Musicalbum_Resource_Sharing::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: Resource sharing failed - ' . $e->getMessage());
                }
            }
        }
        
        if (class_exists('Musicalbum_Knowledge_Base')) {
            try {
                Musicalbum_Knowledge_Base::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: Knowledge base failed - ' . $e->getMessage());
                }
            }
        }
        
        if (class_exists('Musicalbum_Community_Customizations')) {
            try {
                Musicalbum_Community_Customizations::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: Customizations failed - ' . $e->getMessage());
                }
            }
        }
        
        if (class_exists('Musicalbum_Recommendation_Integration')) {
            try {
                Musicalbum_Recommendation_Integration::init();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Musicalbum Community Integration: Recommendation integration failed - ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * 加载必要的类文件
     */
    private function load_includes() {
        $includes_dir = $this->plugin_path . 'includes/';
        
        // 检查文件是否存在再加载
        $files = array(
            'class-bbpress-integration.php',
            'class-buddypress-integration.php',
            'class-viewing-integration.php',
            'class-resource-sharing.php',
            'class-knowledge-base.php',
            'class-customizations.php',
            'class-recommendation-integration.php',
        );
        
        foreach ($files as $file) {
            $file_path = $includes_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * 入队前端资源
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'musicalbum-community-integration',
            $this->plugin_url . 'assets/community-integration.css',
            array(),
            $this->plugin_version
        );
        
        wp_enqueue_script(
            'musicalbum-community-integration',
            $this->plugin_url . 'assets/community-integration.js',
            array('jquery'),
            $this->plugin_version,
            true
        );
        
        // 本地化脚本
        wp_localize_script('musicalbum-community-integration', 'MusicalbumCommunity', array(
            'rest_url' => rest_url('musicalbum/v1/community/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }
    
    /**
     * 入队后台资源
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'musicalbum-community') === false) {
            return;
        }
        
        wp_enqueue_style(
            'musicalbum-community-integration-admin',
            $this->plugin_url . 'assets/admin.css',
            array(),
            $this->plugin_version
        );
    }
    
    /**
     * 注册短码
     */
    public function register_shortcodes() {
        add_shortcode('musicalbum_forum', array($this, 'shortcode_forum'));
        add_shortcode('musicalbum_user_activity', array($this, 'shortcode_user_activity'));
        add_shortcode('musicalbum_resource_library', array($this, 'shortcode_resource_library'));
        add_shortcode('musicalbum_knowledge_base', array($this, 'shortcode_knowledge_base'));
    }
    
    /**
     * 论坛短码
     */
    public function shortcode_forum($atts) {
        $atts = shortcode_atts(array(
            'forum_id' => 0,
            'category' => '',
            'limit' => 10,
        ), $atts);
        
        if (!$this->bbpress_active) {
            return '<p>bbPress 插件未激活，无法显示论坛内容。</p>';
        }
        
        return Musicalbum_BBPress_Integration::render_forum_shortcode($atts);
    }
    
    /**
     * 用户活动短码
     */
    public function shortcode_user_activity($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'limit' => 10,
        ), $atts);
        
        if (!$this->buddypress_active) {
            return '<p>BuddyPress 插件未激活，无法显示用户活动。</p>';
        }
        
        return Musicalbum_BuddyPress_Integration::render_activity_shortcode($atts);
    }
    
    /**
     * 资源库短码
     */
    public function shortcode_resource_library($atts) {
        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
        ), $atts);
        
        return Musicalbum_Resource_Sharing::render_library_shortcode($atts);
    }
    
    /**
     * 知识库短码
     */
    public function shortcode_knowledge_base($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
        ), $atts);
        
        return Musicalbum_Knowledge_Base::render_knowledge_base_shortcode($atts);
    }
    
    /**
     * 注册 REST API 路由
     */
    public function register_rest_routes() {
        register_rest_route('musicalbum/v1', '/community/share-viewing', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_share_viewing'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ));
        
        register_rest_route('musicalbum/v1', '/community/user-stats/(?P<user_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_user_stats'),
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ));
        
        register_rest_route('musicalbum/v1', '/community/resources', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_resources'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * REST API: 分享观演记录
     */
    public function rest_share_viewing($request) {
        return Musicalbum_Viewing_Integration::share_viewing($request);
    }
    
    /**
     * REST API: 获取用户统计
     */
    public function rest_user_stats($request) {
        $user_id = intval($request['user_id']);
        return Musicalbum_BuddyPress_Integration::get_user_stats($user_id);
    }
    
    /**
     * REST API: 获取资源列表
     */
    public function rest_get_resources($request) {
        return Musicalbum_Resource_Sharing::get_resources($request);
    }
    
    /**
     * 添加后台菜单
     */
    public function add_admin_menu() {
        add_options_page(
            'Musicalbum 社区设置',
            'Musicalbum 社区',
            'manage_options',
            'musicalbum-community',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting('musicalbum_community_options', 'musicalbum_community_enable_forum');
        register_setting('musicalbum_community_options', 'musicalbum_community_enable_sharing');
        register_setting('musicalbum_community_options', 'musicalbum_community_enable_knowledge');
        register_setting('musicalbum_community_options', 'musicalbum_community_viewing_forum_id');
        
        add_settings_section(
            'musicalbum_community_section',
            '社区功能设置',
            array($this, 'render_settings_section'),
            'musicalbum_community_options'
        );
        
        add_settings_field(
            'musicalbum_community_enable_forum',
            '启用论坛集成',
            array($this, 'render_enable_forum_field'),
            'musicalbum_community_options',
            'musicalbum_community_section'
        );
        
        add_settings_field(
            'musicalbum_community_enable_sharing',
            '启用资源分享',
            array($this, 'render_enable_sharing_field'),
            'musicalbum_community_options',
            'musicalbum_community_section'
        );
        
        add_settings_field(
            'musicalbum_community_enable_knowledge',
            '启用知识库',
            array($this, 'render_enable_knowledge_field'),
            'musicalbum_community_options',
            'musicalbum_community_section'
        );
        
        add_settings_field(
            'musicalbum_community_viewing_forum_id',
            '观演记录论坛ID',
            array($this, 'render_viewing_forum_field'),
            'musicalbum_community_options',
            'musicalbum_community_section'
        );
        
        add_settings_field(
            'musicalbum_community_create_forum',
            '创建/重置论坛',
            array($this, 'render_create_forum_field'),
            'musicalbum_community_options',
            'musicalbum_community_section'
        );
    }
    
    /**
     * 渲染设置部分
     */
    public function render_settings_section() {
        echo '<p>配置 Musicalbum 社区集成功能。请确保已安装并激活 bbPress 和 BuddyPress 插件。</p>';
        
        // 显示依赖插件状态
        echo '<div class="musicalbum-dependency-status">';
        echo '<h3>依赖插件状态</h3>';
        echo '<ul>';
        echo '<li>bbPress: ' . ($this->bbpress_active ? '<span style="color: green;">✓ 已激活</span>' : '<span style="color: red;">✗ 未激活</span>') . '</li>';
        echo '<li>BuddyPress: ' . ($this->buddypress_active ? '<span style="color: green;">✓ 已激活</span>' : '<span style="color: red;">✗ 未激活</span>') . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * 渲染启用论坛字段
     */
    public function render_enable_forum_field() {
        $value = get_option('musicalbum_community_enable_forum', '1');
        echo '<input type="checkbox" name="musicalbum_community_enable_forum" value="1" ' . checked('1', $value, false) . ' />';
        echo '<p class="description">启用与 bbPress 论坛的集成功能</p>';
    }
    
    /**
     * 渲染启用分享字段
     */
    public function render_enable_sharing_field() {
        $value = get_option('musicalbum_community_enable_sharing', '1');
        echo '<input type="checkbox" name="musicalbum_community_enable_sharing" value="1" ' . checked('1', $value, false) . ' />';
        echo '<p class="description">启用资源分享功能</p>';
    }
    
    /**
     * 渲染启用知识库字段
     */
    public function render_enable_knowledge_field() {
        $value = get_option('musicalbum_community_enable_knowledge', '1');
        echo '<input type="checkbox" name="musicalbum_community_enable_knowledge" value="1" ' . checked('1', $value, false) . ' />';
        echo '<p class="description">启用知识库功能</p>';
    }
    
    /**
     * 渲染观演记录论坛字段
     */
    public function render_viewing_forum_field() {
        $value = get_option('musicalbum_community_viewing_forum_id', '');
        $auto_created = get_option('musicalbum_viewing_forum_id', 0);
        
        echo '<input type="number" name="musicalbum_community_viewing_forum_id" value="' . esc_attr($value ?: $auto_created) . '" class="regular-text" />';
        echo '<p class="description">用于分享观演记录的论坛ID（bbPress 论坛ID）。如果留空，将使用自动创建的"观演交流"论坛（ID: ' . esc_html($auto_created ?: '未创建') . '）</p>';
        
        if ($auto_created) {
            $forum = get_post($auto_created);
            if ($forum) {
                echo '<p><a href="' . esc_url(get_permalink($auto_created)) . '" target="_blank">查看论坛</a> | ';
                echo '<a href="' . esc_url(admin_url('post.php?post=' . $auto_created . '&action=edit')) . '" target="_blank">编辑论坛</a></p>';
            }
        }
    }
    
    /**
     * 渲染创建论坛字段
     */
    public function render_create_forum_field() {
        // 检查是否有创建请求
        if (isset($_POST['musicalbum_create_forum']) && wp_verify_nonce($_POST['_wpnonce'], 'musicalbum_community_options-options')) {
            if (class_exists('Musicalbum_BBPress_Integration')) {
                $forum_id = Musicalbum_BBPress_Integration::force_create_forum();
                if ($forum_id) {
                    echo '<div class="notice notice-success inline"><p>论坛创建成功！论坛ID: ' . esc_html($forum_id) . '</p></div>';
                } else {
                    echo '<div class="notice notice-error inline"><p>论坛创建失败。请确保 bbPress 插件已激活。</p></div>';
                }
            }
        }
        
        echo '<button type="submit" name="musicalbum_create_forum" class="button button-secondary">创建/重置"观演交流"论坛</button>';
        echo '<p class="description">如果论坛不存在或出现问题，点击此按钮重新创建论坛。</p>';
    }
    
    /**
     * 渲染设置页面
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>Musicalbum 社区设置</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('musicalbum_community_options');
                do_settings_sections('musicalbum_community_options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * 插件激活时的处理
     */
    public function activate() {
        // 创建必要的数据库表
        $this->create_tables();
        
        // 设置默认选项
        add_option('musicalbum_community_enable_forum', '1');
        add_option('musicalbum_community_enable_sharing', '1');
        add_option('musicalbum_community_enable_knowledge', '1');
        
        // 刷新重写规则
        flush_rewrite_rules();
    }
    
    /**
     * 插件停用时的处理
     */
    public function deactivate() {
        // 刷新重写规则
        flush_rewrite_rules();
    }
    
    /**
     * 创建数据库表
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 资源分享关联表
        $table_resources = $wpdb->prefix . 'musicalbum_resources';
        $sql_resources = "CREATE TABLE IF NOT EXISTS $table_resources (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            resource_type varchar(50) NOT NULL,
            resource_url text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_resources);
    }
    
    /**
     * 获取插件路径
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }
    
    /**
     * 获取插件URL
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }
    
    /**
     * 检查 bbPress 是否激活
     */
    public function is_bbpress_active() {
        return $this->bbpress_active;
    }
    
    /**
     * 检查 BuddyPress 是否激活
     */
    public function is_buddypress_active() {
        return $this->buddypress_active;
    }
}

// 初始化插件
Musicalbum_Community_Integration::get_instance();

