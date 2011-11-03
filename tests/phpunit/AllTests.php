<?php
/**
 * Menus
 *
 * Модульные тесты
 *
 * @version 1.00
 *
 * @copyright 2011, Eresus Project, http://eresus.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 *
 * Данная программа является свободным программным обеспечением. Вы
 * вправе распространять ее и/или модифицировать в соответствии с
 * условиями версии 3 либо по вашему выбору с условиями более поздней
 * версии Стандартной Общественной Лицензии GNU, опубликованной Free
 * Software Foundation.
 *
 * Мы распространяем эту программу в надежде на то, что она будет вам
 * полезной, однако НЕ ПРЕДОСТАВЛЯЕМ НА НЕЕ НИКАКИХ ГАРАНТИЙ, в том
 * числе ГАРАНТИИ ТОВАРНОГО СОСТОЯНИЯ ПРИ ПРОДАЖЕ и ПРИГОДНОСТИ ДЛЯ
 * ИСПОЛЬЗОВАНИЯ В КОНКРЕТНЫХ ЦЕЛЯХ. Для получения более подробной
 * информации ознакомьтесь со Стандартной Общественной Лицензией GNU.
 *
 * @package Menus
 * @subpackage Tests
 *
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
 *
 * $Id$
 */

if (class_exists('PHP_CodeCoverage_Filter', false))
{
	PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__);
}
else
{
	PHPUnit_Util_Filter::addFileToFilter(__FILE__);
}

require_once dirname(__FILE__) . '/classes/AllTests.php';
require_once dirname(__FILE__) . '/Menus_Test.php';

/**
 * @package Menus
 * @subpackage Tests
 */
class AllTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('All Tests');

		$suite->addTest(      Classes_AllTests::suite());
		$suite->addTestSuite('Menus_Test');

		return $suite;
	}
}
