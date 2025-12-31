<?php
/**
 * saoju-api.php
 * 功能：封装 y.saoju.net 的音乐剧数据接口
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SAOJU_API_BASE', 'https://y.saoju.net/yyj/api/' );

/**
 * 通用 GET 请求
 */
function msr_saoju_get( $endpoint ) {
    $response = wp_remote_get( SAOJU_API_BASE . $endpoint, [
        'timeout' => 15
    ] );

    if ( is_wp_error( $response ) ) {
        return [];
    }

    $body = wp_remote_retrieve_body( $response );
    return json_decode( $body, true );
}

/**
 * 获取全部音乐剧
 */
function msr_get_all_musicals() {
    return msr_saoju_get( 'musical/' );
}

/**
 * 获取全部卡司信息
 */
function msr_get_all_musical_cast() {
    return msr_saoju_get( 'musicalcast/' );
}

/**
 * 查询某音乐剧在时间范围内是否有演出
 */
function msr_has_recent_show( $musical_name, $days = 60 ) {
    $begin = date( 'Y-m-d', strtotime( "-{$days} days" ) );
    $end   = date( 'Y-m-d', strtotime( "+{$days} days" ) );

    $endpoint = 'search_musical_show/?musical=' . urlencode( $musical_name )
        . "&begin_date={$begin}&end_date={$end}";

    $data = msr_saoju_get( $endpoint );

    return ! empty( $data['show_list'] );
}

/**
 * 获取某一天的演出（用于 Trending）
 */
function msr_get_day_shows( $date ) {
    return msr_saoju_get( 'search_day/?date=' . $date );
}

/**
 * 根据演员名查询相关演出
 */
function musicalbum_saoju_search_by_actor( $actor_name ) {
    $url = 'https://y.saoju.net/yyj/api/search_day/?date=' . date('Y-m-d');

    $response = wp_remote_get( $url );
    if ( is_wp_error( $response ) ) {
        return array();
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $data['show_list'] ) ) {
        return array();
    }

    $results = array();

    foreach ( $data['show_list'] as $show ) {
        if ( empty( $show['cast'] ) ) {
            continue;
        }

        foreach ( $show['cast'] as $cast ) {
            if ( isset( $cast['artist'] ) && $cast['artist'] === $actor_name ) {
                $results[] = $show;
                break;
            }
        }
    }

    return $results;
}

/**
 * ===============================
 * 演员名 → 音乐剧名列表
 * 只给“关注演员推荐”用
 * ===============================
 */
function msr_saoju_get_musicals_by_actor_name( $actor_name ) {

    // 1. 演员 ID
    $artists = msr_saoju_get( 'artist/' );
    $artist_id = null;

    foreach ( $artists as $artist ) {
        if ( isset( $artist['fields']['name'] ) && $artist['fields']['name'] === $actor_name ) {
            $artist_id = $artist['pk'];
            break;
        }
    }

    if ( ! $artist_id ) {
        return [];
    }

    // 2. 演员 → 角色
    $casts = msr_saoju_get( 'musicalcast/' );
    $role_ids = [];

    foreach ( $casts as $cast ) {
        if ( isset( $cast['fields']['artist'] ) && $cast['fields']['artist'] == $artist_id ) {
            $role_ids[] = $cast['fields']['role'];
        }
    }

    if ( empty( $role_ids ) ) {
        return [];
    }

    // 3. 角色 → 音乐剧 ID
    $roles = msr_saoju_get( 'role/' );
    $musical_ids = [];

    foreach ( $roles as $role ) {
        if ( in_array( $role['pk'], $role_ids, true ) ) {
            $musical_ids[] = $role['fields']['musical'];
        }
    }

    $musical_ids = array_unique( $musical_ids );

    // 4. 音乐剧 ID → 名称
    $musicals = msr_saoju_get( 'musical/' );
    $names = [];

    foreach ( $musicals as $musical ) {
        if ( in_array( $musical['pk'], $musical_ids, true ) ) {
            $names[] = $musical['fields']['name'];
        }
    }

    return $names;
}
