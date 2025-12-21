<?php
/*
Template Name: Musicalbum Community Center
Description: 社区交流中心：论坛、活动流、资源库、知识库
*/
/**
 * 社区功能页面模板
 * 使用短码渲染功能模块：
 * - [musicalbum_forum]：显示论坛内容
 * - [musicalbum_user_activity]：显示用户活动
 * - [musicalbum_resource_library]：显示资源库
 * - [musicalbum_knowledge_base]：显示知识库
 */
defined('ABSPATH') || exit;
get_header();
?>
<main id="primary" class="site-main" style="padding:2rem;">
  
  <!-- 论坛区域 -->
  <section class="community-section forum-section">
    <h2>观演交流论坛</h2>
    <?php 
    if (shortcode_exists('musicalbum_forum')) {
        // 显示观演记录论坛，如果没有指定forum_id，会使用自动创建的论坛
        echo do_shortcode('[musicalbum_forum limit="10"]');
    } else {
        echo '<p>社区插件未启用或短码未注册。</p>';
    }
    ?>
  </section>
  
  <hr />
  
  <!-- 用户活动区域 -->
  <section class="community-section activity-section">
    <h2>社区动态</h2>
    <?php 
    if (shortcode_exists('musicalbum_user_activity')) {
        // 显示当前用户的活动，如果未登录则显示提示
        if (is_user_logged_in()) {
            echo do_shortcode('[musicalbum_user_activity limit="10"]');
        } else {
            echo '<p>请先登录以查看社区动态。</p>';
        }
    } else {
        echo '<p>社区插件未启用或短码未注册。</p>';
    }
    ?>
  </section>
  
  <hr />
  
  <!-- 资源库区域 -->
  <section class="community-section resource-section">
    <h2>共享资源</h2>
    <?php 
    if (shortcode_exists('musicalbum_resource_library')) {
        echo do_shortcode('[musicalbum_resource_library limit="12"]');
    } else {
        echo '<p>社区插件未启用或短码未注册。</p>';
    }
    ?>
  </section>
  
  <hr />
  
  <!-- 知识库区域 -->
  <section class="community-section knowledge-section">
    <h2>知识库</h2>
    <?php 
    if (shortcode_exists('musicalbum_knowledge_base')) {
        echo do_shortcode('[musicalbum_knowledge_base limit="10"]');
    } else {
        echo '<p>社区插件未启用或短码未注册。</p>';
    }
    ?>
  </section>
  
</main>
<?php get_footer(); ?>

