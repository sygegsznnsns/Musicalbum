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
