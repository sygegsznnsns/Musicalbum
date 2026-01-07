<?php
/**
 * 样式和功能定制类
 * 
 * 处理样式定制和功能扩展
 */

defined('ABSPATH') || exit;

class Musicalbum_Community_Customizations {
    
    /**
     * 初始化
     */
    public static function init() {
        // 添加自定义 body 类
        add_filter('body_class', array(__CLASS__, 'add_body_classes'));
        
        // 自定义 BuddyPress 和 bbPress 模板
        add_filter('bp_locate_template', array(__CLASS__, 'locate_bp_template'), 10, 2);
        add_filter('bbp_get_template_part', array(__CLASS__, 'locate_bbp_template'), 10, 2);
        
        // 添加自定义样式
        add_action('wp_head', array(__CLASS__, 'add_custom_styles'));

        // 自动激活新注册用户（跳过邮件验证/管理员审核）
        add_filter('bp_core_signup_send_activation_key', '__return_false');
        add_action('bp_core_signup_user', array(__CLASS__, 'auto_activate_user'), 10, 5);
    }
    
    /**
     * 自动激活新注册用户
     * 
     * 允许用户注册后直接登录，无需管理员审核或邮件激活
     */
    public static function auto_activate_user($user_id, $user_login, $user_password, $user_email, $usermeta) {
        // 如果 BuddyPress 的激活系统已启用，我们手动完成激活
        if (function_exists('bp_core_activate_signup')) {
            global $wpdb;
            
            // 查找 activation key
            $key = $wpdb->get_var($wpdb->prepare("SELECT activation_key FROM {$wpdb->base_prefix}signups WHERE user_email = %s", $user_email));
            
            if ($key) {
                // 执行激活，这会创建 wp_users 记录
                $user_id = bp_core_activate_signup($key);
                
                // 如果激活成功（返回了用户ID），则自动登录并跳转
                if ($user_id && !is_wp_error($user_id)) {
                    // 自动登录
                    $creds = array(
                        'user_login'    => $user_login,
                        'user_password' => $user_password,
                        'remember'      => true
                    );
                    
                    $user = wp_signon($creds, false);
                    
                    if (!is_wp_error($user)) {
                        // 设置当前用户（为了安全起见，虽然重定向会处理）
                        wp_set_current_user($user->ID);
                        
                        // 重定向到首页或个人中心，跳过默认的"请激活"页面
                        wp_redirect(home_url());
                        exit;
                    }
                }
            }
        }
    }
    
    /**
     * 添加 body 类
     */
    public static function add_body_classes($classes) {
        if (function_exists('bp_is_activity_component') && bp_is_activity_component()) {
            $classes[] = 'musicalbum-activity-page';
        }
        
        if (function_exists('bbp_is_forum') && bbp_is_forum()) {
            $classes[] = 'musicalbum-forum-page';
        }
        
        return $classes;
    }
    
    /**
     * 定位 BuddyPress 模板
     */
    public static function locate_bp_template($template, $template_name) {
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/buddypress/' . $template_name;
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }
    
    /**
     * 定位 bbPress 模板
     */
    public static function locate_bbp_template($templates, $slug) {
        $plugin_template = plugin_dir_path(__FILE__) . '../templates/bbpress/' . $slug . '.php';
        
        if (file_exists($plugin_template)) {
            return array($plugin_template);
        }
        
        return $templates;
    }
    
    /**
     * 添加自定义样式
     */
    public static function add_custom_styles() {
        ?>
        <style>
        .musicalbum-viewing-share {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .musicalbum-viewing-share h4 {
            margin-top: 0;
        }
        
        .musicalbum-share-form textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .share-options {
            margin-bottom: 10px;
        }
        
        .share-options label {
            display: block;
            margin-bottom: 5px;
        }
        
        .share-message {
            margin-top: 10px;
            padding: 10px;
            border-radius: 3px;
        }
        
        .share-message.success {
            background: #d4edda;
            color: #155724;
        }
        
        .share-message.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .musicalbum-resource-library .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .resource-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .resource-item h4 {
            margin-top: 0;
        }
        
        .resource-meta {
            margin: 10px 0;
            font-size: 0.9em;
            color: #666;
        }
        
        .musicalbum-knowledge-base .knowledge-list {
            list-style: none;
            padding: 0;
        }
        
        .musicalbum-knowledge-base .knowledge-list li {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .knowledge-excerpt {
            margin-top: 10px;
            color: #666;
        }
        </style>
        <?php
    }
}

