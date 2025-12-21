<?php
/**
 * 资源分享类
 * 
 * 处理文件上传和资源分享功能
 */

defined('ABSPATH') || exit;

class Musicalbum_Resource_Sharing {
    
    const RESOURCE_CPT = 'musicalbum_resource';
    
    /**
     * 初始化
     */
    public static function init() {
        // 注册资源 CPT
        add_action('init', array(__CLASS__, 'register_resource_cpt'));
        
        // 添加上传处理
        add_action('wp_ajax_musicalbum_upload_resource', array(__CLASS__, 'handle_upload'));
        
        // 集成到 BuddyPress 活动流
        add_action('bp_activity_posted_update', array(__CLASS__, 'sync_to_activity'), 10, 3);
    }
    
    /**
     * 注册资源自定义文章类型
     */
    public static function register_resource_cpt() {
        register_post_type(self::RESOURCE_CPT, array(
            'label' => '共享资源',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'author'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'resources'),
            'menu_icon' => 'dashicons-share',
            'labels' => array(
                'name' => '共享资源',
                'singular_name' => '资源',
                'add_new' => '添加资源',
                'add_new_item' => '添加新资源',
                'edit_item' => '编辑资源',
                'new_item' => '新资源',
                'view_item' => '查看资源',
                'search_items' => '搜索资源',
                'not_found' => '未找到资源',
                'not_found_in_trash' => '回收站中未找到资源',
            ),
        ));
        
        // 注册资源分类
        register_taxonomy('resource_category', self::RESOURCE_CPT, array(
            'label' => '资源分类',
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'resource-category'),
        ));
        
        // 注册资源标签
        register_taxonomy('resource_tag', self::RESOURCE_CPT, array(
            'label' => '资源标签',
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'resource-tag'),
        ));
    }
    
    /**
     * 处理文件上传
     */
    public static function handle_upload() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'musicalbum_upload')) {
            wp_send_json_error(array('message' => '安全验证失败'));
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => '请先登录'));
        }
        
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $uploadedfile = $_FILES['file'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // 创建资源文章
            $resource_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($_POST['title'] ?? basename($movefile['file'])),
                'post_content' => sanitize_textarea_field($_POST['description'] ?? ''),
                'post_type' => self::RESOURCE_CPT,
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ));
            
            if ($resource_id && !is_wp_error($resource_id)) {
                // 保存文件URL
                update_post_meta($resource_id, '_resource_url', $movefile['url']);
                update_post_meta($resource_id, '_resource_file', $movefile['file']);
                update_post_meta($resource_id, '_resource_type', sanitize_text_field($_POST['type'] ?? 'file'));
                
                // 设置分类
                if (!empty($_POST['category'])) {
                    wp_set_post_terms($resource_id, array(intval($_POST['category'])), 'resource_category');
                }
                
                // 设置标签
                if (!empty($_POST['tags'])) {
                    $tags = array_map('trim', explode(',', sanitize_text_field($_POST['tags'])));
                    wp_set_post_terms($resource_id, $tags, 'resource_tag');
                }
                
                // 保存到数据库表
                global $wpdb;
                $table = $wpdb->prefix . 'musicalbum_resources';
                $wpdb->insert($table, array(
                    'post_id' => $resource_id,
                    'user_id' => get_current_user_id(),
                    'resource_type' => sanitize_text_field($_POST['type'] ?? 'file'),
                    'resource_url' => $movefile['url'],
                    'created_at' => current_time('mysql'),
                ));
                
                wp_send_json_success(array(
                    'message' => '上传成功',
                    'resource_id' => $resource_id,
                    'url' => get_permalink($resource_id),
                ));
            } else {
                wp_send_json_error(array('message' => '创建资源失败'));
            }
        } else {
            wp_send_json_error(array('message' => $movefile['error'] ?? '上传失败'));
        }
    }
    
    /**
     * 同步到 BuddyPress 活动流
     */
    public static function sync_to_activity($content, $user_id, $activity_id) {
        // 如果内容包含资源链接，可以在这里处理
        // 这个功能可以根据需要扩展
    }
    
    /**
     * 渲染资源库短码
     */
    public static function render_library_shortcode($atts) {
        $limit = intval($atts['limit']);
        $category = sanitize_text_field($atts['category']);
        
        $args = array(
            'post_type' => self::RESOURCE_CPT,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
        );
        
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'resource_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        $resources = get_posts($args);
        
        ob_start();
        ?>
        <div class="musicalbum-resource-library">
            <h3>资源库</h3>
            <?php if ($resources) : ?>
                <div class="resource-grid">
                    <?php foreach ($resources as $resource) : ?>
                        <?php echo self::render_resource_item($resource); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p>暂无资源。</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * 渲染单个资源项
     */
    public static function render_resource_item($resource) {
        $resource_url = get_post_meta($resource->ID, '_resource_url', true);
        $resource_type = get_post_meta($resource->ID, '_resource_type', true);
        
        ob_start();
        ?>
        <div class="resource-item">
            <h4><a href="<?php echo esc_url(get_permalink($resource->ID)); ?>">
                <?php echo esc_html($resource->post_title); ?>
            </a></h4>
            <p class="resource-description"><?php echo esc_html(wp_trim_words($resource->post_content, 20)); ?></p>
            <div class="resource-meta">
                <span class="resource-type"><?php echo esc_html($resource_type); ?></span>
                <span class="resource-date"><?php echo esc_html(get_the_date('', $resource->ID)); ?></span>
            </div>
            <?php if ($resource_url) : ?>
                <a href="<?php echo esc_url($resource_url); ?>" class="resource-download" target="_blank">下载</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * REST API: 获取资源列表
     */
    public static function get_resources($request) {
        $limit = intval($request['per_page'] ?? 12);
        $category = sanitize_text_field($request['category'] ?? '');
        $page = intval($request['page'] ?? 1);
        
        $args = array(
            'post_type' => self::RESOURCE_CPT,
            'posts_per_page' => $limit,
            'paged' => $page,
            'post_status' => 'publish',
        );
        
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'resource_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        $query = new WP_Query($args);
        $resources = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resource_id = get_the_ID();
                $resources[] = array(
                    'id' => $resource_id,
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'url' => get_permalink(),
                    'resource_url' => get_post_meta($resource_id, '_resource_url', true),
                    'resource_type' => get_post_meta($resource_id, '_resource_type', true),
                    'author' => get_the_author(),
                    'date' => get_the_date('c'),
                );
            }
            wp_reset_postdata();
        }
        
        return array(
            'resources' => $resources,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        );
    }
}

