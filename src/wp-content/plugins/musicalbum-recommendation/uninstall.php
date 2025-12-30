<?php
/**
 * uninstall.php
 * 功能：插件卸载时清理不感兴趣数据
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'msr_not_interested'"
);
