<?php
/*
Template Name: Musicalbum Viewing Center
Description: 观演记录中心：录入表单 + 我的观演列表
*/
/**
 * 使用短码渲染功能模块：
 * - [musicalbum_viewing_form]：前端录入观演记录表单（依赖 ACF）
 * - [musicalbum_profile_viewings]：当前登录用户的观演记录列表
 * 已加入 shortcode_exists 守卫，插件未启用时显示友好提示。
 */
defined('ABSPATH') || exit;
get_header();
?>
<main id="primary" class="site-main" style="padding:2rem;">
  <section>
    <?php echo shortcode_exists('musicalbum_viewing_form') ? do_shortcode('[musicalbum_viewing_form]') : esc_html__('短码未注册或插件未启用', 'musicalbum-child'); ?>
  </section>
  <hr />
  <section>
    <h2>我的观演记录</h2>
    <?php echo shortcode_exists('musicalbum_profile_viewings') ? do_shortcode('[musicalbum_profile_viewings]') : esc_html__('短码未注册或插件未启用', 'musicalbum-child'); ?>
  </section>
  <hr />
  <section>
    <?php echo shortcode_exists('musicalbum_recent_viewings') ? do_shortcode('[musicalbum_recent_viewings]') : esc_html__('短码未注册或插件未启用', 'musicalbum-child'); ?>
  </section>
</main>
<?php get_footer(); ?>
