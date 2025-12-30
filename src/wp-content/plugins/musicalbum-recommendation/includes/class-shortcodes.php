<?php
/**
 * class-shortcodes.php
 * 
 * 功能：该文件定义短代码功能，用于在前端页面显示推荐内容。
 * 
 * 短代码属性：
 * - `limit`：推荐的帖子数量，默认为 5。
 * - `fallback`：备用推荐逻辑，如果推荐为空，则推荐最新发布的帖子。
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // 禁止直接访问
}

class Musicalbum_Shortcodes {

    // 构造函数，注册短代码
    public function __construct() {
        add_shortcode('musicalbum_smart_recommendations', array($this, 'display_recommendations'));
    }

    // 显示推荐内容的短代码回调函数
    public function display_recommendations($atts) {
        // 解析简码属性
        $atts = shortcode_atts(
            array(
                'limit' => 5,       // 默认推荐数量
                'fallback' => 'popular', // 默认备用推荐逻辑
            ),
            $atts
        );
        
        $user_id = get_current_user_id();
        
        // 如果用户未登录，显示提示信息
        if ($user_id === 0) {
            return '请登录以查看个性化推荐内容。';
        }

        // 获取推荐内容
        $recommendations = (new Musicalbum_Recommendation_Engine())->get_recommendations($user_id, $atts['limit']);

        // 如果没有推荐内容，按备用逻辑推荐
        if (!$recommendations->have_posts()) {
            if ($atts['fallback'] == 'popular') {
                // 推荐最受欢迎的文章
                $args = array(
                    'post_type' => 'post',
                    'posts_per_page' => $atts['limit'],
                    'orderby' => 'comment_count', // 按评论数排序
                    'order' => 'DESC'  // 按降序显示
                );
                $recommendations = new WP_Query($args);
            } elseif ($atts['fallback'] == 'latest') {
                // 推荐最新的文章
                $args = array(
                    'post_type' => 'post',
                    'posts_per_page' => $atts['limit'],
                    'orderby' => 'date',
                    'order' => 'DESC'
                );
                $recommendations = new WP_Query($args);
            }
        }

        // 输出推荐内容
        if ($recommendations->have_posts()) {
            $output = '<div class="musicalbum-recommendations">';
            $output .= '<h3>为您推荐</h3>';
            $output .= '<ul>';
            while ($recommendations->have_posts()) {
                $recommendations->the_post();
                $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
            wp_reset_postdata();
            return $output;
        }

        return '没有找到相关的推荐内容。';
    }
}
