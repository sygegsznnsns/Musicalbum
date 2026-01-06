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
        
        // 在资源详情页自动插入预览内容
        add_filter('the_content', array(__CLASS__, 'prepend_resource_preview'));
    }
    
    /**
     * 在资源详情页自动插入预览内容
     */
    public static function prepend_resource_preview($content) {
        // 只在主循环中的单一资源页面处理
        if (!is_singular(self::RESOURCE_CPT) || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $post_id = get_the_ID();
        $file_url = get_post_meta($post_id, '_resource_url', true);
        $file_type = get_post_meta($post_id, '_resource_type', true);
        
        if (!$file_url) {
            return $content;
        }
        
        $preview_html = '<div class="musicalbum-resource-preview" style="margin-bottom: 2rem;">';
        
        switch ($file_type) {
            case 'image':
                $preview_html .= sprintf('<img src="%s" alt="%s" style="max-width:100%%;height:auto;border-radius:8px;">', esc_url($file_url), esc_attr(get_the_title()));
                break;
                
            case 'video':
                $preview_html .= sprintf('<video controls style="width:100%%;border-radius:8px;"><source src="%s">您的浏览器不支持视频标签。</video>', esc_url($file_url));
                break;
                
            case 'audio':
                $preview_html .= sprintf('<audio controls style="width:100%%;"><source src="%s">您的浏览器不支持音频标签。</audio>', esc_url($file_url));
                break;
                
            case 'document':
            default:
                // 对于文档，尝试使用 PDF 嵌入（如果是 PDF）或 Google Docs 预览
                $ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    $preview_html .= sprintf('<iframe src="%s" width="100%%" height="600px" style="border:none;"></iframe>', esc_url($file_url));
                } else {
                    $preview_html .= sprintf('<a href="%s" class="button musicalbum-btn" target="_blank">预览/下载文件</a>', esc_url($file_url));
                }
                break;
        }
        
        $preview_html .= '</div>';
        
        return $preview_html . $content;
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
        // 使用与前端一致的 nonce action ('wp_rest')
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
            wp_send_json_error(array('message' => '安全验证失败：Nonce 不匹配'));
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
            
            <?php if (is_user_logged_in()) : ?>
                <div class="resource-upload-container">
                    <div class="resource-upload-toggle">
                        <button type="button" class="musicalbum-btn" onclick="jQuery('#musicalbum-resource-upload-form').slideToggle()">上传新资源</button>
                    </div>
                    
                    <form id="musicalbum-resource-upload-form" class="musicalbum-form" style="display:none;" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="resource-title">标题 *</label>
                            <input type="text" id="resource-title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="resource-desc">描述</label>
                            <textarea id="resource-desc" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="resource-file">文件 *</label>
                            <input type="file" id="resource-file" name="file" required>
                            <p class="description">支持格式：PDF, Doc, Zip, 图片等</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="resource-type">类型</label>
                            <select id="resource-type" name="type">
                                <option value="document">文档</option>
                                <option value="image">图片</option>
                                <option value="video">视频</option>
                                <option value="audio">音频</option>
                                <option value="other">其他</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="musicalbum-btn">确认上传</button>
                        </div>
                        
                        <div class="upload-message"></div>
                    </form>
                </div>
            <?php endif; ?>
            
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
        $file_type = strtolower(pathinfo($resource_url, PATHINFO_EXTENSION));
        
        // 判断是否为可直接预览的内容（图片、视频、音频）
        $is_previewable = in_array($resource_type, array('image', 'video', 'audio')) || in_array($file_type, array('jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'wav'));
        
        // 构建点击链接：如果可预览，则指向详情页；否则根据需求决定是否指向详情页（用户说“点进标题后直接加载在页面内”，意味着还是要进详情页）
        // 其实无论是否预览，进详情页都是最合理的，因为详情页已经实现了预览功能。
        // 但用户说“我不要预览/下载按钮”，指的是列表页上的按钮吗？
        // 用户说“点进标题后直接加载在页面内”，这正是详情页的功能。
        // 关键是列表页不应该显眼地放“下载”按钮，而是引导点击标题。
        
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
            <!-- 移除列表页的下载按钮，引导用户点击标题进入详情页预览 -->
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

