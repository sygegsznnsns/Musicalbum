<?php
/**
 * Plugin Name: Musicalbum Smart Recommendations
 * Description: A WordPress plugin for personalized content recommendations based on user behavior (views, comments, favorites).
 * Version: 1.0.0
 * Author: Ji Peng
 */

// 防止直接访问
if ( ! defined( 'ABSPATH' ) ) {
    exit; // 禁止直接访问
}

// 插件的主类
class Musicalbum_Smart_Recommendations {

    // 构造函数，初始化插件
    public function __construct() {
        // 加载必要的文件
        $this->load_includes();
    }

    // 加载插件包含的所有文件
    private function load_includes() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-user-behavior-tracker.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-recommendation-engine.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-community-adapter.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-shortcodes.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-admin-settings.php';

        // 实例化每个类
        new Musicalbum_User_Behavior_Tracker();
        new Musicalbum_Recommendation_Engine();
        new Musicalbum_Community_Adapter();
        new Musicalbum_Shortcodes();
        new Musicalbum_Admin_Settings();
    }
}

// 实例化主插件类
$musicalbum_smart_recommendations = new Musicalbum_Smart_Recommendations();
