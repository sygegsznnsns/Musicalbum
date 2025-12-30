<?php
/**
 * BuddyPress 集成类
 * 
 * 处理与 BuddyPress 社交网络的集成功能
 */

defined('ABSPATH') || exit;

class Musicalbum_BuddyPress_Integration {
    
    /**
     * 初始化
     */
    public static function init() {
        // 只在 BuddyPress 激活时执行
        if (!function_exists('buddypress')) {
            return;
        }
        
        // 扩展用户资料页
        // add_action('bp_setup_nav', array(__CLASS__, 'setup_profile_nav'), 100);
        
        // 在用户资料页显示观演记录
        // add_action('bp_profile_header_meta', array(__CLASS__, 'display_viewing_stats'));
        
        // 自定义活动流
        add_action('bp_register_activity_actions', array(__CLASS__, 'register_viewing_activity'));
        
        // 创建观演交流群组
        add_action('bp_loaded', array(__CLASS__, 'create_viewing_group'));
    }
    
    /**
     * 设置用户资料导航
     */
    public static function setup_profile_nav() {
        // 确保 BuddyPress 已加载
        if (!function_exists('buddypress') || !function_exists('bp_core_new_nav_item')) {
            return;
        }
        
        // 添加"观演记录"标签页
        try {
            bp_core_new_nav_item(array(
                'name' => '观演记录',
                'slug' => 'viewings',
                'screen_function' => array(__CLASS__, 'viewings_screen'),
                'position' => 30,
                'default_subnav_slug' => 'viewings',
            ));
        } catch (Exception $e) {
            // 静默失败，不影响其他功能
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Musicalbum: Failed to setup profile nav - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 观演记录页面屏幕函数
     */
    public static function viewings_screen() {
        add_action('bp_template_content', array(__CLASS__, 'viewings_content'));
        bp_core_load_template('members/single/plugins');
    }
    
    /**
     * 观演记录页面内容
     */
    public static function viewings_content() {
        $user_id = bp_displayed_user_id();
        
        $viewings = get_posts(array(
            'post_type' => 'musicalbum_viewing',
            'author' => $user_id,
            'posts_per_page' => 10,
            'post_status' => 'publish',
        ));
        
        ?>
        <div class="musicalbum-user-viewings">
            <h3>观演记录</h3>
            <?php if ($viewings) : ?>
                <ul class="viewing-list">
                    <?php foreach ($viewings as $viewing) : ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($viewing->ID)); ?>">
                                <?php echo esc_html(get_the_title($viewing->ID)); ?>
                            </a>
                            <span class="viewing-date">
                                <?php echo esc_html(get_the_date('', $viewing->ID)); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>该用户还没有观演记录。</p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 在用户资料页显示观演记录统计
     */
    public static function display_viewing_stats() {
        $user_id = bp_displayed_user_id();
        if (!$user_id) {
            return;
        }
        
        $viewing_count = self::get_user_viewing_count($user_id);
        
        if ($viewing_count > 0) {
            echo '<span class="viewing-count">';
            echo sprintf('观演记录：%d 条', $viewing_count);
            echo '</span>';
        }
    }
    
    /**
     * 获取用户观演记录数量
     */
    public static function get_user_viewing_count($user_id) {
        $count = wp_cache_get('viewing_count_' . $user_id, 'musicalbum');
        
        if (false === $count) {
            $count = get_posts(array(
                'post_type' => 'musicalbum_viewing',
                'author' => $user_id,
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post_status' => 'publish',
            ));
            $count = count($count);
            wp_cache_set('viewing_count_' . $user_id, $count, 'musicalbum', 3600);
        }
        
        return $count;
    }
    
    /**
     * 注册观演记录分享活动类型
     */
    public static function register_viewing_activity() {
        if (!function_exists('bp_activity_set_action')) {
            return;
        }
        
        bp_activity_set_action(
            'musicalbum',
            'viewing_shared',
            '分享了观演记录',
            array(__CLASS__, 'format_viewing_activity'),
            '观演记录',
            array('activity', 'member'),
            10
        );
    }
    
    /**
     * 格式化观演记录活动
     */
    public static function format_viewing_activity($action, $activity) {
        $viewing_id = $activity->item_id;
        $viewing = get_post($viewing_id);
        
        if (!$viewing) {
            return $action;
        }
        
        $viewing_link = get_permalink($viewing_id);
        $viewing_title = get_the_title($viewing_id);
        
        $action = sprintf(
            '%s <a href="%s">%s</a>',
            $action,
            esc_url($viewing_link),
            esc_html($viewing_title)
        );
        
        return $action;
    }
    
    /**
     * 创建观演交流群组
     */
    public static function create_viewing_group() {
        // 确保 BuddyPress 已加载
        if (!function_exists('buddypress') || !function_exists('groups_create_group')) {
            return;
        }
        
        // 检查是否已经创建过
        $group_id = get_option('musicalbum_viewing_group_id', 0);
        if ($group_id) {
            // 检查群组是否存在
            $group = groups_get_group($group_id);
            if ($group && !empty($group->id)) {
                return;
            }
        }
        
        // 创建群组
        try {
            $group_id = groups_create_group(array(
                'name' => '观演交流',
                'description' => '分享观演记录，讨论剧目和演出体验的群组',
                'status' => 'public',
                'creator_id' => 1, // 管理员ID
            ));
            
            if ($group_id && !is_wp_error($group_id)) {
                update_option('musicalbum_viewing_group_id', $group_id);
            }
        } catch (Exception $e) {
            // 静默失败，不影响其他功能
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Musicalbum: Failed to create viewing group - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * 渲染用户活动短码
     */
    public static function render_activity_shortcode($atts) {
        if (!function_exists('bp_activity_get')) {
            return '<p>BuddyPress 插件未激活。</p>';
        }
        
        $user_id = intval($atts['user_id']);
        $limit = intval($atts['limit']);
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return '<p>请先登录。</p>';
        }
        
        $activities = bp_activity_get(array(
            'user_id' => $user_id,
            'per_page' => $limit,
        ));
        
        ob_start();
        ?>
        <div class="musicalbum-user-activity-shortcode">
            <h3>用户活动</h3>
            <?php if ($activities && !empty($activities['activities'])) : ?>
                <ul class="activity-list">
                    <?php foreach ($activities['activities'] as $activity) : ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <?php echo wp_kses_post($activity->content); ?>
                            </div>
                            <div class="activity-meta">
                                <?php echo esc_html(bp_core_time_since($activity->date_recorded)); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>暂无活动。</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * 分享观演记录到活动流
     */
    public static function share_viewing_to_activity($viewing_id, $user_id, $message = '') {
        if (!function_exists('bp_activity_add')) {
            return false;
        }
        
        $viewing = get_post($viewing_id);
        if (!$viewing || $viewing->post_type !== 'musicalbum_viewing') {
            return false;
        }
        
        $viewing_title = get_the_title($viewing_id);
        $viewing_link = get_permalink($viewing_id);
        
        $content = $message . "\n\n";
        $content .= '<a href="' . esc_url($viewing_link) . '">' . esc_html($viewing_title) . '</a>';
        
        $activity_id = bp_activity_add(array(
            'action' => '分享了观演记录',
            'content' => $content,
            'component' => 'musicalbum',
            'type' => 'viewing_shared',
            'user_id' => $user_id,
            'item_id' => $viewing_id,
            'primary_link' => $viewing_link,
        ));
        
        return $activity_id;
    }
    
    /**
     * 获取用户统计信息
     */
    public static function get_user_stats($user_id) {
        $viewing_count = self::get_user_viewing_count($user_id);
        
        // 获取论坛主题数
        $topic_count = 0;
        if (function_exists('bbp_get_user_topic_count')) {
            $topic_count = bbp_get_user_topic_count($user_id, true);
        }
        
        // 获取论坛回复数
        $reply_count = 0;
        if (function_exists('bbp_get_user_reply_count')) {
            $reply_count = bbp_get_user_reply_count($user_id, true);
        }
        
        // 获取 BuddyPress 活动数
        $activity_count = 0;
        if (function_exists('bp_activity_total_favorites_for_user')) {
            $activities = bp_activity_get(array(
                'user_id' => $user_id,
                'per_page' => 1,
            ));
            if ($activities && isset($activities['total'])) {
                $activity_count = $activities['total'];
            }
        }
        
        return array(
            'viewing_count' => $viewing_count,
            'topic_count' => $topic_count,
            'reply_count' => $reply_count,
            'activity_count' => $activity_count,
        );
    }
}

