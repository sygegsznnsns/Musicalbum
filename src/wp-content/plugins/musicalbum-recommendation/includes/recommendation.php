<?php
/**
 * recommendation.php
 * 功能：生成三类音乐剧推荐结果
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 基于观演历史推荐
 */
function msr_recommend_by_history( $user_id ) {
    $history = musicalbum_get_user_viewing_history( $user_id );
    $not_interested = msr_get_not_interested_list( $user_id );

    $actor_count = [];

    $casts = msr_get_all_musical_cast();

    foreach ( $history as $item ) {
        foreach ( $casts as $cast ) {
            if ( $cast['fields']['musical_name'] === $item['musical'] ) {
                $actor = $cast['fields']['artist_name'];
                $actor_count[ $actor ] = ( $actor_count[ $actor ] ?? 0 ) + 1;
            }
        }
    }

    arsort( $actor_count );
    $top_actors = array_slice( array_keys( $actor_count ), 0, 3 );

    return msr_recommend_by_actors( $top_actors, $user_id, '基于你的观演历史推荐' );
}

/**
 * 演员关联推荐
 */
function msr_recommend_by_actors( $actors, $user_id, $reason_prefix = '演员关联推荐' ) {
    $casts = msr_get_all_musical_cast();
    $history = musicalbum_get_user_viewing_history( $user_id );
    $viewed = array_column( $history, 'musical' );
    $not_interested = msr_get_not_interested_list( $user_id );

    $results = [];

    foreach ( $casts as $cast ) {
        if ( in_array( $cast['fields']['artist_name'], $actors, true ) ) {
            $musical = $cast['fields']['musical_name'];

            if ( in_array( $musical, $viewed, true ) ) continue;
            if ( in_array( $musical, $not_interested, true ) ) continue;
            if ( ! msr_has_recent_show( $musical ) ) continue;

            $results[ $musical ] = [
                'musical' => $musical,
                'reason'  => "{$reason_prefix}（演员：{$cast['fields']['artist_name']}）"
            ];
        }
    }

    return array_values( $results );
}

/**
 * Trending 推荐
 */
function msr_recommend_trending( $user_id ) {
    $not_interested = msr_get_not_interested_list( $user_id );
    $count = [];

    for ( $i = 0; $i < 7; $i++ ) {
        $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
        $data = msr_get_day_shows( $date );

        if ( empty( $data['show_list'] ) ) continue;

        foreach ( $data['show_list'] as $show ) {
            $musical = $show['musical'];
            if ( in_array( $musical, $not_interested, true ) ) continue;
            $count[ $musical ] = ( $count[ $musical ] ?? 0 ) + 1;
        }
    }

    arsort( $count );

    $results = [];
    foreach ( array_keys( $count ) as $musical ) {
        if ( msr_has_recent_show( $musical ) ) {
            $results[] = [
                'musical' => $musical,
                'reason'  => '近期热门演出'
            ];
        }
    }

    return array_slice( $results, 0, 5 );
}
