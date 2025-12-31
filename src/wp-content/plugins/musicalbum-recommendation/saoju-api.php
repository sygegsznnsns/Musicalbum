<?php
/**
 * saoju-api.php
 * 功能：封装 y.saoju.net 的音乐剧数据接口（演员 → 剧目完整链路）
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

    return json_decode( wp_remote_retrieve_body( $response ), true );
}

/**
 * ========
 * 演员 → 音乐剧（核心方法）
 * ========
 *
 * @param string $actor_name
 * @return array 音乐剧数组
 */
function msr_get_musicals_by_actor_name( $actor_name ) {

    // 1. 找演员 ID
    $artists = msr_saoju_get( 'artist/' );
    if ( empty( $artists ) ) {
        return [];
    }

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

    // 2. 找该演员参与的角色 ID
    $casts = msr_saoju_get( 'musicalcast/' );
    if ( empty( $casts ) ) {
        return [];
    }

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
    if ( empty( $musical_ids ) ) {
        return [];
    }

    // 4. 音乐剧 ID → 音乐剧信息
    $musicals = msr_saoju_get( 'musical/' );
    $results = [];

    foreach ( $musicals as $musical ) {
        if ( in_array( $musical['pk'], $musical_ids, true ) ) {
            $results[] = [
                'id'   => $musical['pk'],
                'name' => $musical['fields']['name'],
            ];
        }
    }

    return $results;
}
