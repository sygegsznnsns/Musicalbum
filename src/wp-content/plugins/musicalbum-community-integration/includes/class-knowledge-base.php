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

