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

