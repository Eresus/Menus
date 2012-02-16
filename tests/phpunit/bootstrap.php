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

define('TESTS_SRC_DIR', realpath(__DIR__ . '/../../src'));

define('GUEST', 5);

define('SECTIONS_ACTIVE',  0x0001);
define('SECTIONS_VISIBLE', 0x0002);

class Plugin
{
	public function __construct() {}
	protected function listenEvents() {}
}
