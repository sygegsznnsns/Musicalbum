<?php
if (!defined('ABSPATH')) { exit; }

/**
 * 入队父/子主题样式：
 * - 父主题样式：确保 Astra 的基础样式先加载
 * - 子主题样式：在父样式之后加载，用于覆写与扩展
 */
add_action('wp_enqueue_scripts', function() {
    $parent_style = 'parent-style';
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css', [], null);
    wp_enqueue_style(
        'musicalbum-child-style',
        get_stylesheet_uri(),
        [$parent_style],
        wp_get_theme()->get('Version')
    );
    
    // 入队背景音乐相关资源
    $theme_version = wp_get_theme()->get('Version');
    $assets_url = get_stylesheet_directory_uri() . '/assets';
    
    // 背景音乐CSS
    wp_enqueue_style(
        'background-music-style',
        $assets_url . '/background-music.css',
        [],
        $theme_version
    );
    
    // 背景音乐JavaScript
    wp_enqueue_script(
        'background-music-script',
        $assets_url . '/background-music.js',
        [],
        $theme_version,
        true // 在footer加载
    );
});

/**
 * 注册主题自定义选项（用于设置背景音乐）
 */
add_action('admin_init', function() {
    register_setting('musicalbum_theme_options', 'background_music_url');
    
    add_settings_section(
        'musicalbum_music_section',
        '背景音乐设置',
        function() {
            echo '<p>设置网站的背景音乐。您可以上传音频文件到媒体库，然后复制文件URL粘贴到下方。</p>';
        },
        'musicalbum_theme_options'
    );
    
    add_settings_field(
        'background_music_url',
        '音频文件URL',
        function() {
            $value = get_option('background_music_url', '');
            echo '<input type="url" name="background_music_url" value="' . esc_attr($value) . '" class="regular-text" />';
            echo '<p class="description">输入音频文件的完整URL（从媒体库获取），或留空使用默认路径：<code>' . esc_html(get_stylesheet_directory_uri() . '/assets/background-music.mp3') . '</code></p>';
        },
        'musicalbum_theme_options',
        'musicalbum_music_section'
    );
});

/**
 * 在WordPress后台添加主题选项菜单
 */
add_action('admin_menu', function() {
    add_theme_page(
        '主题设置',
        '主题设置',
        'manage_options',
        'musicalbum-theme-options',
        function() {
            ?>
            <div class="wrap">
                <h1>Musicalbum 主题设置</h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('musicalbum_theme_options');
                    do_settings_sections('musicalbum_theme_options');
                    submit_button();
                    ?>
                </form>
                <hr>
                <h2>如何添加背景音乐？</h2>
                <ol>
                    <li>进入 <strong>媒体 → 添加新媒体</strong>，上传您的音频文件（MP3格式推荐）</li>
                    <li>上传后，点击音频文件，在右侧详情中复制<strong>文件URL</strong></li>
                    <li>将复制的URL粘贴到上方的"音频文件URL"输入框中</li>
                    <li>点击<strong>保存更改</strong>按钮</li>
                    <li>刷新网站前台页面，您应该能看到右下角的音乐播放器</li>
                </ol>
                <p><strong>提示：</strong>如果留空，系统会尝试使用主题目录下的 <code>assets/background-music.mp3</code> 文件。</p>
            </div>
            <?php
        }
    );
});

/**
 * 在footer中添加背景音乐播放器
 */
add_action('wp_footer', function() {
    // 优先使用后台设置的音频URL
    $music_url = get_option('background_music_url', '');
    
    // 如果后台没有设置，使用默认路径
    if (empty($music_url)) {
        $music_url = get_stylesheet_directory_uri() . '/assets/background-music.mp3';
    }
    
    // 如果URL为空，不显示播放器
    if (empty($music_url)) {
        return;
    }
    ?>
    <!-- 背景音乐播放器 -->
    <audio id="background-music" loop preload="auto">
        <source src="<?php echo esc_url($music_url); ?>" type="audio/mpeg">
        <!-- 如果浏览器不支持MP3，可以添加其他格式 -->
        <!-- <source src="<?php echo esc_url(str_replace('.mp3', '.ogg', $music_url)); ?>" type="audio/ogg"> -->
    </audio>
    
    <div id="background-music-player">
        <button id="music-play-pause" aria-label="播放背景音乐">
            <span class="music-icon">▶</span>
        </button>
        <div id="music-volume-control">
            <span id="music-volume-icon">🔊</span>
            <input type="range" id="music-volume" min="0" max="1" step="0.01" value="0.5" aria-label="音量控制">
        </div>
    </div>
    
    <div id="music-info" style="display: none; opacity: 0;">
        背景音乐已加载
    </div>
    <?php
});

/**
 * 在此添加子主题的其他钩子与模板辅助函数
 */


/*
Plugin Name: Musicalbum User Access Control
Description: 观演记录的用户账户与权限控制模块（基于 WordPress 原生权限）
Version: 1.0.0
*/

defined('ABSPATH') || exit;

final class Musicalbum_User_Access {

    /**
     * 与推荐插件保持一致的 CPT 名称
     */
    const VIEWING_CPT = 'musicalbum_viewing';

    public static function init() {
        // 1. 后台 / 前端查询：只显示当前用户自己的观演记录
        add_action('pre_get_posts', [__CLASS__, 'limit_viewing_records_to_owner']);

        // 2. 权限兜底：禁止编辑 / 删除他人的观演记录
        add_filter('user_has_cap', [__CLASS__, 'restrict_editing_foreign_records'], 10, 4);
    }

    /**
     * 仅允许用户看到自己创建的观演记录
     *
     * 适用场景：
     * - WP 后台列表页
     * - Profile Builder 前端列表
     * - ACF / REST 查询
     */
    public static function limit_viewing_records_to_owner($query) {

        if (is_admin() && !$query->is_main_query()) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if ($query->get('post_type') !== self::VIEWING_CPT) {
            return;
        }

        // 管理员仍可看到全部数据
        if (current_user_can('edit_others_posts')) {
            return;
        }

        // 普通用户：只能看到自己的记录
        $query->set('author', get_current_user_id());
    }

    /**
     * 权限兜底控制：
     * 防止用户通过 URL / REST / 表单操作他人的观演记录
     *
     * 注意：
     * - 不是“新增权限”
     * - 只是阻止越权
     */
    public static function restrict_editing_foreign_records($allcaps, $caps, $args, $user) {

        if (empty($args[2])) {
            return $allcaps;
        }

        $post_id = intval($args[2]);
        $post    = get_post($post_id);

        if (!$post || $post->post_type !== self::VIEWING_CPT) {
            return $allcaps;
        }

        // 管理员不受限制
        if (user_can($user, 'edit_others_posts')) {
            return $allcaps;
        }

        // 非作者，禁止编辑 / 删除
        if ((int) $post->post_author !== (int) $user->ID) {
            $allcaps['edit_post']   = false;
            $allcaps['delete_post'] = false;
        }

        return $allcaps;
    }
}

Musicalbum_User_Access::init();

