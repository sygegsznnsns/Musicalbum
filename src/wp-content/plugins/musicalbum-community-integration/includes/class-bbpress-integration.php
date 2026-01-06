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
        // 确保 bbPress 已加载
        if (!class_exists('bbPress')) {
            return;
        }
        
        // 检查是否已经创建过
        $forum_id = get_option('musicalbum_viewing_forum_id', 0);
        if ($forum_id) {
            $forum = get_post($forum_id);
            if ($forum && $forum->post_type === 'forum') {
                return; // 论坛已存在
            }
            // 如果论坛不存在，清除选项以便重新创建
            delete_option('musicalbum_viewing_forum_id');
        }
        
        // 创建"观演交流"论坛
        try {
            // 使用 WordPress 原生方式创建论坛
            $forum_id = wp_insert_post(array(
                'post_title'   => '观演交流',
                'post_content' => '分享观演记录，讨论剧目和演出体验',
                'post_status'  => 'publish',
                'post_type'    => 'forum',
                'post_author'  => 1, // 管理员
            ));
            
            if ($forum_id && !is_wp_error($forum_id)) {
                update_option('musicalbum_viewing_forum_id', $forum_id);
                
                // 设置论坛元数据（如果需要）
                if (function_exists('bbp_update_forum_meta')) {
                    bbp_update_forum_meta($forum_id, '_bbp_status', 'open');
                    bbp_update_forum_meta($forum_id, '_bbp_visibility', 'publish');
                }
            }
        } catch (Exception $e) {
            // 静默失败，不影响其他功能
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Musicalbum: Failed to create viewing forum - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 手动创建论坛（管理员工具函数）
     */
    public static function force_create_forum() {
        // 清除现有选项
        delete_option('musicalbum_viewing_forum_id');
        
        // 重新创建
        self::create_viewing_forums();
        
        $forum_id = get_option('musicalbum_viewing_forum_id', 0);
        return $forum_id;
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
        if (!class_exists('bbPress')) {
            return '<p>bbPress 插件未激活。</p>';
        }
        
        $forum_id = intval($atts['forum_id']);
        $category = sanitize_text_field($atts['category']);
        $limit = intval($atts['limit']);
        $link_to_root = filter_var($atts['link_to_root'], FILTER_VALIDATE_BOOLEAN);
        
        // 如果开启了 link_to_root 模式，且没有指定具体的 forum_id，则显示论坛根目录（所有论坛列表）
        // 实际上 shortcode_forum 中默认 forum_id 为 0
        if ($link_to_root && $forum_id === 0) {
            ob_start();
            ?>
            <div class="musicalbum-forum-shortcode">
                <h3>论坛版块</h3>
                <?php
                // 获取所有论坛版块 (bbp_has_forums 会自动设置全局查询)
                if (bbp_has_forums(array('post_status' => 'publish'))) : ?>
                    <ul class="musicalbum-forum-list">
                        <?php while (bbp_forums()) : bbp_the_forum(); ?>
                            <li>
                                <a href="<?php bbp_forum_permalink(); ?>" class="bbp-forum-title">
                                    <?php bbp_forum_title(); ?>
                                </a>
                                <div class="forum-meta">
                                    <?php bbp_forum_topic_count(); ?> 话题, 
                                    <?php bbp_forum_reply_count(); ?> 回复
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else : ?>
                    <p>暂无论坛版块。</p>
                <?php endif; ?>
                
                <p><a href="<?php echo esc_url(bbp_get_forums_url()); ?>" class="button">进入论坛首页</a></p>
            </div>
            <?php
            return ob_get_clean();
        }

        // 下面是原有的显示单个论坛话题的逻辑
        if (!$forum_id) {
            // 如果没有指定论坛ID，使用观演记录论坛
            $forum_id = get_option('musicalbum_viewing_forum_id', 0);
            
            // 如果还是没有，尝试创建
            if (!$forum_id) {
                self::create_viewing_forums();
                $forum_id = get_option('musicalbum_viewing_forum_id', 0);
            }
        }
        
        if (!$forum_id) {
            return '<p>未找到指定的论坛。请确保 bbPress 插件已激活，或联系管理员创建论坛。</p>';
        }
        
        // 使用 WordPress 原生方式获取论坛
        $forum = get_post($forum_id);
        if (!$forum || $forum->post_type !== 'forum') {
            // 如果论坛不存在，尝试重新创建
            delete_option('musicalbum_viewing_forum_id');
            self::create_viewing_forums();
            $forum_id = get_option('musicalbum_viewing_forum_id', 0);
            $forum = $forum_id ? get_post($forum_id) : null;
            
            if (!$forum || $forum->post_type !== 'forum') {
                return '<p>论坛不存在。请确保 bbPress 插件已激活。</p>';
            }
        }
        
        // 获取论坛主题
        $topics = array();
        if (function_exists('bbp_get_forum_topics')) {
            $topics = bbp_get_forum_topics(array(
                'post_parent' => $forum_id,
                'posts_per_page' => $limit,
            ));
        } else {
            // 备用方式：使用 WP_Query
            $topics_query = new WP_Query(array(
                'post_type' => 'topic',
                'post_parent' => $forum_id,
                'posts_per_page' => $limit,
                'post_status' => 'publish',
            ));
            $topics = $topics_query->posts;
        }
        
        ob_start();
        ?>
        <div class="musicalbum-forum-shortcode">
            <h3><?php echo esc_html($forum->post_title); ?></h3>
            <?php if ($forum->post_content) : ?>
                <p class="forum-description"><?php echo esc_html($forum->post_content); ?></p>
            <?php endif; ?>
            <?php if ($topics && !empty($topics)) : ?>
                <ul class="musicalbum-forum-topics">
                    <?php foreach ($topics as $topic) : 
                        $topic_id = is_object($topic) ? $topic->ID : $topic;
                        $topic_title = get_the_title($topic_id);
                        $topic_permalink = get_permalink($topic_id);
                        
                        // 获取回复数
                        $reply_count = 0;
                        if (function_exists('bbp_get_topic_reply_count')) {
                            $reply_count = bbp_get_topic_reply_count($topic_id);
                        } else {
                            $replies = get_posts(array(
                                'post_type' => 'reply',
                                'post_parent' => $topic_id,
                                'posts_per_page' => -1,
                                'fields' => 'ids',
                            ));
                            $reply_count = count($replies);
                        }
                    ?>
                        <li>
                            <a href="<?php echo esc_url($topic_permalink); ?>">
                                <?php echo esc_html($topic_title); ?>
                            </a>
                            <span class="topic-meta">
                                <?php echo esc_html($reply_count); ?> 回复
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>暂无主题。成为第一个发帖的人吧！</p>
            <?php endif; ?>
            <?php if (function_exists('bbp_get_forum_permalink')) : 
                $forum_link = $link_to_root ? bbp_get_forums_url() : bbp_get_forum_permalink($forum_id);
                $link_text = $link_to_root ? '进入论坛首页' : '进入版块';
            ?>
                <p><a href="<?php echo esc_url($forum_link); ?>" class="button"><?php echo esc_html($link_text); ?></a></p>
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

