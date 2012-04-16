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
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Menus
 * @subpackage Tests
 *
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
 *
 * $Id$
 */


require_once __DIR__ . '/../bootstrap.php';
require_once TESTS_SRC_DIR . '/menus/classes/Menu.php';

/**
 * @package Menus
 * @subpackage Tests
 *
 * @since 2.03
 */
class Menu_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @covers Menus_Menu::buildURL
	 */
	public function test_buildURL()
	{
		$m_buildURL = new ReflectionMethod('Menus_Menu', 'buildURL');
		$m_buildURL->setAccessible(true);
		$params = array();
		$ids = array();
		$Eresus = new Eresus;
		$Eresus->root = 'http://example.org/';
		$menu = new Menus_Menu($Eresus, new TClientUI, $params, $ids);

		$item = array(
			'name' => 'main',
			'owner' => 0,
			'type' => 'default',
		);
		$this->assertEquals('http://example.org/', $m_buildURL->invoke($menu, $item, ''));

		$item = array(
			'name' => 'foo',
			'owner' => 0,
			'type' => 'default',
		);
		$this->assertEquals('http://example.org/foo/', $m_buildURL->invoke($menu, $item, ''));

		$item = array(
			'name' => 'bar',
			'owner' => 0,
			'type' => 'default',
		);
		$this->assertEquals('http://example.org/foo/bar/', $m_buildURL->invoke($menu, $item, 'foo/'));

		$sections = $this->getMock('stdClass', array('get'));
		$sections->expects($this->once())->method('get')->with(123)->will($this->returnValue(array(
			'content' => 'foo'
		)));
		$Eresus->sections = $sections;

		$ui = $this->getMock('TClientUI', array('replaceMacros'));
		$ui->expects($this->once())->method('replaceMacros')->with('foo')->
			will($this->returnValue('/bar.html'));

		$menu = new Menus_Menu($Eresus, $ui, $params, $ids);

		$item = array(
			'id' => 123,
			'name' => 'foo',
			'owner' => 0,
			'type' => 'url',
		);
		$this->assertEquals('http://example.org/bar.html', $m_buildURL->invoke($menu, $item, 'foo/'));

	}
	//-----------------------------------------------------------------------------
}