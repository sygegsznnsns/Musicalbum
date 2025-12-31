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
    $response = wp_remote_get( SAOJU_API_BASE . $endpoint, [ 'timeout' => 15 ] );
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
    $end = date( 'Y-m-d', strtotime( "+{$days} days" ) );
    $endpoint = 'search_musical_show/?musical=' . urlencode( $musical_name ) . "&begin_date={$begin}&end_date={$end}";
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
 * 基于用户关注演员，推荐相关音乐剧（按演员分组）
 *
 * @param int $user_id
 * @return array
 */
function musicalbum_recommend_by_favorite_actors( $user_id ) {

    $actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );

    if ( empty( $actors ) || ! is_array( $actors ) ) {
        return [];
    }

    $results = [];

    foreach ( $actors as $actor_name ) {

        $musicals = msr_get_musicals_by_actor_name( $actor_name );

        if ( empty( $musicals ) ) {
            continue;
        }

        $results[ $actor_name ] = $musicals;
    }

    return $results;
}

/**
 * 根据演员名字，查询该演员参与的所有音乐剧
 *
 * 数据链路：
 * artist.name → artist.pk
 * artist.pk → musicalcast.artist
 * musicalcast.role → role.pk
 * role.musical → musical.pk
 * musical.pk → musical.name
 *
 * @param string $actor_name
 * @return array 音乐剧数组，每项包含 musical_id + musical_name
 */
function msr_get_musicals_by_actor_name( $actor_name ) {

    if ( empty( $actor_name ) ) {
        return [];
    }

    /**
     * Step 1：获取演员 ID
     */
    $artists = msr_saoju_get( 'artist/' );
    $actor_id = null;

    foreach ( $artists as $item ) {
        if ( isset( $item['fields']['name'] ) && $item['fields']['name'] === $actor_name ) {
            $actor_id = $item['pk'];
            break;
        }
    }

    if ( ! $actor_id ) {
        return [];
    }

    /**
     * Step 2：获取该演员参与的角色 ID 列表
     */
    $casts = msr_saoju_get( 'musicalcast/' );
    $role_ids = [];

    foreach ( $casts as $item ) {
        if ( isset( $item['fields']['artist'] ) && intval( $item['fields']['artist'] ) === intval( $actor_id ) ) {
            $role_ids[] = $item['fields']['role'];
        }
    }

    $role_ids = array_unique( $role_ids );

    if ( empty( $role_ids ) ) {
        return [];
    }

    /**
     * Step 3：通过角色获取音乐剧 ID
     */
    $roles = msr_saoju_get( 'role/' );
    $musical_ids = [];

    foreach ( $roles as $item ) {
        if ( in_array( $item['pk'], $role_ids, true ) ) {
            $musical_ids[] = $item['fields']['musical'];
        }
    }

    $musical_ids = array_unique( $musical_ids );

    if ( empty( $musical_ids ) ) {
        return [];
    }

    /**
     * Step 4：获取音乐剧名称
     */
    $musicals = msr_saoju_get( 'musical/' );
    $results = [];

    foreach ( $musicals as $item ) {
        if ( in_array( $item['pk'], $musical_ids, true ) ) {
            $results[] = [
                'musical_id'   => $item['pk'],
                'musical_name' => $item['fields']['name'],
            ];
        }
    }

    return $results;
}
