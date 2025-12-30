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
require_once plugin_dir_path( __FILE__ ) . 'recommendation.php';
require_once plugin_dir_path( __FILE__ ) . 'feedback.php';
require_once plugin_dir_path( __FILE__ ) . 'page-recommend.php';

/**
 * 注册短代码 [musical_recommend]
 */
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );
