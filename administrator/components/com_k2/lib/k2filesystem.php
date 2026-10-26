<?php
/**
 * @version    2.11.x
 * @package    K2
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2006 - 2022 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\Filesystem\File as FrameworkFile;
use Joomla\Filesystem\Folder as FrameworkFolder;
use Joomla\Filesystem\Path as FrameworkPath;
use Joomla\CMS\Log\Log;

/*
 * Joomla 6 removed the CMS Filesystem package (Joomla\CMS\Filesystem) - it is only available
 * through the "Behaviour - Backward Compatibility 6" plugin. The framework package
 * (Joomla\Filesystem) is the replacement, but it throws exceptions where the CMS package
 * returned false. K2 relies on the old behaviour in many places (e.g. deleting image sizes
 * that may not exist), so these wrappers keep it: framework call, false instead of an exception.
 */

if (!class_exists('K2File')) {
    class K2File
    {
        public static function __callStatic($method, $arguments)
        {
            try {
                return call_user_func_array([FrameworkFile::class, $method], $arguments);
            } catch (\Throwable $e) {
                Log::add($e->getMessage(), Log::WARNING, 'jerror');
                return false;
            }
        }

        public static function exists($file)
        {
            return is_file(FrameworkPath::clean($file));
        }

        // Not part of the framework package
        public static function getName($file)
        {
            return basename(str_replace('\\', '/', $file));
        }
    }
}

if (!class_exists('K2Folder')) {
    class K2Folder
    {
        public static function __callStatic($method, $arguments)
        {
            try {
                return call_user_func_array([FrameworkFolder::class, $method], $arguments);
            } catch (\Throwable $e) {
                Log::add($e->getMessage(), Log::WARNING, 'jerror');
                return ($method === 'files' || $method === 'folders') ? [] : false;
            }
        }

        public static function exists($path)
        {
            return is_dir(FrameworkPath::clean($path));
        }
    }
}

if (!class_exists('K2Path')) {
    class K2Path
    {
        public static function __callStatic($method, $arguments)
        {
            // Path::check() must keep throwing - it guards against path traversal
            return call_user_func_array([FrameworkPath::class, $method], $arguments);
        }
    }
}
