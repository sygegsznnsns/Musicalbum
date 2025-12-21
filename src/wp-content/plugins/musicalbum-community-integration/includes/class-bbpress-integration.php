<?php
/**
 * bbPress 集成类
 * 
 * 处理与 bbPress 论坛的集成功能
 */

defined('ABSPATH') || exit;

class Musicalbum_BBPress_Integration {
    
    /**
     * 初始化
     */
    public static function init() {
        // 只在 bbPress 激活时执行
        if (!class_exists('bbPress')) {
            return;
        }
        
        // 创建观演记录相关论坛分类
        add_action('init', array(__CLASS__, 'create_viewing_forums'));
        
        // 添加观演记录关联显示
        add_filter('bbp_get_topic_content', array(__CLASS__, 'display_linked_viewing'), 10, 2);
        
        // 自定义论坛样式类
        add_filter('body_class', array(__CLASS__, 'add_forum_body_class'));
    }
    
    /**
     * 创建观演记录相关的论坛分类
     */
    public static function create_viewing_forums() {
        if (!function_exists('bbp_create_forum')) {
            return;
        }
        
        // 检查是否已经创建过
        $forum_id = get_option('musicalbum_viewing_forum_id', 0);
        if ($forum_id && get_post($forum_id)) {
            return;
        }
        
        // 创建"观演交流"论坛
        $forum_id = bbp_create_forum(array(
            'post_title' => '观演交流',
            'post_content' => '分享观演记录，讨论剧目和演出体验',
            'post_status' => 'publish',
        ));
        
        if ($forum_id && !is_wp_error($forum_id)) {
            update_option('musicalbum_viewing_forum_id', $forum_id);
        }
    }
    
    /**
     * 在论坛主题中显示关联的观演记录
     */
    public static function display_linked_viewing($content, $topic_id) {
        $viewing_id = get_post_meta($topic_id, '_linked_viewing_id', true);
        
        if (!$viewing_id) {
            return $content;
        }
        
        $viewing = get_post($viewing_id);
        if (!$viewing || $viewing->post_type !== 'musicalbum_viewing') {
            return $content;
        }
        
        $viewing_link = get_permalink($viewing_id);
        $viewing_title = get_the_title($viewing_id);
        
        $linked_content = '<div class="musicalbum-linked-viewing">';
        $linked_content .= '<h4>关联的观演记录</h4>';
        $linked_content .= '<p><a href="' . esc_url($viewing_link) . '">' . esc_html($viewing_title) . '</a></p>';
        $linked_content .= '</div>';
        
        return $linked_content . $content;
    }
    
    /**
     * 添加论坛页面 body 类
     */
    public static function add_forum_body_class($classes) {
        if (function_exists('bbp_is_forum') && bbp_is_forum()) {
            $classes[] = 'musicalbum-forum-page';
        }
        
        return $classes;
    }
    
    /**
     * 渲染论坛短码
     */
    public static function render_forum_shortcode($atts) {
        if (!function_exists('bbp_get_forum')) {
            return '<p>bbPress 插件未激活。</p>';
        }
        
        $forum_id = intval($atts['forum_id']);
        $category = sanitize_text_field($atts['category']);
        $limit = intval($atts['limit']);
        
        if (!$forum_id) {
            // 如果没有指定论坛ID，使用观演记录论坛
            $forum_id = get_option('musicalbum_viewing_forum_id', 0);
        }
        
        if (!$forum_id) {
            return '<p>未找到指定的论坛。</p>';
        }
        
        $forum = bbp_get_forum($forum_id);
        if (!$forum) {
            return '<p>论坛不存在。</p>';
        }
        
        // 获取论坛主题
        $topics = bbp_get_forum_topics(array(
            'post_parent' => $forum_id,
            'posts_per_page' => $limit,
        ));
        
        ob_start();
        ?>
        <div class="musicalbum-forum-shortcode">
            <h3><?php echo esc_html($forum->post_title); ?></h3>
            <?php if ($topics) : ?>
                <ul class="musicalbum-forum-topics">
                    <?php foreach ($topics as $topic) : ?>
                        <li>
                            <a href="<?php echo esc_url(bbp_get_topic_permalink($topic->ID)); ?>">
                                <?php echo esc_html($topic->post_title); ?>
                            </a>
                            <span class="topic-meta">
                                <?php echo esc_html(bbp_get_topic_reply_count($topic->ID)); ?> 回复
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>暂无主题。</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * 分享观演记录到论坛
     */
    public static function share_viewing_to_forum($viewing_id, $user_id, $message = '') {
        if (!function_exists('bbp_new_topic')) {
            return false;
        }
        
        $viewing = get_post($viewing_id);
        if (!$viewing || $viewing->post_type !== 'musicalbum_viewing') {
            return false;
        }
        
        $forum_id = get_option('musicalbum_community_viewing_forum_id', 0);
        if (!$forum_id) {
            $forum_id = get_option('musicalbum_viewing_forum_id', 0);
        }
        
        if (!$forum_id) {
            return false;
        }
        
        $viewing_title = get_the_title($viewing_id);
        $viewing_link = get_permalink($viewing_id);
        
        $topic_title = '观演记录：' . $viewing_title;
        $topic_content = $message . "\n\n";
        $topic_content .= '<p><a href="' . esc_url($viewing_link) . '">查看完整观演记录</a></p>';
        
        $topic_id = bbp_new_topic(array(
            'post_parent' => $forum_id,
            'post_title' => $topic_title,
            'post_content' => $topic_content,
            'post_author' => $user_id,
        ), array('forum_id' => $forum_id));
        
        if ($topic_id && !is_wp_error($topic_id)) {
            // 关联观演记录
            update_post_meta($topic_id, '_linked_viewing_id', $viewing_id);
            return $topic_id;
        }
        
        return false;
    }
}

