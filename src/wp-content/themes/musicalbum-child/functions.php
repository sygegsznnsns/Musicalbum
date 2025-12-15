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
});

/**
 * 在此添加子主题的其他钩子与模板辅助函数
 */
