<?php
/**
 * Object Cache 4 everyone Uninstall
 *
 * Uninstalling Object Cache 4 everyone plugin
 *
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('uninstall.php');
}

//Delete object-cache.php
include_once('oc4-deactivation.php');

//Clean everything
oc4everyone_deactivation();

// Clear any cached data that has been removed.
wp_cache_flush();
