<?php
/**
 * 观演记录分享表单模板
 * 
 * @var int $viewing_id 观演记录ID
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="musicalbum-viewing-share">
    <h4>分享到社区</h4>
    <form class="musicalbum-share-form" data-viewing-id="<?php echo esc_attr($viewing_id); ?>">
        <?php wp_nonce_field('musicalbum_share', 'nonce'); ?>
        <textarea name="message" placeholder="添加分享说明（可选）" rows="3"></textarea>
        <div class="share-options">
            <?php if (class_exists('bbPress')) : ?>
            <label>
                <input type="checkbox" name="share_to_forum" value="1" checked>
                分享到论坛
            </label>
            <?php endif; ?>
            <?php if (function_exists('buddypress')) : ?>
            <label>
                <input type="checkbox" name="share_to_activity" value="1" checked>
                分享到活动流
            </label>
            <?php endif; ?>
        </div>
        <button type="submit" class="button button-primary">分享</button>
        <div class="share-message" style="display: none;"></div>
    </form>
</div>

