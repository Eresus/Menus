<?php
/**
 * Стартовый файл тестов
 *
 * @package Menus
 * @subpackage Tests
 */

/**
 * Путь к папке исходные кодов
 */
define('TESTS_SRC_DIR', realpath(__DIR__ . '/../../src'));

spl_autoload_register(
    function ($class)
    {
        if ('Menus' == $class)
        {
            require TESTS_SRC_DIR . '/menus.php';
        }
        elseif (substr($class, 0, 6) == 'Menus_')
        {
            $path = TESTS_SRC_DIR . '/menus/classes/' . str_replace('_', '/', substr($class, 6))
                . '.php';
            if (file_exists($path))
            {
                require $path;
            }
        }
    }
);

require_once 'stubs.php';

