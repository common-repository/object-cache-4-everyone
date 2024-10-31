<?php
/**
 *
 * Disk backend for object cache
 *
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ObjectCacheDisk
{
    const RES_SUCCESS = 0;
    const RES_FAILURE = 1;
    const RES_DATA_EXISTS = 12;
    const RES_NOTSTORED = 14;
    const RES_NOTFOUND = 16;
    private $result_code;
    private $local_path = '';

    public function __construct($persistent_id = null)
    {
        //Create
        $this->local_path =  WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'object' . DIRECTORY_SEPARATOR;

        if (!file_exists($this->local_path)) {
            $return = mkdir($this->local_path, 0755, true);
            if (!$return) {
                $this->result_code = self::RES_FAILURE;
                return;
            }
        }
        $this->result_code = self::RES_SUCCESS;
    }

    public function quit()
    {
        return false;
    }


    private function deleteDirectory($dirPath)
    {
        if (is_dir($dirPath)) {
            $objects = scandir($dirPath);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir") {
                        $this->deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dirPath . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            reset($objects);
            return rmdir($dirPath);
        }
    }

    public function flush($delay = 0)
    {
        if ($this->deleteDirectory($this->local_path)) {
            self::RES_SUCCESS;
            return true;
        } else {
            self::RES_NOTSTORED;
            return false;
        }
    }

    public function getResultCode()
    {
        return $this->result_code;
    }


    public function replace($key, $value, $expiration = 0)
    {
        return $this->add($key, $value, $expiration);
    }

    public function delete($key, $time = 0)
    {
        //Find file and put
        $path = $this->_get_path($key);
        if (file_exists($path)) {
            unlink($path);

            $dir = dirname($path);
            if (is_dir($dir)) {
                rmdir($dir);
            }

            $this->result_code = self::RES_SUCCESS;
            return true;
        }
        $this->result_code = self::RES_NOTFOUND;
        return false;
    }

    public function add($key, $value, $expiration = 0)
    {
        //Find file and put
        $path = $this->_get_path($key);
        if (file_exists($path)) {
            $this->result_code = self::RES_DATA_EXISTS;
            return false;
        }
        return $this->set($key, $value, $expiration);
    }

    public function set($key, $value, $expiration = 0)
    {
        //Find file and put
        $path = $this->_get_path($key);

        //Folder for file
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $return = mkdir($dir, 0755, true);
            if (!$return) {
                $this->result_code = self::RES_FAILURE;
                return false;
            }
        }

        if (is_object($value)) {
            $value = clone $value;
        }

        $return = file_put_contents($path, @serialize($value));
        if (!$return) {
            $this->result_code = self::RES_FAILURE;
            return false;
        }

        $this->result_code = self::RES_SUCCESS;
        return true;
    }

    public function get($key)
    {
        //Find file and return
        $path = $this->_get_path($key);
        if (!file_exists($path) ||  !is_readable($path)) {
            $this->result_code = self::RES_FAILURE;
            return false;
        }

        $objData = file_get_contents($path);
        if ($objData === false) {
            $this->result_code = self::RES_FAILURE;
            return false;
        }

        $data = unserialize($objData);

        $this->result_code = self::RES_SUCCESS;
        return $data;
    }

    private function _get_path($key)
    {
        $hash = md5($key); //Returns the hash as a 32-character hexadecimal number.

        $array_hash = str_split($hash, 8); //8 name based

        $path = $this->local_path . implode(DIRECTORY_SEPARATOR, $array_hash) . DIRECTORY_SEPARATOR . '.object';
        return $path;
    }
}


if (class_exists('ObjectCacheDisk')) {
    /**
     * Adds a value to cache.
     *
     * If the specified key already exists, the value is not stored and the function
     * returns false.
     *
     * @link http://www.php.net/manual/en/memcached.add.php
     *
     * @param string    $key        The key under which to store the value.
     * @param mixed     $value      The value to store.
     * @param string    $group      The group value appended to the $key.
     * @param int       $expiration The expiration time, defaults to 0.
     * @return bool                 Returns TRUE on success or FALSE on failure.
     */
    function wp_cache_add($key, $value, $group = '', $expiration = 0)
    {
        global $wp_object_cache;
        return $wp_object_cache->add($key, $value, $group, $expiration);
    }


    /**
     * Closes the cache.
     *
     * This function has ceased to do anything since WordPress 2.5. The
     * functionality was removed along with the rest of the persistent cache. This
     * does not mean that plugins can't implement this function when they need to
     * make sure that the cache is cleaned up after WordPress no longer needs it.
     *
     * @since 2.0.0
     *
     * @return  bool    Always returns True
     */
    function wp_cache_close()
    {
        return true;
    }

    /**
     * Decrement a numeric item's value.
     *
     * @link http://www.php.net/manual/en/memcached.decrement.php
     *
     * @param string    $key    The key under which to store the value.
     * @param int       $offset The amount by which to decrement the item's value.
     * @param string    $group  The group value appended to the $key.
     * @return int|bool         Returns item's new value on success or FALSE on failure.
     */
    function wp_cache_decrement($key, $offset = 1, $group = '')
    {
        global $wp_object_cache;
        return $wp_object_cache->decrement($key, $offset, $group);
    }

    /**
     * Decrement a numeric item's value.
     *
     * Same as wp_cache_decrement. Original WordPress caching backends use wp_cache_decr. I
     * want both spellings to work.
     *
     * @link http://www.php.net/manual/en/memcached.decrement.php
     *
     * @param string    $key    The key under which to store the value.
     * @param int       $offset The amount by which to decrement the item's value.
     * @param string    $group  The group value appended to the $key.
     * @return int|bool         Returns item's new value on success or FALSE on failure.
     */
    function wp_cache_decr($key, $offset = 1, $group = '')
    {
        return wp_cache_decrement($key, $offset, $group);
    }

    /**
     * Remove the item from the cache.
     *
     * Remove an item from memcached with identified by $key after $time seconds. The
     * $time parameter allows an object to be queued for deletion without immediately
     * deleting. Between the time that it is queued and the time it's deleted, add,
     * replace, and get will fail, but set will succeed.
     *
     * @link http://www.php.net/manual/en/memcached.delete.php
     *
     * @param string    $key    The key under which to store the value.
     * @param string    $group  The group value appended to the $key.
     * @param int       $time   The amount of time the server will wait to delete the item in seconds.
     * @return bool             Returns TRUE on success or FALSE on failure.
     */
    function wp_cache_delete($key, $group = '', $time = 0)
    {
        global $wp_object_cache;
        return $wp_object_cache->delete($key, $group, $time);
    }

    /**
     * Invalidate all items in the cache.
     *
     * @link http://www.php.net/manual/en/memcached.flush.php
     *
     * @param int       $delay  Number of seconds to wait before invalidating the items.
     * @return bool             Returns TRUE on success or FALSE on failure.
     */
    function wp_cache_flush($delay = 0)
    {
        global $wp_object_cache;
        return $wp_object_cache->flush();
    }

    /**
     * Retrieve object from cache.
     *
     * Gets an object from cache based on $key and $group. In order to fully support the $cache_cb and $cas_token
     * parameters, the runtime cache is ignored by this function if either of those values are set. If either of
     * those values are set, the request is made directly to the memcached server for proper handling of the
     * callback and/or token.
     *
     * Note that the $deprecated and $found args are only here for compatibility with the native wp_cache_get function.
     *
     * @link http://www.php.net/manual/en/memcached.get.php
     *
     * @param string        $key        The key under which to store the value.
     * @param string        $group      The group value appended to the $key.
     * @param bool          $force      Whether or not to force a cache invalidation.
     * @param null|bool     $found      Variable passed by reference to determine if the value was found or not.
     * @return bool|mixed               Cached object value.
     */
    function wp_cache_get($key, $group = '', $force = false, &$found = null)
    {
        global $wp_object_cache;

        return $wp_object_cache->get($key, $group, $force, $found);
    }


    /**
     * Get server pool statistics.
     *
     * @link http://www.php.net/manual/en/memcached.getstats.php
     *
     * @return array    Array of server statistics, one entry per server.
     */
    function wp_cache_get_stats()
    {
        global $wp_object_cache;
        return $wp_object_cache->getStats();
    }

    /**
     * Increment a numeric item's value.
     *
     * @link http://www.php.net/manual/en/memcached.increment.php
     *
     * @param string    $key    The key under which to store the value.
     * @param int       $offset The amount by which to increment the item's value.
     * @param string    $group  The group value appended to the $key.
     * @return int|bool         Returns item's new value on success or FALSE on failure.
     */
    function wp_cache_increment($key, $offset = 1, $group = '')
    {
        global $wp_object_cache;
        return $wp_object_cache->increment($key, $offset, $group);
    }

    /**
     * Increment a numeric item's value.
     *
     * This is the same as wp_cache_increment, but kept for back compatibility. The original
     * WordPress caching backends use wp_cache_incr. I want both to work.
     *
     * @link http://www.php.net/manual/en/memcached.increment.php
     *
     * @param string    $key    The key under which to store the value.
     * @param int       $offset The amount by which to increment the item's value.
     * @param string    $group  The group value appended to the $key.
     * @return int|bool         Returns item's new value on success or FALSE on failure.
     */
    function wp_cache_incr($key, $offset = 1, $group = '')
    {
        return wp_cache_increment($key, $offset, $group);
    }

    /**
     * Replaces a value in cache.
     *
     * This method is similar to "add"; however, is does not successfully set a value if
     * the object's key is not already set in cache.
     *
     * @link http://www.php.net/manual/en/memcached.replace.php
     *
     * @param string    $key        The key under which to store the value.
     * @param mixed     $value      The value to store.
     * @param string    $group      The group value appended to the $key.
     * @param int       $expiration The expiration time, defaults to 0.
     * @return bool                 Returns TRUE on success or FALSE on failure.
     */
    function wp_cache_replace($key, $value, $group = '', $expiration = 0)
    {
        global $wp_object_cache;
        return $wp_object_cache->replace($key, $value, $group, $expiration);
    }


    /**
     * Sets a value in cache.
     *
     * The value is set whether or not this key already exists in memcached.
     *
     * @link http://www.php.net/manual/en/memcached.set.php
     *
     * @param string    $key        The key under which to store the value.
     * @param mixed     $value      The value to store.
     * @param string    $group      The group value appended to the $key.
     * @param int       $expiration The expiration time, defaults to 0.
     * @return bool                 Returns TRUE on success or FALSE on failure.
     */
    function wp_cache_set($key, $value, $group = '', $expiration = 0)
    {
        global $wp_object_cache;
        return $wp_object_cache->set($key, $value, $group, $expiration);
    }


    /**
     * Switch blog prefix, which changes the cache that is accessed.
     *
     * @param  int     $blog_id    Blog to switch to.
     * @return void
     */
    function wp_cache_switch_to_blog($blog_id)
    {
        global $wp_object_cache;
        return $wp_object_cache->switch_to_blog($blog_id);
    }


    /**
     * Sets up Object Cache Global and assigns it.
     *
     * @global  WP_Object_Cache     $wp_object_cache    WordPress Object Cache
     * @return  void
     */
    function wp_cache_init()
    {
        global $wp_object_cache;
        //Create a persistent instance
        $wp_object_cache = new WP_Object_Cache(OC4EVERYONE_PREDEFINED_SERVER);
    }

    /**
     * Adds a group or set of groups to the list of non-persistent groups.
     *
     * @param   string|array    $groups     A group or an array of groups to add.
     * @return  void
     */
    function wp_cache_add_global_groups($groups)
    {
        global $wp_object_cache;
        $wp_object_cache->add_global_groups($groups);
    }

    /**
     * Adds a group or set of groups to the list of non-Memcached groups.
     *
     * @param   string|array    $groups     A group or an array of groups to add.
     * @return  void
     */
    function wp_cache_add_non_persistent_groups($groups)
    {
        global $wp_object_cache;
        $wp_object_cache->add_non_persistent_groups($groups);
    }

    class WP_Object_Cache
    {

        //Easy stats
        public $stats     = array();
        public $now = 0; //init


        /**
         * The amount of times the cache data was already stored in the cache.
         *
         * @since 2.5.0
         * @var int
         */
        public $cache_hits = 0;

        /**
         * Amount of times the cache did not have the request in cache.
         *
         * @since 2.0.0
         * @var int
         */
        public $cache_misses = 0;


        /**
         * Holds the ObjectCacheDisk object.
         *
         * @var ObjectCacheDisk
         */
        public $diskcached;

        /**
         * Holds the non-Memcached objects.
         *
         * @var array
         */
        public $cache = array();

        /**
         * List of global groups.
         *
         * @var array
         */
        public $global_groups = array('users', 'userlogins', 'usermeta', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss');

        /**
         * List of groups not saved to Memcached.
         *
         * @var array
         */
        public $no_mc_groups = array('comment', 'counts', 'plugins');

        /**
         * Prefix used for global groups.
         *
         * @var string
         */
        public $global_prefix = '';

        /**
         * Prefix used for non-global groups.
         *
         * @var string
         */
        public $blog_prefix = '';

        /**
         * Instantiate the Memcached class.
         *
         * Instantiates the Memcached class .
         *
         * @link    http://www.php.net/manual/en/memcached.construct.php
         *
         * @param   null    $persistent_id      To create an instance that persists between requests, use persistent_id to specify a unique ID for the instance.
         */
        public function __construct($persistent_id = null)
        {
            global $blog_id, $table_prefix;

            $this->stats = array(
                'cmd_get' => 0,
                'cmd_set' => 0,
                'get_hits' => 0,
                'get_misses' => 0,
            );

            $this->diskcached = new ObjectCacheDisk();

            //Started? - first filemtime file

            // Assign global and blog prefixes for use with keys
            if (function_exists('is_multisite')) {
                $this->global_prefix = (is_multisite() || defined('CUSTOM_USER_TABLE') && defined('CUSTOM_USER_META_TABLE')) ? '' : $table_prefix;
                $this->blog_prefix = (is_multisite() ? $blog_id : $table_prefix) . ':';
            }

            // Setup cacheable values for handling expiration times
            $this->thirty_days = 60 * 60 * 24 * 30;
            $this->now         = time();

            $this->get_hits   = &$this->stats['get_hits'];
            $this->get_misses = &$this->stats['get_misses'];

            $this->cmd_get = &$this->stats['cmd_get'];
            $this->cmd_set = &$this->stats['cmd_set'];
        }

        /**
         * Adds a value to cache.
         *
         * If the specified key already exists, the value is not stored and the function
         * returns false.
         *
         * @link    http://www.php.net/manual/en/memcached.add.php
         *
         * @param   string      $key            The key under which to store the value.
         * @param   mixed       $value          The value to store.
         * @param   string      $group          The group value appended to the $key.
         * @param   int         $expiration     The expiration time, defaults to 0.
         * @return  bool                        Returns TRUE on success or FALSE on failure.
         */
        public function add($key, $value, $group = 'default', $expiration = 0)
        {
            /*
    		 * Ensuring that wp_suspend_cache_addition is defined before calling, because sometimes an advanced-cache.php
    		 * file will load object-cache.php before wp-includes/functions.php is loaded. In those cases, if wp_cache_add
    		 * is called in advanced-cache.php before any more of WordPress is loaded, we get a fatal error because
    		 * wp_suspend_cache_addition will not be defined until wp-includes/functions.php is loaded.
    		 */
            if (function_exists('wp_suspend_cache_addition') && wp_suspend_cache_addition()) {
                return false;
            }

            $derived_key = $this->buildKey($key, $group);
            $expiration  = $this->sanitize_expiration($expiration);

            // If group is a non-Memcached group, save to runtime cache, not Memcached
            if (in_array($group, $this->no_mc_groups) || $expiration > 0) {

                // Add does not set the value if the key exists; mimic that here
                if (isset($this->cache[$derived_key])) {
                    return false;
                }

                $this->add_to_internal_cache($derived_key, $value);

                return true;
            }

            // Save to Memcached
            $result = $this->diskcached->add($derived_key, $value, $expiration);

            // Store in runtime cache if add was successful
            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $value);
            }

            return $result;
        }

        /**
         * Decrement a numeric item's value.
         *
         * @link http://www.php.net/manual/en/memcached.decrement.php
         *
         * @param string    $key    The key under which to store the value.
         * @param int       $offset The amount by which to decrement the item's value.
         * @param string    $group  The group value appended to the $key.
         * @return int|bool         Returns item's new value on success or FALSE on failure.
         */
        public function decrement($key, $offset = 1, $group = 'default')
        {
            $derived_key = $this->buildKey($key, $group);

            // Decrement values in no_mc_groups
            if (in_array($group, $this->no_mc_groups)) {

                // Only decrement if the key already exists and value is 0 or greater (mimics memcached behavior)
                if (isset($this->cache[$derived_key]) && $this->cache[$derived_key] >= 0) {

                    // If numeric, subtract; otherwise, consider it 0 and do nothing
                    if (is_numeric($this->cache[$derived_key])) {
                        $this->cache[$derived_key] -= (int) $offset;
                    } else {
                        $this->cache[$derived_key] = 0;
                    }

                    // Returned value cannot be less than 0
                    if ($this->cache[$derived_key] < 0) {
                        $this->cache[$derived_key] = 0;
                    }

                    return $this->cache[$derived_key];
                } else {
                    return false;
                }
            }

            $result = $this->diskcached->decrement($derived_key, $offset);

            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $result);
            }

            return $result;
        }

        /**
         * Decrement a numeric item's value.
         *
         * Alias for $this->decrement. Other caching backends use this abbreviated form of the function. It *may* cause
         * breakage somewhere, so it is nice to have. This function will also allow the core unit tests to pass.
         *
         * @param string    $key    The key under which to store the value.
         * @param int       $offset The amount by which to decrement the item's value.
         * @param string    $group  The group value appended to the $key.
         * @return int|bool         Returns item's new value on success or FALSE on failure.
         */
        public function decr($key, $offset = 1, $group = 'default')
        {
            return $this->decrement($key, $offset, $group);
        }

        /**
         * Remove the item from the cache.
         *
         * Remove an item from memcached with identified by $key after $time seconds. The
         * $time parameter allows an object to be queued for deletion without immediately
         * deleting. Between the time that it is queued and the time it's deleted, add,
         * replace, and get will fail, but set will succeed.
         *
         * @link http://www.php.net/manual/en/memcached.delete.php
         *
         * @param   string      $key        The key under which to store the value.
         * @param   string      $group      The group value appended to the $key.
         * @param   int         $time       The amount of time the server will wait to delete the item in seconds.
         * @return  bool                    Returns TRUE on success or FALSE on failure.
         */
        public function delete($key, $group = 'default', $time = 0)
        {
            $derived_key = $this->buildKey($key, $group);

            // Remove from no_mc_groups array
            if (in_array($group, $this->no_mc_groups)) {
                if (isset($this->cache[$derived_key])) {
                    unset($this->cache[$derived_key]);
                }

                return true;
            }

            $result = $this->diskcached->delete($derived_key, $time);

            if (isset($this->cache[$derived_key])) {
                unset($this->cache[$derived_key]);
            }

            return $result;
        }


        /**
         * Invalidate all items in the cache.
         *
         * @link http://www.php.net/manual/en/memcached.flush.php
         *
         * @param   int     $delay      Number of seconds to wait before invalidating the items.
         * @return  bool                Returns TRUE on success or FALSE on failure.
         */
        public function flush()
        {
            $result = $this->diskcached->flush();

            // Only reset the runtime cache if memcached was properly flushed
            if ($result && ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->cache = array();
            }

            return $result;
        }

        /**
         * Retrieve object from cache.
         *
         * Gets an object from cache based on $key and $group. In order to fully support the $cache_cb and $cas_token
         * parameters, the runtime cache is ignored by this function if either of those values are set. If either of
         * those values are set, the request is made directly to the memcached server for proper handling of the
         * callback and/or token. Note that the $cas_token variable cannot be directly passed to the function. The
         * variable need to be first defined with a non null value.
         *
         * If using the $cache_cb argument, the new value will always have an expiration of time of 0 (forever). This
         * is a limitation of the Memcached PECL extension.
         *
         * @link http://www.php.net/manual/en/memcached.get.php
         *
         * @param   string          $key        The key under which to store the value.
         * @param   string          $group      The group value appended to the $key.
         * @param   bool            $force      Whether or not to force a cache invalidation.
         * @param   null|bool       $found      Variable passed by reference to determine if the value was found or not.
         * @return  bool|mixed                  Cached object value.
         */
        public function get($key, $group = 'default', $force = false, &$found = null)
        {
            ++$this->stats['cmd_get'];

            $derived_key = $this->buildKey($key, $group);

            // Assume object is not found
            $found = false;

            if (isset($this->cache[$derived_key]) && !$force) {
                $found = true;
                $this->cache_hits += 1;
                ++$this->stats['get_hits'];
                return is_object($this->cache[$derived_key]) ? clone $this->cache[$derived_key] : $this->cache[$derived_key];
            } elseif (in_array($group, $this->no_mc_groups)) {
                $this->cache_misses += 1;
                ++$this->stats['get_misses'];
                return false;
            } else {
                $value = $this->diskcached->get($derived_key);
            }

            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $value);
                $found = true;
            }

            if ($found) {
                $this->cache_hits += 1;
                ++$this->stats['get_hits'];
            } else {
                $this->cache_misses += 1;
                ++$this->stats['get_misses'];
            }

            return is_object($value) ? clone $value : $value;
        }




        /**
         * Get server pool statistics.
         *
         * @link    http://www.php.net/manual/en/memcached.getstats.php
         *
         * @return  array       Array of server statistics, one entry per server.
         */
        public function getStats()
        {
            return array('runtime' => array(
                'pid' => 0,
                'uptime' => time() - $this->now,
                'threads' => 1,
                'time' => time(),
                'total_items' => count($this->cache),
                'curr_connections' => 1,
                'total_connections' => 1,
                'cmd_get' => $this->cmd_get,
                'cmd_set' => $this->cmd_set,
                'get_hits' => $this->get_hits,
                'get_misses' => $this->get_misses,
                'version' => '1.0'
            ));
        }


        /**
         * Increment a numeric item's value.
         *
         * @link http://www.php.net/manual/en/memcached.increment.php
         *
         * @param   string      $key        The key under which to store the value.
         * @param   int         $offset     The amount by which to increment the item's value.
         * @param   string      $group      The group value appended to the $key.
         * @return  int|bool                Returns item's new value on success or FALSE on failure.
         */
        public function increment($key, $offset = 1, $group = 'default')
        {
            $derived_key = $this->buildKey($key, $group);

            // Increment values in no_mc_groups
            if (in_array($group, $this->no_mc_groups)) {

                // Only increment if the key already exists and the number is currently 0 or greater (mimics memcached behavior)
                if (isset($this->cache[$derived_key]) &&  $this->cache[$derived_key] >= 0) {

                    // If numeric, add; otherwise, consider it 0 and do nothing
                    if (is_numeric($this->cache[$derived_key])) {
                        $this->cache[$derived_key] += (int) $offset;
                    } else {
                        $this->cache[$derived_key] = 0;
                    }

                    // Returned value cannot be less than 0
                    if ($this->cache[$derived_key] < 0) {
                        $this->cache[$derived_key] = 0;
                    }

                    return $this->cache[$derived_key];
                } else {
                    return false;
                }
            }

            $result = $this->diskcached->increment($derived_key, $offset);

            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $result);
            }

            return $result;
        }

        private function getResultCode()
        {
            return $this->diskcached->getResultCode();
        }

        /**
         * Synonymous with $this->incr.
         *
         * Certain plugins expect an "incr" method on the $wp_object_cache object (e.g., Batcache). Since the original
         * version of this library matched names to the memcached methods, the "incr" method was missing. Adding this
         * method restores compatibility with plugins expecting an "incr" method.
         *
         * @param   string      $key        The key under which to store the value.
         * @param   int         $offset     The amount by which to increment the item's value.
         * @param   string      $group      The group value appended to the $key.
         * @return  int|bool                Returns item's new value on success or FALSE on failure.
         */
        public function incr($key, $offset = 1, $group = 'default')
        {
            return $this->increment($key, $offset, $group);
        }

        /**
         * Replaces a value in cache.
         *
         * This method is similar to "add"; however, is does not successfully set a value if
         * the object's key is not already set in cache.
         *
         * @link    http://www.php.net/manual/en/memcached.replace.php
         *
         * @param   string      $server_key     The key identifying the server to store the value on.
         * @param   string      $key            The key under which to store the value.
         * @param   mixed       $value          The value to store.
         * @param   string      $group          The group value appended to the $key.
         * @param   int         $expiration     The expiration time, defaults to 0.
         * @return  bool                        Returns TRUE on success or FALSE on failure.
         */
        public function replace($key, $value, $group = 'default', $expiration = 0)
        {
            $derived_key = $this->buildKey($key, $group);
            $expiration  = $this->sanitize_expiration($expiration);

            // If group is a non-Memcached group, save to runtime cache, not Memcached
            if (in_array($group, $this->no_mc_groups) || $expiration > 0) {

                // Replace won't save unless the key already exists; mimic this behavior here
                if (!isset($this->cache[$derived_key])) {
                    return false;
                }

                $this->cache[$derived_key] = $value;
                return true;
            }

            // Save to Memcached
            $result = $this->diskcached->replace($derived_key, $value, $expiration);

            // Store in runtime cache if add was successful
            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $value);
            }

            return $result;
        }

        /**
         * Sets a value in cache.
         *
         * The value is set whether or not this key already exists in memcached.
         *
         * @link http://www.php.net/manual/en/memcached.set.php
         *
         * @param   string      $key        The key under which to store the value.
         * @param   mixed       $value      The value to store.
         * @param   string      $group      The group value appended to the $key.
         * @param   int         $expiration The expiration time, defaults to 0.
         * @return  bool                    Returns TRUE on success or FALSE on failure.
         */
        public function set($key, $value, $group = 'default', $expiration = 0)
        {
            ++$this->stats['cmd_set'];

            $derived_key = $this->buildKey($key, $group);
            $expiration  = $this->sanitize_expiration($expiration);

            // If group is a non-Memcached group, save to runtime cache, not Memcached
            if (in_array($group, $this->no_mc_groups) || $expiration > 0) {
                $this->add_to_internal_cache($derived_key, $value);
                return true;
            }

            // Save to Memcached
            $result = $this->diskcached->set($derived_key, $value, $expiration);

            // Store in runtime cache if add was successful
            if (ObjectCacheDisk::RES_SUCCESS === $this->getResultCode()) {
                $this->add_to_internal_cache($derived_key, $value);
            }

            return $result;
        }

        /**
         * Builds a key for the cached object using the blog_id, key, and group values.
         *
         * @author  Ryan Boren   This function is inspired by the original WP Memcached Object cache.
         * @link    http://wordpress.org/extend/plugins/memcached/
         *
         * @param   string      $key        The key under which to store the value.
         * @param   string      $group      The group value appended to the $key.
         * @return  string
         */
        public function buildKey($key, $group = 'default')
        {
            if (empty($group)) {
                $group = 'default';
            }

            if (false !== array_search($group, $this->global_groups)) {
                $prefix = $this->global_prefix;
            } else {
                $prefix = $this->blog_prefix;
            }

            return preg_replace('/\s+/', '', WP_CACHE_KEY_SALT . "$prefix$group:$key");
        }


        /**
         * Ensure that a proper expiration time is set.
         *
         * Memcached treats any value over 30 days as a timestamp. If a developer sets the expiration for greater than 30
         * days or less than the current timestamp, the timestamp is in the past and the value isn't cached. This function
         * detects values in that range and corrects them.
         *
         * @param  string|int    $expiration    The dirty expiration time.
         * @return string|int                   The sanitized expiration time.
         */
        public function sanitize_expiration($expiration)
        {
            if ($expiration === 0) { //forever
                return $expiration;
            }

            //Expiration seconds from now
            $expiration = $expiration + $this->now;

            return $expiration;
        }

        /**
         * Concatenates two values and casts to type of the first value.
         *
         * This is used in append and prepend operations to match how these functions are handled
         * by memcached. In both cases, whichever value is the original value in the combined value
         * will dictate the type of the combined value.
         *
         * @param   mixed       $original   Original value that dictates the combined type.
         * @param   mixed       $pended     Value to combine with original value.
         * @param   string      $direction  Either 'pre' or 'app'.
         * @return  mixed                   Combined value casted to the type of the first value.
         */
        public function combine_values($original, $pended, $direction)
        {
            $type = gettype($original);

            // Combine the values based on direction of the "pend"
            if ('pre' == $direction) {
                $combined = $pended . $original;
            } else {
                $combined = $original . $pended;
            }

            // Cast type of combined value
            settype($combined, $type);

            return $combined;
        }

        /**
         * Simple wrapper for saving object to the internal cache.
         *
         * @param   string      $derived_key    Key to save value under.
         * @param   mixed       $value          Object value.
         */
        public function add_to_internal_cache($derived_key, $value)
        {
            if (is_object($value)) {
                $value = clone $value;
            }

            $this->cache[$derived_key] = $value;
        }

        /**
         * Determines if a no_mc_group exists in a group of groups.
         *
         * @param   mixed   $groups     The groups to search.
         * @return  bool                True if a no_mc_group is present; false if a no_mc_group is not present.
         */
        public function contains_no_mc_group($groups)
        {
            if (is_scalar($groups)) {
                return in_array($groups, $this->no_mc_groups);
            }

            if (!is_array($groups)) {
                return false;
            }

            foreach ($groups as $group) {
                if (in_array($group, $this->no_mc_groups)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Add global groups.
         *
         * @author  Ryan Boren   This function comes straight from the original WP Memcached Object cache
         * @link    http://wordpress.org/extend/plugins/memcached/
         *
         * @param   array       $groups     Array of groups.
         * @return  void
         */
        public function add_global_groups($groups)
        {
            if (!is_array($groups)) {
                $groups = (array) $groups;
            }

            $this->global_groups = array_merge($this->global_groups, $groups);
            $this->global_groups = array_unique($this->global_groups);
        }

        /**
         * Add non-persistent groups.
         *
         * @author  Ryan Boren   This function comes straight from the original WP Memcached Object cache
         * @link    http://wordpress.org/extend/plugins/memcached/
         *
         * @param   array       $groups     Array of groups.
         * @return  void
         */
        public function add_non_persistent_groups($groups)
        {
            if (!is_array($groups)) {
                $groups = (array) $groups;
            }

            $this->no_mc_groups = array_merge($this->no_mc_groups, $groups);
            $this->no_mc_groups = array_unique($this->no_mc_groups);
        }

        /**
         * Get a value specifically from the internal, run-time cache, not memcached.
         *
         * @param   int|string  $key        Key value.
         * @param   int|string  $group      Group that the value belongs to.
         * @return  bool|mixed              Value on success; false on failure.
         */
        public function get_from_runtime_cache($key, $group)
        {
            $derived_key = $this->buildKey($key, $group);

            if (isset($this->cache[$derived_key])) {
                return $this->cache[$derived_key];
            }

            return false;
        }

        /**
         * Switch blog prefix, which changes the cache that is accessed.
         *
         * @param  int     $blog_id    Blog to switch to.
         * @return void
         */
        public function switch_to_blog($blog_id)
        {
            global $table_prefix;
            $blog_id           = (int) $blog_id;
            $this->blog_prefix = (is_multisite() ? $blog_id : $table_prefix) . ':';
        }
    }
} //if (class_exists('ObjectCacheDisk'))
