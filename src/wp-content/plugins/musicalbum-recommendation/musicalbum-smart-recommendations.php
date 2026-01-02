<?php
/**
 * Plugin Name: Musicalbum Smart Recommendations
 * Description: 基于观演历史、演员关联与热门演出的音乐剧推荐插件
 * Version: 1.0.0
 * Author: Ji Peng
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 引入核心文件
 */
require_once plugin_dir_path( __FILE__ ) . 'saoju-api.php';

// 推荐算法（必须最先）
require_once plugin_dir_path( __FILE__ ) . 'recommendation.php';

// 不感兴趣 / 反馈
require_once plugin_dir_path( __FILE__ ) . 'feedback.php';

// 页面 + shortcode
require_once plugin_dir_path( __FILE__ ) . 'page-recommend.php';

/**
 * 注册短代码 [musical_recommend]
 */
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );

/**
 * 加载推荐插件的前端样式
 */
add_action( 'wp_enqueue_scripts', 'msr_enqueue_styles' );
function msr_enqueue_styles() {

    // 后台页面不用管
    if ( is_admin() ) {
        return;
    }

    wp_enqueue_style(
        'msr-style',
        plugin_dir_url( __FILE__ ) . 'assets/recommendations.css',
        [],
        '1.0.0'
    );
}
