<?php
/*
Template Name: Musicalbum Community Center
Description: 社区交流中心：论坛、活动流、资源库、知识库
*/
defined('ABSPATH') || exit;
get_header();
?>
<main id="primary" class="site-main">
  
  <div class="community-container">
      
      <!-- 论坛区域 -->
      <section class="community-section forum-section">
        <h2>观演交流论坛</h2>
        <?php 
        if (shortcode_exists('musicalbum_forum')) {
            // 使用 link_to_root="true" 让链接指向论坛首页
            echo do_shortcode('[musicalbum_forum limit="5" link_to_root="true"]');
        } else {
            echo '<p>社区插件未启用。</p>';
        }
        ?>
      </section>
      <?php /*
      <!-- 用户活动区域 -->
      <section class="community-section activity-section">
        <h2>社区动态</h2>
        <?php 
        if (shortcode_exists('musicalbum_user_activity')) {
            if (is_user_logged_in()) {
                echo do_shortcode('[musicalbum_user_activity limit="5"]');
            } else {
                echo '<p>请先登录以查看社区动态。</p>';
            }
        }
        ?>
      </section>
      */ ?>
      
      <!-- 资源库区域 -->
      <section class="community-section resource-section">
        <h2>共享资源</h2>
        <?php 
        if (shortcode_exists('musicalbum_resource_library')) {
            echo do_shortcode('[musicalbum_resource_library limit="6"]');
        }
        ?>
      </section>
      
      <!-- 知识库区域 -->
      <section class="community-section knowledge-section">
        <h2>知识库</h2>
        <?php 
        if (shortcode_exists('musicalbum_knowledge_base')) {
            echo do_shortcode('[musicalbum_knowledge_base limit="5"]');
        }
        ?>
      </section>

  </div>
  
</main>
<?php get_footer(); ?>
