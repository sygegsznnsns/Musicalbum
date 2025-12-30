<?php
/**
 * actor-preference.php
 * 功能：
 * 1. 管理用户喜欢的演员列表（user_meta）
 * 2. 基于演员偏好，从 saoju 接口推荐相关剧目
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 获取用户喜欢的演员列表（演员名数组）
 */
function musicalbum_get_favorite_actors( $user_id ) {
    $actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    return is_array( $actors ) ? $actors : array();
}

/**
 * 添加喜欢的演员
 */
function musicalbum_add_favorite_actor( $user_id, $actor_name ) {
    $actors = musicalbum_get_favorite_actors( $user_id );

    if ( ! in_array( $actor_name, $actors, true ) ) {
        $actors[] = $actor_name;
        update_user_meta( $user_id, 'musicalbum_favorite_actors', $actors );
    }
}

/**
 * 删除喜欢的演员
 */
function musicalbum_remove_favorite_actor( $user_id, $actor_name ) {
    $actors = musicalbum_get_favorite_actors( $user_id );

    $actors = array_diff( $actors, array( $actor_name ) );
    update_user_meta( $user_id, 'musicalbum_favorite_actors', array_values( $actors ) );
}

/**
 * 基于喜欢的演员推荐剧目（通过 saoju 接口）
 */
function musicalbum_recommend_by_favorite_actors( $user_id, $limit = 10 ) {

    $actors = musicalbum_get_favorite_actors( $user_id );

    if ( empty( $actors ) ) {
        return array();
    }

    require_once plugin_dir_path( __FILE__ ) . 'saoju-api.php';

    $results = array();
    $seen = array();

    foreach ( $actors as $actor ) {

        // 通过演员名 → 查出演出（saoju 的 show / cast 体系）
        $shows = musicalbum_saoju_search_by_actor( $actor );

        foreach ( $shows as $show ) {

            $musical = $show['musical'] ?? '';

            if ( empty( $musical ) || isset( $seen[ $musical ] ) ) {
                continue;
            }

            $seen[ $musical ] = true;

            $results[] = array(
                'musical' => $musical,
                'reason'  => '你关注的演员「' . $actor . '」参演该剧',
            );

            if ( count( $results ) >= $limit ) {
                return $results;
            }
        }
    }

    return $results;
}
