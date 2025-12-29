<?php
/*
Plugin Name: Musicalbum Smart Recommendations
Description: 基于用户行为、内容标签与社区活跃度的智能内容推荐系统
Version: 1.0.0
Author: Chen Pan
*/

defined('ABSPATH') || exit;

final class Musicalbum_Smart_Recommendations {

    private static $instance = null;

    private $plugin_path;
    private $plugin_url;
    private $version = '1.0.0';

    // 可选依赖
    private $community_active = false;
    private $buddypress_active = false;
    private $bbpress_active = false;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url  = plugin_dir_url(__FILE__);

        add_action('plugins_loaded', [$this, 'check_dependencies'], 5);
        add_action('plugins_loaded', [$this, 'init'], 10);

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function check_dependencies() {
        $this->community_active  = class_exists('Musicalbum_Community_Integration');
        $this->buddypress_active = function_exists('buddypress');
        $this->bbpress_active    = class_exists('bbPress');

        if (is_admin() && !$this->community_active) {
            add_action('admin_notices', [$this, 'dependency_notice']);
        }
    }

    public function dependency_notice() {
        echo '<div class="notice notice-warning"><p>';
        echo '<strong>Musicalbum Smart Recommendations</strong>：未检测到 Musicalbum Community Integration，社区活跃度权重将被忽略。';
        echo '</p></div>';
    }

    public function init() {
        $this->load_includes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('rest_api_init', [$this, 'register_rest_api']);

        if (class_exists('Musicalbum_User_Behavior_Tracker')) {
            Musicalbum_User_Behavior_Tracker::init();
        }

        if (class_exists('Musicalbum_Recommendation_Engine')) {
            Musicalbum_Recommendation_Engine::init();
        }

        if ($this->community_active && class_exists('Musicalbum_Community_Adapter')) {
            Musicalbum_Community_Adapter::init();
        }

        if (class_exists('Musicalbum_Admin_Settings')) {
            Musicalbum_Admin_Settings::init();
        }
    }

    private function load_includes() {
        $includes = [
            'class-user-behavior-tracker.php',
            'class-recommendation-engine.php',
            'class-community-adapter.php',
            'class-shortcodes.php',
            'class-admin-settings.php',
        ];

        foreach ($includes as $file) {
            $path = $this->plugin_path . 'includes/' . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'musicalbum-recommendations',
            $this->plugin_url . 'assets/recommendations.css',
            [],
            $this->version
        );
    }

    public function register_shortcodes() {
        add_shortcode(
            'musicalbum_smart_recommendations',
            ['Musicalbum_Shortcodes', 'render_recommendations']
        );
    }

    public function register_rest_api() {
        register_rest_route('musicalbum/v1', '/recommendations', [
            'methods'  => 'GET',
            'callback' => ['Musicalbum_Recommendation_Engine', 'rest_get_recommendations'],
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ]);
    }

    public function activate() {
        add_option('musicalbum_recommendation_weight_tag', 5);
        add_option('musicalbum_recommendation_weight_behavior', 3);
        add_option('musicalbum_recommendation_weight_community', 2);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

Musicalbum_Smart_Recommendations::get_instance();
