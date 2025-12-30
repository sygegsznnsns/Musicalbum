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
     * - WP 后台列表页：管理员可查看所有记录，普通用户只看自己的
     * - 前端列表：所有用户（包括管理员）只能查看自己的记录
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
            // 如果 post_type 为空，需要检查是否真的是针对 viewing CPT 的查询
            if (is_admin()) {
                // 后台：检查当前屏幕是否是viewing CPT的列表页
                $screen = function_exists('get_current_screen') ? get_current_screen() : null;
                if ($screen && isset($screen->post_type) && in_array($screen->post_type, $viewing_types)) {
                    // 是viewing CPT的列表页，继续执行限制
                } else {
                    // 不是viewing CPT，不限制
                    return;
                }
            } else {
                // 前端：只有当访问viewing单篇文章时才限制
                if ($query->is_singular()) {
                    // 检查当前查询的对象是否是viewing类型
                    $queried_object = $query->get_queried_object();
                    if ($queried_object && isset($queried_object->post_type) && in_array($queried_object->post_type, $viewing_types)) {
                        // 是viewing类型，继续执行限制
                    } else {
                        // 不是viewing类型，不限制
                        return;
                    }
                } else {
                    // 不是单篇文章查询（如页面、文章列表等），不限制
            return;
                }
            }
        } elseif (is_array($post_type)) {
            // post_type 是数组，检查是否包含viewing类型
            $intersect = array_intersect($post_type, $viewing_types);
            if (empty($intersect)) {
                return;
            }
        } else {
            // post_type 是字符串，检查是否是viewing类型
            if (!in_array($post_type, $viewing_types)) {
                return;
            }
        }

        // 在后台，管理员可以查看所有记录
        if (is_admin() && current_user_can('edit_others_posts')) {
            return;
        }

        // 在前端，即使是管理员，也只能看到自己的记录
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

