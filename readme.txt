=== Object Cache 4 everyone ===
Contributors: fpuenteonline
Tags: Memcached, cache, object cache, WPO
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.2
Requires PHP: 7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Memcached or disk backend support for the WP Object Cache. Memcached server running and PHP Memcached class needed for better performance. No configuration needed, runs automatically

== Description ==

Improve your server performance using Memcached for object caching or improve your server performance using disk support for object caching.

Less resources needed and better pagespeed.

Less database queries needed.

Detects automatically any Memcached server running and adds an object-cache.php file to your WordPress site.

Default memcached servers tested:
- 127.0.0.1:11211
- 127.0.0.1:11212
- 127.0.0.1:11213
- 127.0.0.1:20000
- 127.0.0.1:20001
- Any SiteGround Memcached setup

Since 1.4, you can declare your own Memcached Server instance in the wp-config.php file.
Example:
define('OC4EVERYONE_MEMCACHED_SERVER', '127.0.0.1:11211');

Starting from version 1.6, you have the option to declare in the wp-config.php file whether you want to use the external disk object cache in case the Memcached Server is not present.
Outdated or malfunctioning disks can negatively impact website performance.
Example:
define('OC4EVERYONE_DISABLE_DISK_CACHE', true);

This plugin includes a modified template from this project:
https://github.com/humanmade/wordpress-pecl-memcached-object-cache
A WordPress object cache that uses the memcached (not memcache) PECL extension.
Thanks to Zack Tollman & 10up

== Changelog ==
= 2.2 - 2023-11-06 =
Added Memcached server flush functionality. You can now easily flush your Memcached server's cache directly from the plugin. Click the "Flush cache" link in the plugin description to clear the cache.
Added WordPress 6.4 compatibility. The plugin has been updated to ensure seamless compatibility with the latest version of WordPress, version 6.4.

= 2.1 - 2023-09-19 =
Added functionality to enable the plugin in additional staging or development environments on the same server.

= 2.0 - 2023-09-15 =
Implemented WordPress best practices and reorganized server info.

= 1.8 - 2023-09-13 =
Flush cache on plugin activation/deactivation

= 1.7 - 2023-08-19 =
Sanitize wrong keys

= 1.6 - 2023-08-09 =
Disable Disk Object cache conditionally

= 1.5 - 2023-08-03 =
Showing Memcached stats

= 1.4.3 - 2023-08-02 =
PHP 8.2 Deprecation Test

= 1.4 - 2023-08-02 =
Declare your own Memcached Server instance
More accurate message information
New strings

= 1.3 - 2023-07-26 =
Updated WordPress compatibility
New strings

= 1.2 - 2023-06-12 =
Updated WordPress compatibility
PHP 8.x compatibility

= 1.1 - 2020-05-20 =
Improvements, more default servers added.
Added SiteGround Memcached server automatically.

= 1.0 - 2020-05-16 =
Initial release.

== Frequently Asked Questions ==
