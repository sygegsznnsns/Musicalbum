<?php
/**
 * 推荐系统集成类
 * 
 * 将社区数据整合到推荐算法中
 */

defined('ABSPATH') || exit;

class Musicalbum_Recommendation_Integration {
    
    /**
     * 初始化
     */
    public static function init() {
        // 如果推荐插件存在，添加过滤器
        if (class_exists('Musicalbum_Smart_Recs')) {
            // 通过反射或直接修改推荐插件来集成
            // 由于推荐插件使用私有方法，我们通过其他方式集成
            add_action('musicalbum_before_recommendations', array(__CLASS__, 'before_recommendations'), 10, 2);
        }
    }
    
    /**
     * 在推荐之前执行
     */
    public static function before_recommendations($post_id, $count) {
        // 这里可以添加社区数据来影响推荐
        // 由于原推荐插件没有公开过滤器，这个功能作为扩展点保留
    }
    
    /**
     * 获取社区增强的推荐内容
     * 可以作为独立功能使用，或通过其他方式集成到推荐系统
     */
    public static function get_community_enhanced_recommendations($post_id, $count = 6) {
        // 获取社区热门内容
        $popular_posts = self::get_popular_community_posts($count);
        
        // 获取用户关注的人的观演记录
        $followed_users_posts = self::get_followed_users_viewings($count);
        
        // 合并结果
        $all_posts = array_merge($followed_users_posts, $popular_posts);
        $all_posts = array_unique($all_posts);
        
        // 限制数量
        return array_slice($all_posts, 0, $count);
    }
    
    /**
     * 获取社区热门内容
     */
    private static function get_popular_community_posts($limit = 5) {
        $popular_posts = array();
        
        // 从 BuddyPress 活动流获取热门内容
        if (function_exists('bp_activity_get')) {
            $activities = bp_activity_get(array(
                'type' => 'viewing_shared',
                'per_page' => $limit * 2, // 获取更多以便筛选
                'filter' => array(
                    'action' => 'viewing_shared',
                ),
            ));
            
            if ($activities && !empty($activities['activities'])) {
                $viewing_ids = array();
                foreach ($activities['activities'] as $activity) {
                    if (isset($activity->item_id)) {
                        $viewing_ids[] = $activity->item_id;
                    }
                }
                
                // 统计出现次数
                $viewing_counts = array_count_values($viewing_ids);
                arsort($viewing_counts);
                
                // 获取最热门的观演记录
                $top_viewings = array_slice(array_keys($viewing_counts), 0, $limit, true);
                
                foreach ($top_viewings as $viewing_id) {
                    $viewing = get_post($viewing_id);
                    if ($viewing && $viewing->post_type === 'musicalbum_viewing') {
                        $popular_posts[] = $viewing_id;
                    }
                }
            }
        }
        
        // 从论坛获取热门主题关联的观演记录
        if (function_exists('bbp_get_recent_reply_ids')) {
            $recent_replies = bbp_get_recent_reply_ids(array(
                'number' => $limit * 3,
            ));
            
            if ($recent_replies) {
                $topic_viewing_map = array();
                foreach ($recent_replies as $reply_id) {
                    $topic_id = bbp_get_reply_topic_id($reply_id);
                    if ($topic_id) {
                        $viewing_id = get_post_meta($topic_id, '_linked_viewing_id', true);
                        if ($viewing_id) {
                            if (!isset($topic_viewing_map[$viewing_id])) {
                                $topic_viewing_map[$viewing_id] = 0;
                            }
                            $topic_viewing_map[$viewing_id]++;
                        }
                    }
                }
                
                arsort($topic_viewing_map);
                $top_viewings = array_slice(array_keys($topic_viewing_map), 0, $limit, true);
                $popular_posts = array_merge($popular_posts, $top_viewings);
            }
        }
        
        return array_unique($popular_posts);
    }
    
    /**
     * 获取用户关注的人的观演记录
     */
    private static function get_followed_users_viewings($limit = 5) {
        if (!is_user_logged_in() || !function_exists('bp_get_following_ids')) {
            return array();
        }
        
        $user_id = get_current_user_id();
        $following_ids = bp_get_following_ids(array('user_id' => $user_id));
        
        if (empty($following_ids)) {
            return array();
        }
        
        $following_ids = wp_parse_id_list($following_ids);
        
        // 获取关注的人的观演记录
        $viewings = get_posts(array(
            'post_type' => 'musicalbum_viewing',
            'author__in' => $following_ids,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ));
        
        return $viewings;
    }
    
    /**
     * 合并并排序帖子
     */
    private static function merge_and_sort_posts($original_posts, $popular_posts, $followed_posts) {
        // 创建帖子ID数组
        $post_ids = array();
        foreach ($original_posts as $post) {
            $post_id = is_object($post) ? $post->ID : $post;
            $post_ids[] = $post_id;
        }
        
        // 添加热门内容（提高权重）
        foreach ($popular_posts as $popular_id) {
            if (!in_array($popular_id, $post_ids)) {
                $post_ids[] = $popular_id;
            }
        }
        
        // 添加关注的人的观演记录（最高优先级）
        $final_posts = array();
        foreach ($followed_posts as $followed_id) {
            if (!in_array($followed_id, $final_posts)) {
                array_unshift($final_posts, $followed_id);
            }
        }
        
        // 添加其他帖子
        foreach ($post_ids as $post_id) {
            if (!in_array($post_id, $final_posts)) {
                $final_posts[] = $post_id;
            }
        }
        
        // 转换为帖子对象
        $result = array();
        foreach ($final_posts as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $result[] = $post;
            }
        }
        
        return $result;
    }
    
    /**
     * 获取用户社区活跃度分数
     * 用于推荐算法权重计算
     */
    public static function get_user_community_score($user_id) {
        $score = 0;
        
        // 论坛活动
        if (function_exists('bbp_get_user_topic_count')) {
            $topic_count = bbp_get_user_topic_count($user_id, true);
            $reply_count = bbp_get_user_reply_count($user_id, true);
            $score += ($topic_count * 2) + $reply_count;
        }
        
        // BuddyPress 活动
        if (function_exists('bp_activity_get')) {
            $activities = bp_activity_get(array(
                'user_id' => $user_id,
                'per_page' => 1,
            ));
            if ($activities && isset($activities['total'])) {
                $score += $activities['total'];
            }
        }
        
        // 观演记录数量
        $viewing_count = Musicalbum_BuddyPress_Integration::get_user_viewing_count($user_id);
        $score += $viewing_count * 3; // 观演记录权重更高
        
        return $score;
    }
}

