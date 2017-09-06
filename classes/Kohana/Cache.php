<?php

namespace Kohana;

use \Kohana_Exception as Kohana_Exception;
use \Route as Route;
use \Text as Text;

class Cache
{
    public static $dir = APPPATH . 'cache';
    public static $life = 60;
    public static $_files = [];

    public static function init($dir)
    {
        if (isset($dir)) {
            if (!is_dir($dir)) {
                try {
                    // Create the cache directory
                    mkdir($dir, 0755, true);

                    // Set permissions (must be manually set to fix umask issues)
                    chmod($dir, 0755);
                } catch (\Exception $e) {
                    throw new Kohana_Exception('Could not create cache directory :dir',
                        array(':dir' => Text::file_path($dir)));
                }
            }

            // Set the cache directory path
            Cache::$dir = realpath($dir);
        } else {
            // Use the default cache directory
            Cache::$dir = APPPATH . 'cache';
        }

        if (!is_writable(static::$dir)) {
            throw new Kohana_Exception('Directory :dir must be writable',
                array(':dir' => Text::file_path(static::$dir)));
        }

        if (isset($settings['cache_life'])) {
            // Set the default cache lifetime
            static::$life = (int)$settings['cache_life'];
        }

        static::$_files = static::cache('Kohana::find_file()');
    }

    /**
     * Provides simple file-based caching for strings and arrays:
     *
     *     // Set the "foo" cache
     *     Kohana::cache('foo', 'hello, world');
     *
     *     // Get the "foo" cache
     *     $foo = Kohana::cache('foo');
     *
     * All caches are stored as PHP code, generated with [var_export][ref-var].
     * Caching objects may not work as expected. Storing references or an
     * object or array that has recursion will cause an E_FATAL.
     *
     * The cache directory and default cache lifetime is set by [Kohana::init]
     *
     * [ref-var]: http://php.net/var_export
     *
     * @throws  Kohana_Exception
     * @param   string  $name       name of the cache
     * @param   mixed   $data       data to cache
     * @param   integer $lifetime   number of seconds the cache is valid for
     * @return  mixed    for getting
     * @return  boolean  for setting
     */
    public static function cache($name, $data = null, $lifetime = null)
    {
        // Cache file is a hash of the name
        $file = sha1($name) . '.txt';

        // Cache directories are split by keys to prevent filesystem overload
        $dir = static::$dir . '/' . $file[0] . $file[1] . '/';

        if ($lifetime === null) {
            // Use the default lifetime
            $lifetime = static::$life;
        }

        if ($data === null) {
            if (is_file($dir . $file)) {
                if ((time() - filemtime($dir . $file)) < $lifetime) {
                    // Return the cache
                    try {
                        return unserialize(file_get_contents($dir . $file));
                    } catch (\Exception $e) {
                        // Cache is corrupt, let return happen normally.
                    }
                } else {
                    try {
                        // Cache has expired
                        unlink($dir . $file);
                    } catch (\Exception $e) {
                        // Cache has mostly likely already been deleted,
                        // let return happen normally.
                    }
                }
            }

            // Cache not found
            return null;
        }

        if (!is_dir($dir)) {
            // Create the cache directory
            mkdir($dir, 0777, true);

            // Set permissions (must be manually set to fix umask issues)
            chmod($dir, 0777);
        }

        // Force the data to be a string
        $data = serialize($data);

        try {
            // Write the cache
            return (bool)file_put_contents($dir . $file, $data, LOCK_EX);
        } catch (\Exception $e) {
            // Failed to write cache
            return false;
        }
    }

    /*
     * Kohana_Route::cache
     *
     * 	/**
         * Saves or loads the route cache. If your routes will remain the same for
         * a long period of time, use this to reload the routes from the cache
         * rather than redefining them on every page load.
         *
         *     if ( ! Route::cache())
         *     {
         *         // Set routes here
         *         Route::cache(TRUE);
         *     }
         *
         * @param   boolean $save   cache the current routes
         * @param   boolean $append append, rather than replace, cached routes when loading
         * @return  void    when saving routes
         * @return  boolean when loading routes
         */
    public static function cache_route($save = false, $append = false)
    {
        if ($save === true) {
            try {
                // Cache all defined routes
                static::cache('Route::cache()', Route::$_routes);
            } catch (\Exception $e) {
                // We most likely have a lambda in a route, which cannot be cached
                throw new Kohana_Exception('One or more routes could not be cached (:message)', array(
                    ':message' => $e->getMessage(),
                ), 0, $e);
            }
        } else {
            if ($routes = static::cache('Route::cache()')) {
                if ($append) {
                    // Append cached routes
                    Route::$_routes[] = $routes;
                } else {
                    // Replace existing routes
                    Route::$_routes = $routes;
                }

                // Routes were cached
                return Route::$cache = true;
            } else {
                // Routes were not cached
                return Route::$cache = false;
            }
        }

        return false;
    }

    /* TODO: reduce file IO by caching classes paths */
    public static function find_file($dir, $file, $ext = null, $array = false)
    {
        if ($ext === null) {
            // Use the default extension
            $ext = EXT;
        } elseif ($ext) {
            // Prefix the extension with a period
            $ext = ".{$ext}";
        } else {
            // Use no extension
            $ext = '';
        }

        // Create a partial path of the filename
        $path = $dir . '/' . $file . $ext;

        if (isset(static::$_files[$path . ($array ? '_array' : '_path')])) {
            // This path has been cached
            return static::$_files[$path . ($array ? '_array' : '_path')];
        }
    }

    /* TODO: cache response */
    public static function cache_response()
    {

    }

}