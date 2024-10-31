<?php

/**
 * Plugin Name: Object Cache 4 everyone
 * Description: Memcached or disk backend support for the WP Object Cache. Memcached server running and PHP Memcached class needed for better performance. No configuration needed, runs automatically
 * Plugin URI: https://wordpress.org/plugins/object-cache-4-everyone
 * Author: fpuenteonline
 * Version: 2.2
 * Author URI: https://twitter.com/fpuenteonline
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 *
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

//Delete object-cache.php
include_once('oc4-deactivation.php');
register_deactivation_hook(__FILE__, 'oc4everyone_deactivation');

//First install
if (!function_exists('oc4everyone_plugins_loaded_activation')) {
    function oc4everyone_admin_notices_no_class_exists()
    {
        if (defined('OC4EVERYONE_DISABLE_DISK_CACHE') && OC4EVERYONE_DISABLE_DISK_CACHE) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Object Cache 4 everyone</strong> ' . esc_html__('needs PHP Memcached class installed for better performance.') . '</p></div>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Object Cache 4 everyone::needs PHP Memcached class installed for better performance.');
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Object Cache 4 everyone</strong> ' . esc_html__('needs PHP Memcached class installed for better performance. Running disk support instead.') . '</p></div>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Object Cache 4 everyone::needs PHP Memcached class installed for better performance. Running disk support instead.');
            }
        }
    }
    function oc4everyone_admin_notices_no_server_running()
    {
        if (defined('OC4EVERYONE_DISABLE_DISK_CACHE') && OC4EVERYONE_DISABLE_DISK_CACHE) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Object Cache 4 everyone</strong> ' . esc_html__('needs PHP Memcached class installed for better performance.') . '</p></div>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Object Cache 4 everyone::needs PHP Memcached class installed for better performance.');
            }
        } else {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>Object Cache 4 everyone</strong> ' . esc_html__('needs PHP Memcached class installed for better performance. Running disk support instead.') . '</p></div>';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Object Cache 4 everyone::needs PHP Memcached class installed for better performance. Running disk support instead.');
            }
        }
    }
    function oc4everyone_admin_notices_object()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Object Cache 4 everyone::has detected another WP Object Cache instance running. Plugin is deactivated now.');
        }
        echo '<div class="notice notice-error is-dismissible"><p><strong>Object Cache 4 everyone</strong> ' . esc_html__('has detected another WP Object Cache instance running. Plugin is deactivated now.') . '<br/><code>' . WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'object-cache.php' . '</code></p></div>';
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
    function oc4everyone_admin_notices_ok_disk()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Object Cache 4 everyone::running Disk. Thanks for using.');
        }
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo '<strong>Object Cache 4 everyone</strong> ' . esc_html__('running. Thanks for using.');
        echo ' ';
        echo esc_html__('Disk external object cache running');
        echo '</p></div>';

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
    function oc4everyone_admin_notices_ok_memcached()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Object Cache 4 everyone::running Memcached. Thanks for using.');
        }
        echo '<div class="notice notice-success is-dismissible"><p>';
        echo '<strong>Object Cache 4 everyone</strong> ' . esc_html__('running. Thanks for using.');
        echo ' ';
        echo esc_html__('Memcached Server running');
        echo '</p></div>';

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
    function oc4everyone_admin_init_deactivate_itself()
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Object Cache 4 everyone::oc4everyone_admin_init_deactivate_itself');
        }
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
        deactivate_plugins(plugin_basename(__FILE__), true);
    }

    function oc4everyone_plugins_loaded_activation()
    {
        if (defined('OC4EVERYONE_PREDEFINED_SERVER')) {
            return; //Nothing needed, everything works
        }

        if (!current_user_can('activate_plugins') || !is_admin()) {
            return; //Only for admin users and dashboard access
        }

        //Check object-cache.php exists
        if (file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'object-cache.php')) {
            add_action('admin_notices', 'oc4everyone_admin_notices_object', PHP_INT_MAX);
            add_action('admin_init', 'oc4everyone_admin_init_deactivate_itself', PHP_INT_MAX);
            return;
        }

        //Check Memcached class
        if (class_exists('Memcached')) {
            //Detecting first Memcached server running
            $memcached_servers =  array(
                '127.0.0.1:11211',
                '127.0.0.1:11212',
                '127.0.0.1:11213',
                '127.0.0.1:20000',
                '127.0.0.1:20001'
            );

            if (defined('OC4EVERYONE_MEMCACHED_SERVER')) {
                $memcached_servers =  array(OC4EVERYONE_MEMCACHED_SERVER);
            } else {
                //Try SG Memcached server first
                // Get the account name.
                if (function_exists('get_current_user') && get_current_user() !== '') {
                    $account_name = get_current_user();

                    // Generate the port file path.
                    $port_file_path = "/home/{$account_name}/.SGCache/cache_status";
                    if (file_exists($port_file_path) && is_readable($port_file_path)) {
                        $string = file_get_contents($port_file_path);

                        preg_match('#memcache\|\|([0-9]+)#', $string, $matches);

                        // Return empty string if there is no match.
                        if (!empty($matches[1])) {
                            //Override current list
                            $memcached_servers =  array(
                                '127.0.0.1:' . $matches[1]
                            );
                        }
                    }
                }
            }

            $found_server = '';
            foreach ($memcached_servers as $server) {
                $temp_Memcached = new Memcached();
                list($node, $port) = explode(':', $server);
                $temp_Memcached->addServer($node, $port);

                //Checks server
                $temp_Memcached->getVersion();
                if ($temp_Memcached->getResultCode() === 0) {
                    $found_server =  $server;
                    break;
                }
            }
            if ($found_server !== '') {

                //Memcached + Memcached Server running

                //Copy object-cache.php + define('OC4EVERYONE_PREDEFINED_SERVER', $server); line
                $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'object-cache-memcached-template.php');

                $template = "<?php
/**
 * Plugin Name: Object Cache 4 everyone - Memcached
 * Plugin URI: https://wordpress.org/plugins/object-cache-4-everyone
 * Author: fpuenteonline
 * Author URI: https://twitter.com/fpuenteonline
 *
 */
?>" . $template;
                $template .= '//Detected memcached server - ' . date('d/m/Y G:i:s', current_time('timestamp', 0)) . PHP_EOL;
                $template .= "define('OC4EVERYONE_PREDEFINED_SERVER', '$found_server');" . PHP_EOL;

                $template .= "if (! defined('WP_CACHE_KEY_SALT')) {" . PHP_EOL;
                global $wpdb;
                $template .= "define('WP_CACHE_KEY_SALT', '" . DB_NAME . DB_USER . $wpdb->prefix . "');" . PHP_EOL;
                $template .= "}" . PHP_EOL;

                file_put_contents(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'object-cache.php', $template);

                add_action('admin_notices', 'oc4everyone_admin_notices_ok_memcached', PHP_INT_MAX);

                //Flush cache on plugin activation
                wp_cache_flush();

                return;
            } else {
                //Memcached - Memcached Server not running
                add_action('admin_notices', 'oc4everyone_admin_notices_no_server_running', PHP_INT_MAX);
            }        
        //class_exists('Memcached')
        } else {
            add_action('admin_notices', 'oc4everyone_admin_notices_no_class_exists', PHP_INT_MAX);
        }

        if (defined('OC4EVERYONE_DISABLE_DISK_CACHE') && OC4EVERYONE_DISABLE_DISK_CACHE) {
            add_action('admin_init', 'oc4everyone_admin_init_deactivate_itself', PHP_INT_MAX);
            return;
        }

        //Copy object-cache.php + define('OC4EVERYONE_PREDEFINED_SERVER', ''); line
        $template = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'object-cache-disk-template.php');

        $template = "<?php
/**
 * Plugin Name: Object Cache 4 everyone - Disk
 * Plugin URI: https://wordpress.org/plugins/object-cache-4-everyone
 * Author: fpuenteonline
 * Author URI: https://twitter.com/fpuenteonline
 *
 */
?>" . $template;

        $template .= '//No detected memcached server - ' . date('d/m/Y G:i:s', current_time('timestamp', 0)) . PHP_EOL;
        $template .= "define('OC4EVERYONE_PREDEFINED_SERVER', '');" . PHP_EOL;

        $template .= "if (! defined('WP_CACHE_KEY_SALT')) {" . PHP_EOL;
        $template .= "define('WP_CACHE_KEY_SALT', '" . filemtime(__FILE__) . "');" . PHP_EOL;
        $template .= "}" . PHP_EOL;

        file_put_contents(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'object-cache.php', $template);

        add_action('admin_notices', 'oc4everyone_admin_notices_ok_disk', PHP_INT_MAX);

        //Flush cache on plugin activation
        wp_cache_flush();
    }
}
add_action('plugins_loaded', 'oc4everyone_plugins_loaded_activation');


function oc4everyone_add_server_info($links_array, $plugin_file_name, $plugin_data, $status)
{
    global $wp_object_cache;

    if (strpos($plugin_file_name, basename(__FILE__)) && class_exists('Memcached') && method_exists($wp_object_cache, 'getStats') && file_exists(WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'object-cache.php')) {
        //Extra check
        if(!defined('OC4EVERYONE_PREDEFINED_SERVER') || !array_key_exists(OC4EVERYONE_PREDEFINED_SERVER, $wp_object_cache->getStats())){
            return $links_array;        
        }

        $hits = $wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER]['get_hits'];
        //Extra check
        if($hits == 0) {
            return $links_array;        
        }
        $misses = $wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER]['get_misses'];
        $total = $hits + $misses;
        $found = @round((100 / $total) * $hits, 2);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Object Cache 4 everyone::Memcached Server running');
        }
        $nonce = wp_create_nonce('flush_memcached_nonce');

        $links_array[] = '<a href="' . esc_url(admin_url('admin-post.php?action=oc4flush_memcached&nonce=' . $nonce)) . '"><strong>' . esc_html__('Flush cache') . '</strong></a>' . 
            '<br/><br/>' .
            esc_html__('Memcached Server running:') . ' <strong><code style="background-color: inherit;">' . OC4EVERYONE_PREDEFINED_SERVER . '</code></strong>' . '<br/>' .
            esc_html__('Cache Hit Ratio') . ' <strong><code style="background-color: inherit;">' . $found . '%</code></strong>' . '<br/>' .
            esc_html__('Uptime:')  . ' <strong><code style="background-color: inherit;">' . secondsToHumanReadable($wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER]['uptime']) . '</strong></code>' . '<br/>' .
            esc_html__('Current Unique Items / Total Items:') . ' <strong><code style="background-color: inherit;">' . $wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER]['curr_items'] . ' / ' . $wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER]['total_items'] . '</strong></code>' . '<br/>';
    }

    return $links_array;
}
add_filter('plugin_row_meta', 'oc4everyone_add_server_info', PHP_INT_MAX, 4);


add_action('admin_post_oc4flush_memcached', 'oc4flush_memcached');
function oc4flush_memcached() {
    if (!class_exists('Memcached')) {
        wp_die(esc_html__('Failed to flush Memcached server'));
    }
    // Verify the nonce
    if (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'flush_memcached_nonce')) {
        //flush cache 
        global $wp_object_cache;
        error_log(print_r($wp_object_cache->getStats()[OC4EVERYONE_PREDEFINED_SERVER],true));        
        $wp_object_cache->flush();

        $memcached = new Memcached();
        list($node, $port) = explode(':', OC4EVERYONE_PREDEFINED_SERVER);
        $memcached->addServer($node, $port, PHP_INT_MAX);

        if ($memcached->flush(0)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Object Cache 4 everyone::Memcached server flushed.');
            }
            wp_redirect(admin_url('plugins.php'));
            exit;
        } else {
            wp_die(esc_html__('Failed to flush Memcached server'));
        }
    } else {
        wp_die(esc_html__('Nonce verification failed. Access denied.'));
    }
}


function secondsToHumanReadable($seconds)
{
    $units = array(
        "D"    => 86400,
        "H"   => 3600,
        "M" => 60,
        "S" => 1
    );

    $output = "";

    foreach ($units as $unit => $value) {
        if ($seconds >= $value) {
            $numUnits = floor($seconds / $value);
            $output .= $numUnits . " " . $unit . " ";
            $seconds %= $value;
        }
    }

    return trim($output);
}
