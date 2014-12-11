<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCI\Helper;

use b8\Config;

/**
 * Languages Helper Class - Handles loading strings files and the strings within them.
 * @package PHPCI\Helper
 */
class Lang
{
    protected static $language = null;
    protected static $strings = array();
    protected static $languages = array();

    /**
     * Get a specific string from the language file.
     * @param $string
     * @return mixed|string
     */
    public static function get($string)
    {
        $vars = func_get_args();

        if (array_key_exists($string, self::$strings)) {
            $vars[0] = self::$strings[$string];
            return call_user_func_array('sprintf', $vars);
        }

        return '%%MISSING STRING: ' . $string . '%%';
    }

    /**
     * Output a specific string from the language file.
     */
    public static function out()
    {
        print call_user_func_array(array('PHPCI\Helper\Lang', 'get'), func_get_args());
    }

    /**
     * Get the currently active language.
     * @return string|null
     */
    public static function getLanguage()
    {
        return self::$language;
    }

    public static function setLanguage($language)
    {
        if (in_array($language, self::$languages)) {
            self::$language = $language;
            self::$strings = self::loadLanguage();
            return;
        }
    }

    /**
     * Return a list of available languages and their names.
     * @return array
     */
    public static function getLanguageOptions()
    {
        $languages = array();

        foreach (self::$languages as $language) {
            require(PHPCI_DIR . 'PHPCI/Languages/lang.' . $language . '.php');
            $languages[$language] = $strings['language_name'];
        }

        return $languages;
    }

    /**
     * Get the strings for the currently active language.
     * @return string[]
     */
    public static function getStrings()
    {
        return self::$strings;
    }

    /**
     * Initialise the Language helper, try load the language file for the user's browser or the configured default.
     * @param Config $config
     */
    public static function init(Config $config)
    {
        $matches = array();
        foreach (glob(PHPCI_DIR . 'PHPCI/Languages/lang.*.php') as $file) {
            if (preg_match('/lang\.([a-z]{2}\-?[a-z]*)\.php/', $file, $matches)) {
                self::$languages[] = $matches[1];
            }
        }

        // Try cookies first:
        if (isset($_COOKIE) && array_key_exists('phpcilang', $_COOKIE)) {
            $language = $_COOKIE['phpcilang'];

            if (in_array($language, self::$languages)) {
                self::$language = $language;
                self::$strings = self::loadLanguage();
                return;
            }
        }

        // Try user language:
        if (isset($_SERVER) && array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

            foreach ($langs as $lang) {
                $parts = explode(';', $lang);

                $language = strtolower($parts[0]);

                if (in_array($language, self::$languages)) {
                    self::$language = $language;
                    self::$strings = self::loadLanguage();
                    return;
                }
            }
        }

        // Try the installation default language:
        $language = $config->get('phpci.basic.language', null);

        if (in_array($language, self::$languages)) {
            self::$language = $language;
            self::$strings = self::loadLanguage();
            return;
        }

        // Fall back to English:
        self::$language = 'en';
        self::$strings = self::loadLanguage();
    }

    /**
     * Load a specific language file.
     * @return string[]|null
     */
    protected static function loadLanguage()
    {
        $langFile = PHPCI_DIR . 'PHPCI/Languages/lang.' . self::$language . '.php';

        if (!file_exists($langFile)) {
            return null;
        }

        require_once($langFile);

        if (is_null($strings) || !is_array($strings) || !count($strings)) {
            return null;
        }

        return $strings;
    }
}
