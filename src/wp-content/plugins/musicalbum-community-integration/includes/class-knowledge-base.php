<?php
/**
 * 知识库类
 * 
 * 处理知识库功能
 */

defined('ABSPATH') || exit;

class Musicalbum_Knowledge_Base {
    
    const KNOWLEDGE_CPT = 'musicalbum_knowledge';
    
    /**
     * 初始化
     */
    public static function init() {
        // 注册知识库 CPT
        add_action('init', array(__CLASS__, 'register_knowledge_cpt'));
        
        // 集成到论坛分类
        add_action('init', array(__CLASS__, 'link_to_forum_category'));
        
        // 在论坛话题中添加"收录到知识库"按钮
        add_action('bbp_theme_after_topic_meta', array(__CLASS__, 'add_convert_button'));
        
        // 在论坛回复中也添加"收录到知识库"按钮
        add_action('bbp_theme_after_reply_meta', array(__CLASS__, 'add_convert_button_for_reply'));
        
        // 处理收录 AJAX 请求
        add_action('wp_ajax_musicalbum_convert_topic_to_knowledge', array(__CLASS__, 'handle_convert_ajax'));
    }
    
    /**
     * 在论坛话题中添加"收录到知识库"按钮 (仅限管理员/编辑)
     */
    public static function add_convert_button() {
        if (!current_user_can('edit_others_posts')) {
            return;
        }
        
        $topic_id = bbp_get_topic_id();
        // 检查是否已收录
        $is_converted = get_post_meta($topic_id, '_musicalbum_converted_to_knowledge', true);
        
        if ($is_converted) {
            echo '<span class="bbp-admin-links"> | <span class="musicalbum-converted-badge" style="color:green;">✅ 已收录到知识库</span></span>';
        } else {
            echo '<span class="bbp-admin-links"> | <a href="#" class="musicalbum-convert-btn" data-type="topic" data-id="' . esc_attr($topic_id) . '" style="color:#ff6464;">📥 收录主题内容</a></span>';
        }
    }
    
    /**
     * 在论坛回复中添加"收录到知识库"按钮
     */
    public static function add_convert_button_for_reply() {
        if (!current_user_can('edit_others_posts')) {
            return;
        }
        
        $reply_id = bbp_get_reply_id();
        // 检查是否已收录
        $is_converted = get_post_meta($reply_id, '_musicalbum_converted_to_knowledge', true);
        
        if ($is_converted) {
            echo '<span class="bbp-admin-links"> | <span class="musicalbum-converted-badge" style="color:green;">✅ 已收录</span></span>';
        } else {
            echo '<span class="bbp-admin-links"> | <a href="#" class="musicalbum-convert-btn" data-type="reply" data-id="' . esc_attr($reply_id) . '" style="color:#ff6464;">📥 收录本楼回复</a></span>';
        }
    }
    
    /**
     * 处理收录 AJAX 请求
     */
    public static function handle_convert_ajax() {
        check_ajax_referer('wp_rest', 'nonce'); // 使用通用的 REST nonce
        
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error('权限不足');
        }
        
        $object_id = intval($_POST['object_id']);
        $object_type = sanitize_text_field($_POST['object_type']); // 'topic' or 'reply'
        
        if (!$object_id) {
            wp_send_json_error('无效的 ID');
        }
        
        $post_obj = get_post($object_id);
        if (!$post_obj) {
            wp_send_json_error('内容不存在');
        }
        
        // 检查是否已收录
        if (get_post_meta($object_id, '_musicalbum_converted_to_knowledge', true)) {
            wp_send_json_error('该内容已收录');
        }
        
        // 确定标题
        $title = $post_obj->post_title;
        if ($object_type === 'reply') {
            // 回复通常没有标题，或者标题是 "Reply To: ..."
            // 我们可以使用 "关于[话题]的回复 - by [作者]"
            $topic_id = bbp_get_reply_topic_id($object_id);
            $topic_title = get_the_title($topic_id);
            $author_name = get_the_author_meta('display_name', $post_obj->post_author);
            $title = "回复：{$topic_title} (来自 {$author_name})";
        }
        
        // 创建知识库文章
        $knowledge_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $post_obj->post_content . "\n\n<!-- 原文来自论坛: " . get_permalink($object_id) . " -->",
            'post_type' => self::KNOWLEDGE_CPT,
            'post_status' => 'publish',
            'post_author' => $post_obj->post_author,
        ));
        
        if ($knowledge_id && !is_wp_error($knowledge_id)) {
            // 标记已收录
            update_post_meta($object_id, '_musicalbum_converted_to_knowledge', $knowledge_id);
            // 双向链接
            update_post_meta($knowledge_id, '_source_forum_post_id', $object_id);
            
            wp_send_json_success(array(
                'message' => '收录成功！',
                'url' => get_permalink($knowledge_id)
            ));
        } else {
            wp_send_json_error('创建文章失败');
        }
    }
    
    /**
     * 注册知识库自定义文章类型
     */
    public static function register_knowledge_cpt() {
        register_post_type(self::KNOWLEDGE_CPT, array(
            'label' => '知识库',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'author', 'comments'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'knowledge'),
            'menu_icon' => 'dashicons-book-alt',
            'labels' => array(
                'name' => '知识库',
                'singular_name' => '知识文章',
                'add_new' => '添加文章',
                'add_new_item' => '添加新文章',
                'edit_item' => '编辑文章',
                'new_item' => '新文章',
                'view_item' => '查看文章',
                'search_items' => '搜索文章',
                'not_found' => '未找到文章',
                'not_found_in_trash' => '回收站中未找到文章',
            ),
        ));
        
        // 注册知识库分类
        register_taxonomy('knowledge_category', self::KNOWLEDGE_CPT, array(
            'label' => '知识分类',
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'knowledge-category'),
        ));
        
        // 注册知识库标签
        register_taxonomy('knowledge_tag', self::KNOWLEDGE_CPT, array(
            'label' => '知识标签',
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'knowledge-tag'),
        ));
    }
    
    /**
     * 链接到论坛分类
     */
    public static function link_to_forum_category() {
        // 这个功能可以在需要时扩展
        // 例如：在知识库文章页面显示相关的论坛讨论
    }
    
    /**
     * 渲染知识库短码
     */
    public static function render_knowledge_base_shortcode($atts) {
        $limit = intval($atts['limit']);
        $category = sanitize_text_field($atts['category']);
        
        $args = array(
            'post_type' => self::KNOWLEDGE_CPT,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
        );
        
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'knowledge_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            );
        }
        
        $knowledge_posts = get_posts($args);
        
        ob_start();
        ?>
        <div class="musicalbum-knowledge-base">
            <h3>知识库</h3>
            
            <?php if (is_user_logged_in()) : ?>
                <div class="contribution-box">
                    💡 想要贡献您的音乐剧知识？<a href="<?php echo esc_url(site_url('/forums/')); ?>">前往论坛</a> 发布草稿，管理员审核后将收录至此。
                </div>
            <?php endif; ?>
            
            <?php if ($knowledge_posts) : ?>
                <ul class="knowledge-list">
                    <?php foreach ($knowledge_posts as $post) : ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </a>
                            <span class="knowledge-date">
                                <?php echo esc_html(get_the_date('', $post->ID)); ?>
                            </span>
                            <p class="knowledge-excerpt">
                                <?php echo esc_html(wp_trim_words($post->post_content, 30)); ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>暂无知识文章。</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

