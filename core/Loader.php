<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik
 * @package Piwik
 */

namespace Piwik;

use Exception;

/**
 * Piwik auto loader
 *
 * @package Piwik
 */
class Loader
{
    // our class search path; current directory is intentionally excluded
    protected static $dirs = array('/core/', '/plugins/');

    /**
     * Get class file name
     *
     * @param string $class Class name
     * @return string Class file name
     * @throws Exception if class name is invalid
     */
    protected static function  getClassFileName($class)
    {
        if (!preg_match('/^[A-Za-z0-9_\\\\]+$/D', $class)) {
            throw new Exception("Invalid class name \"$class\".");
        }

        // prefixed class
        $class = str_replace('_', '/', $class);

        // namespace \Piwik\Common
        $class = str_replace('\\', '/', $class);

        if ($class == 'Piwik') {
            return $class;
        }

        $class = self::removePrefix($class, 'Piwik/');
        $class = self::removePrefix($class, 'Plugins/');
        return $class;
    }

    protected static function removePrefix($class, $vendorPrefixToRemove)
    {
        if (strpos($class, $vendorPrefixToRemove) === 0) {
            return substr($class, strlen($vendorPrefixToRemove));
        }

        return $class;
    }

    private static function isPluginClass($class)
    {
        return 0 === strpos($class, 'Piwik\Plugins') || 0 === strpos($class, '\Piwik\Plugins');
    }

    private static function usesPiwikNamespace($class)
    {
        return 0 === strpos($class, 'Piwik\\') || 0 === strpos($class, '\Piwik\\');
    }

    /**
     * Load class by name
     *
     * @param string $class Class name
     * @throws Exception if class not found
     */
    public static function loadClass($class)
    {
        $classPath = self::getClassFileName($class);

        $loaded = false;
        if (static::isPluginClass($class)) {
            $loaded = static::tryToLoadClass($class, '/plugins/', $classPath);
        } else if (static::usesPiwikNamespace($class)) {
            $loaded = static::tryToLoadClass($class, '/core/', $classPath);
        }

        if ($loaded) {
            return;
        }

        if (strpos($class, '\Piwik') === 0
            || strpos($class, 'Piwik') === 0
        ) {
            // only used for files that declare multiple classes (ie Piwik/Live.php declares Piwik\Live\VisitorLog)
            do {
                // auto-discover class location
                foreach (self::$dirs as $dir) {
                    if (static::tryToLoadClass($class, $dir, $classPath)) {
                        return;
                    }
                }

                // truncate to find file with multiple class definitions
                $lastSlash = strrpos($classPath, '/');
                $classPath = ($lastSlash === false) ? '' : substr($classPath, 0, $lastSlash);
            } while (!empty($classPath));
        } else {
            // non-Piwik classes (e.g., Zend Framework) are in libs/
            static::tryToLoadClass($class, '/libs/', $classPath);
        }
    }

    private static function tryToLoadClass($class, $dir, $classPath)
    {
        $path = PIWIK_INCLUDE_PATH . $dir . $classPath . '.php';

        if (file_exists($path)) {
            require_once $path; // prefixed by PIWIK_INCLUDE_PATH

            return class_exists($class, false) || interface_exists($class, false);
        }

        return false;
    }

    /**
     * Autoloader
     *
     * @param string $class Class name
     */
    public static function autoload($class)
    {
        try {
            self::loadClass($class);
        } catch (Exception $e) {
        }
    }
}

// use the SPL autoload stack
spl_autoload_register(array('Piwik\Loader', 'autoload'));

// preserve any existing __autoload
if (function_exists('__autoload')) {
    spl_autoload_register('__autoload');
}