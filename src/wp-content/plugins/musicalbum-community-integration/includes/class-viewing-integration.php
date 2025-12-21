<?php
/**
 * 观演记录集成类
 * 
 * 处理观演记录与社区功能的集成
 */

defined('ABSPATH') || exit;

class Musicalbum_Viewing_Integration {
    
    /**
     * 初始化
     */
    public static function init() {
        // 在观演记录详情页添加分享按钮
        add_action('wp_footer', array(__CLASS__, 'add_share_button_script'));
        
        // AJAX 处理分享请求
        add_action('wp_ajax_musicalbum_share_viewing', array(__CLASS__, 'ajax_share_viewing'));
        
        // 在观演记录内容后添加分享按钮
        add_filter('the_content', array(__CLASS__, 'add_share_button_to_viewing'), 20);
    }
    
    /**
     * 在观演记录内容后添加分享按钮
     */
    public static function add_share_button_to_viewing($content) {
        // 确保只在观演记录页面显示
        if (!is_singular('musicalbum_viewing')) {
            return $content;
        }
        
        // 确保用户已登录
        if (!is_user_logged_in()) {
            return $content;
        }
        
        try {
            $viewing_id = get_the_ID();
            if (!$viewing_id) {
                return $content;
            }
            
            $share_button = self::render_share_button($viewing_id);
            return $content . $share_button;
        } catch (Exception $e) {
            // 如果出错，返回原内容
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Musicalbum: Failed to add share button - ' . $e->getMessage());
            }
            return $content;
        }
    }
    
    /**
     * 渲染分享按钮
     */
    public static function render_share_button($viewing_id) {
        ob_start();
        ?>
        <div class="musicalbum-viewing-share">
            <h4>分享到社区</h4>
            <form class="musicalbum-share-form" data-viewing-id="<?php echo esc_attr($viewing_id); ?>">
                <textarea name="message" placeholder="添加分享说明（可选）" rows="3"></textarea>
                <div class="share-options">
                    <label>
                        <input type="checkbox" name="share_to_forum" value="1" checked>
                        分享到论坛
                    </label>
                    <?php if (function_exists('buddypress')) : ?>
                    <label>
                        <input type="checkbox" name="share_to_activity" value="1" checked>
                        分享到活动流
                    </label>
                    <?php endif; ?>
                </div>
                <button type="submit" class="button">分享</button>
                <div class="share-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX 处理分享请求
     */
    public static function ajax_share_viewing() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'musicalbum_share')) {
            wp_send_json_error(array('message' => '安全验证失败'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => '请先登录'));
        }
        
        $viewing_id = intval($_POST['viewing_id']);
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $share_to_forum = isset($_POST['share_to_forum']) && $_POST['share_to_forum'] === '1';
        $share_to_activity = isset($_POST['share_to_activity']) && $_POST['share_to_activity'] === '1';
        
        $user_id = get_current_user_id();
        $results = array();
        
        // 分享到论坛
        if ($share_to_forum && class_exists('Musicalbum_BBPress_Integration')) {
            $topic_id = Musicalbum_BBPress_Integration::share_viewing_to_forum($viewing_id, $user_id, $message);
            if ($topic_id) {
                $results['forum'] = array(
                    'success' => true,
                    'topic_id' => $topic_id,
                    'url' => get_permalink($topic_id),
                );
            } else {
                $results['forum'] = array('success' => false);
            }
        }
        
        // 分享到活动流
        if ($share_to_activity && class_exists('Musicalbum_BuddyPress_Integration')) {
            $activity_id = Musicalbum_BuddyPress_Integration::share_viewing_to_activity($viewing_id, $user_id, $message);
            if ($activity_id) {
                $results['activity'] = array(
                    'success' => true,
                    'activity_id' => $activity_id,
                );
            } else {
                $results['activity'] = array('success' => false);
            }
        }
        
        if (!empty($results)) {
            wp_send_json_success(array(
                'message' => '分享成功',
                'results' => $results,
            ));
        } else {
            wp_send_json_error(array('message' => '分享失败'));
        }
    }
    
    /**
     * REST API: 分享观演记录
     */
    public static function share_viewing($request) {
        $viewing_id = intval($request['viewing_id']);
        $message = sanitize_textarea_field($request['message'] ?? '');
        $share_to_forum = isset($request['share_to_forum']) && $request['share_to_forum'] === true;
        $share_to_activity = isset($request['share_to_activity']) && $request['share_to_activity'] === true;
        
        $user_id = get_current_user_id();
        $results = array();
        
        // 验证观演记录
        $viewing = get_post($viewing_id);
        if (!$viewing || $viewing->post_type !== 'musicalbum_viewing') {
            return new WP_Error('invalid_viewing', '无效的观演记录', array('status' => 400));
        }
        
        // 检查权限（只能分享自己的观演记录，除非是管理员）
        if ($viewing->post_author != $user_id && !current_user_can('edit_others_posts')) {
            return new WP_Error('permission_denied', '无权分享此观演记录', array('status' => 403));
        }
        
        // 分享到论坛
        if ($share_to_forum && class_exists('Musicalbum_BBPress_Integration')) {
            $topic_id = Musicalbum_BBPress_Integration::share_viewing_to_forum($viewing_id, $user_id, $message);
            if ($topic_id) {
                $results['forum'] = array(
                    'success' => true,
                    'topic_id' => $topic_id,
                    'url' => get_permalink($topic_id),
                );
            } else {
                $results['forum'] = array('success' => false, 'message' => '分享到论坛失败');
            }
        }
        
        // 分享到活动流
        if ($share_to_activity && class_exists('Musicalbum_BuddyPress_Integration')) {
            $activity_id = Musicalbum_BuddyPress_Integration::share_viewing_to_activity($viewing_id, $user_id, $message);
            if ($activity_id) {
                $results['activity'] = array(
                    'success' => true,
                    'activity_id' => $activity_id,
                );
            } else {
                $results['activity'] = array('success' => false, 'message' => '分享到活动流失败');
            }
        }
        
        if (empty($results)) {
            return new WP_Error('no_action', '未选择分享目标', array('status' => 400));
        }
        
        return array(
            'success' => true,
            'message' => '分享成功',
            'results' => $results,
        );
    }
    
    /**
     * 添加分享按钮脚本
     */
    public static function add_share_button_script() {
        if (!is_singular('musicalbum_viewing')) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // 分享表单处理已在 community-integration.js 中实现
            // 这里保留作为备用
        });
        </script>
        <?php
    }
}

