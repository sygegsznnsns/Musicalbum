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
 * 加载推荐插件的前端样式和脚本
 */
add_action( 'wp_enqueue_scripts', 'msr_enqueue_scripts' );
function msr_enqueue_scripts() {

    // 后台页面不用管
    if ( is_admin() ) {
        return;
    }

    // 加载样式
    wp_enqueue_style(
        'msr-style',
        plugin_dir_url( __FILE__ ) . 'assets/recommendations.css',
        [],
        '1.0.0'
    );
    
    // 加载JavaScript
    wp_enqueue_script(
        'msr-recommendations',
        plugin_dir_url( __FILE__ ) . 'assets/recommendations.js',
        ['jquery'],
        '1.0.0',
        true
    );
    
    // 传递AJAX URL到前端
    wp_localize_script('msr-recommendations', 'ajaxurl', admin_url('admin-ajax.php'));
}

/**
 * 注册设置项：DeepSeek API Key
 */
add_action( 'admin_init', function () {
    register_setting(
        'musicalbum_ai_settings',
        'musicalbum_deepseek_api_key'
    );
} );
add_action( 'admin_menu', function () {
    add_options_page(
        'Musicalbum AI 设置',
        'Musicalbum AI',
        'manage_options',
        'musicalbum-ai-settings',
        'musicalbum_ai_settings_page'
    );
} );
function musicalbum_ai_settings_page() {
    ?>
    <div class="wrap">
        <h1>Musicalbum · AI 设置</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'musicalbum_ai_settings' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">DeepSeek API Key</th>
                    <td>
                        <input
                            type="text"
                            name="musicalbum_deepseek_api_key"
                            value="<?php echo esc_attr( get_option( 'musicalbum_deepseek_api_key' ) ); ?>"
                            class="regular-text"
                        />
                        <p class="description">
                            用于 AI 音乐剧推荐（DeepSeek）
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

