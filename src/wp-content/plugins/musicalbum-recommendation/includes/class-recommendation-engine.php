<?php
/**
 * 功能：
 * 根据内容标签 + 用户行为推荐 post 类型文章
 * 推荐为空时自动 fallback
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Musicalbum_Recommendation_Engine {

    public function get_recommended_posts( $user_id, $limit = 6, $fallback = 'latest' ) {

        $tag_ids = $this->collect_user_interest_tags( $user_id );

        if ( ! empty( $tag_ids ) ) {
            $query = new WP_Query(array(
                'post_type'      => 'post',
                'posts_per_page' => $limit,
                'tag__in'        => $tag_ids,
                'post_status'    => 'publish',
                'orderby'        => 'date',
                'order'          => 'DESC'
            ));

            if ( $query->have_posts() ) {
                return $query->posts;
            }
        }

        // fallback：最新文章
        return $this->fallback_latest_posts( $limit );
    }

    /**
     * 从用户行为中提取兴趣标签
     */
    private function collect_user_interest_tags( $user_id ) {

        if ( ! $user_id ) return array();

        $viewed_posts = get_user_meta( $user_id, '_musicalbum_viewed_posts', true );
        if ( ! is_array( $viewed_posts ) ) return array();

        $tag_ids = array();

        foreach ( $viewed_posts as $post_id ) {
            $tags = wp_get_post_tags( $post_id );
            foreach ( $tags as $tag ) {
                $tag_ids[] = $tag->term_id;
            }
        }

        return array_unique( $tag_ids );
    }

    private function fallback_latest_posts( $limit ) {
        return get_posts(array(
            'post_type'      => 'post',
            'posts_per_page' => $limit,
            'post_status'    => 'publish'
        ));
    }
}
