<?php
/**
 * Musicalbum Community Adapter
 *
 * 功能说明：
 * - 作为 Smart Recommendations 与 Community Integration 之间的适配层
 * - 从 BuddyPress / bbPress / Community 插件中提取用户社区活跃数据
 * - 统一输出为「社区活跃度评分」，供推荐引擎使用
 *
 * 设计原则：
 * - 不直接依赖具体业务实现
 * - 任一社区组件缺失时自动降级
 * - 只提供“读接口”，不写社区数据
 */

defined('ABSPATH') || exit;

class Musicalbum_Community_Adapter {

    public static function init() {
        // 目前不需要注册 Hook，预留扩展点
    }

    /**
     * 获取用户社区活跃度评分
     *
     * @param int $user_id
     * @return int
     */
    public static function get_user_activity_score($user_id) {
        $score = 0;

        // BuddyPress 活跃度
        if (function_exists('bp_activity_get')) {
            $score += self::get_buddypress_activity_score($user_id);
        }

        // bbPress 活跃度
        if (class_exists('bbPress')) {
            $score += self::get_bbpress_activity_score($user_id);
        }

        // Musicalbum Community 扩展（如果有）
        if (class_exists('Musicalbum_Community_Integration')) {
            $score += self::get_musicalbum_community_score($user_id);
        }

        return intval($score);
    }

    /**
     * BuddyPress 用户活动评分
     */
    private static function get_buddypress_activity_score($user_id) {

        if (!function_exists('bp_activity_get')) {
            return 0;
        }

        $activities = bp_activity_get([
            'user_id' => $user_id,
            'max'     => 20,
        ]);

        if (empty($activities['activities'])) {
            return 0;
        }

        // 简单策略：每条动态记 1 分
        return count($activities['activities']);
    }

    /**
     * bbPress 用户发帖 / 回复评分
     */
    private static function get_bbpress_activity_score($user_id) {

        if (!function_exists('bbp_get_user_topic_count')) {
            return 0;
        }

        $topics  = intval(bbp_get_user_topic_count($user_id));
        $replies = intval(bbp_get_user_reply_count($user_id));

        // 发帖权重大于回复
        return ($topics * 2) + $replies;
    }

    /**
     * Musicalbum 社区插件的扩展活跃度
     */
    private static function get_musicalbum_community_score($user_id) {

        /**
         * 预留过滤器，允许 Community 插件主动提供评分
         * 例如：观演分享、资源上传等
         */
        $score = apply_filters(
            'musicalbum_community_activity_score',
            0,
            $user_id
        );

        return intval($score);
    }
}
