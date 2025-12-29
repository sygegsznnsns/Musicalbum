<?php
/**
 * Musicalbum Smart Recommendations - Admin Settings
 *
 * 功能说明：
 * - 提供智能推荐系统的后台配置界面
 * - 管理推荐算法权重
 * - 控制推荐系统启用状态
 *
 * 面向对象：
 * - 管理员 / 系统维护者
 */

defined('ABSPATH') || exit;

class Musicalbum_Admin_Settings {

    public static function init() {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function add_menu() {
        add_options_page(
            'Musicalbum 智能推荐设置',
            'Musicalbum 推荐',
            'manage_options',
            'musicalbum-recommendations',
            [self::class, 'render_page']
        );
    }

    public static function register_settings() {

        register_setting('musicalbum_recommendation_options', 'musicalbum_recommendation_weight_tag');
        register_setting('musicalbum_recommendation_options', 'musicalbum_recommendation_weight_behavior');
        register_setting('musicalbum_recommendation_options', 'musicalbum_recommendation_weight_community');

        add_settings_section(
            'musicalbum_recommendation_main',
            '推荐算法参数',
            '__return_false',
            'musicalbum_recommendation_options'
        );

        self::add_field(
            'musicalbum_recommendation_weight_tag',
            '内容标签权重',
            '标签匹配在推荐中的影响程度'
        );

        self::add_field(
            'musicalbum_recommendation_weight_behavior',
            '用户行为权重',
            '浏览 / 收藏 / 发帖等历史行为影响'
        );

        self::add_field(
            'musicalbum_recommendation_weight_community',
            '社区活跃度权重',
            '论坛 / 社区活跃度影响'
        );
    }

    private static function add_field($option, $label, $desc) {
        add_settings_field(
            $option,
            $label,
            function () use ($option, $desc) {
                $value = get_option($option, 1);
                echo '<input type="number" min="0" name="' . esc_attr($option) . '" value="' . esc_attr($value) . '" />';
                echo '<p class="description">' . esc_html($desc) . '</p>';
            },
            'musicalbum_recommendation_options',
            'musicalbum_recommendation_main'
        );
    }

    public static function render_page() {
        ?>
        <div class="wrap">
            <h1>Musicalbum 智能推荐设置</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('musicalbum_recommendation_options');
                do_settings_sections('musicalbum_recommendation_options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
