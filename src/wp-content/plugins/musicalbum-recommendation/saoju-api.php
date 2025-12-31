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
 * 通用 GET 请求（带缓存）
 *
 * @param string $endpoint
 * @param int    $cache_ttl 缓存秒数，默认 1 小时
 * @return array
 */
function msr_saoju_get( $endpoint, $cache_ttl = 3600 ) {

    $cache_key = 'msr_saoju_' . md5( $endpoint );
    $cached = get_transient( $cache_key );

    if ( $cached !== false ) {
        return $cached;
    }

    $response = wp_remote_get(
        SAOJU_API_BASE . $endpoint,
        [ 'timeout' => 15 ]
    );

    if ( is_wp_error( $response ) ) {
        return [];
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( is_array( $data ) ) {
        set_transient( $cache_key, $data, $cache_ttl );
    }

    return is_array( $data ) ? $data : [];
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
 * 根据演员名字，查询该演员参与的所有音乐剧（带缓存）
 *
 * 数据链路：
 * artist.name → artist.pk
 * artist.pk → musicalcast.artist
 * musicalcast.role → role.pk
 * role.musical → musical.pk
 * musical.pk → musical.name
 *
 * @param string $actor_name
 * @return array
 */
function msr_get_musicals_by_actor_name( $actor_name ) {

    if ( empty( $actor_name ) ) {
        return [];
    }

    /**
     * Step 0：结果缓存（演员维度）
     */
    $cache_key = 'msr_actor_musicals_' . md5( $actor_name );
    $cached = get_transient( $cache_key );

    if ( $cached !== false ) {
        return $cached;
    }

    /**
     * Step 1：获取演员 ID
     */
    $artists = msr_saoju_get( 'artist/' );
    $actor_id = null;

    foreach ( $artists as $item ) {
        if (
            isset( $item['fields']['name'], $item['pk'] ) &&
            $item['fields']['name'] === $actor_name
        ) {
            $actor_id = $item['pk'];
            break;
        }
    }

    if ( ! $actor_id ) {
        set_transient( $cache_key, [], 12 * HOUR_IN_SECONDS );
        return [];
    }

    /**
     * Step 2：获取该演员参与的角色 ID
     */
    $casts = msr_saoju_get( 'musicalcast/' );
    $role_ids = [];

    foreach ( $casts as $item ) {
        if (
            isset( $item['fields']['artist'], $item['fields']['role'] ) &&
            intval( $item['fields']['artist'] ) === intval( $actor_id )
        ) {
            $role_ids[] = $item['fields']['role'];
        }
    }

    $role_ids = array_unique( $role_ids );

    if ( empty( $role_ids ) ) {
        set_transient( $cache_key, [], 12 * HOUR_IN_SECONDS );
        return [];
    }

    /**
     * Step 3：通过角色获取音乐剧 ID
     */
    $roles = msr_saoju_get( 'role/' );
    $musical_ids = [];

    foreach ( $roles as $item ) {
        if (
            isset( $item['pk'], $item['fields']['musical'] ) &&
            in_array( $item['pk'], $role_ids, true )
        ) {
            $musical_ids[] = $item['fields']['musical'];
        }
    }

    $musical_ids = array_unique( $musical_ids );

    if ( empty( $musical_ids ) ) {
        set_transient( $cache_key, [], 12 * HOUR_IN_SECONDS );
        return [];
    }

    /**
     * Step 4：获取音乐剧名称
     */
    $musicals = msr_saoju_get( 'musical/' );
    $results = [];

    foreach ( $musicals as $item ) {
        if (
            isset( $item['pk'], $item['fields']['name'] ) &&
            in_array( $item['pk'], $musical_ids, true )
        ) {
            $results[] = [
                'musical_id'   => $item['pk'],
                'musical_name' => $item['fields']['name'],
            ];
        }
    }

    /**
     * Step 5：写入缓存
     */
    set_transient( $cache_key, $results, 12 * HOUR_IN_SECONDS );

    return $results;
}

