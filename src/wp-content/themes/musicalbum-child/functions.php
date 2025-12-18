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

        // 支持旧 CPT 名称与新 CPT 名称，确保前端/后台查询都能被限制
        $post_type = $query->get('post_type');
        $viewing_types = array(self::VIEWING_CPT, 'viewing_record');
        // 处理 post_type 为空或数组的情况
        if (empty($post_type)) {
            // 如果没有显式设置 post_type，检查 query 是否是针对 viewing CPT 的（例如 admin 列表）
            // 继续执行，后续 author 限制也适用于全局查询限制（不会误伤其他查询）
        } elseif (is_array($post_type)) {
            $intersect = array_intersect($post_type, $viewing_types);
            if (empty($intersect)) {
                return;
            }
        } else {
            if (!in_array($post_type, $viewing_types)) {
                return;
            }
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


        // 支持旧/new CPT 名称
        $valid_types = array(self::VIEWING_CPT, 'viewing_record');
        if (!$post || !in_array($post->post_type, $valid_types)) {
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

