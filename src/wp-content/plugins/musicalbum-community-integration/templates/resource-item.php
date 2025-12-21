<?php
/**
 * 资源项模板
 * 
 * @var WP_Post $resource 资源文章对象
 */

if (!defined('ABSPATH')) {
    exit;
}

$resource_url = get_post_meta($resource->ID, '_resource_url', true);
$resource_type = get_post_meta($resource->ID, '_resource_type', true);
?>

<div class="resource-item">
    <h4>
        <a href="<?php echo esc_url(get_permalink($resource->ID)); ?>">
            <?php echo esc_html($resource->post_title); ?>
        </a>
    </h4>
    
    <?php if ($resource->post_content) : ?>
    <p class="resource-description">
        <?php echo esc_html(wp_trim_words($resource->post_content, 20)); ?>
    </p>
    <?php endif; ?>
    
    <div class="resource-meta">
        <?php if ($resource_type) : ?>
        <span class="resource-type"><?php echo esc_html($resource_type); ?></span>
        <?php endif; ?>
        <span class="resource-date"><?php echo esc_html(get_the_date('', $resource->ID)); ?></span>
    </div>
    
    <?php if ($resource_url) : ?>
    <a href="<?php echo esc_url($resource_url); ?>" class="resource-download" target="_blank" rel="noopener">
        下载资源
    </a>
    <?php endif; ?>
    
    <div class="resource-author">
        <small>作者：<?php echo esc_html(get_the_author_meta('display_name', $resource->post_author)); ?></small>
    </div>
</div>

