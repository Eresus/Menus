<?php
/**
 * Тесты класса Menus_Menu
 *
 * @version ${product.version}
 *
 * @copyright 2011, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
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
 */

require_once __DIR__ . '/../bootstrap.php';

/**
 * @package Menus
 * @subpackage Tests
 *
 * @since 2.03
 */
class Menus_MenuTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Menus_Menu::renderBranch
     */
    public function test_renderBranch()
    {
        $m_renderBranch = new ReflectionMethod('Menus_Menu', 'renderBranch');
        $m_renderBranch->setAccessible(true);
        $params = array('name' => 'menuFoo', 'expandLevelAuto' => 0, 'expandLevelMax' => 0);
        $ids = array();
        $Eresus = new Eresus;
        $Eresus->root = 'http://example.org/';
        $Eresus->sections = $this->getMock('stdClass', array('get', 'children'));
        $map = array(
            array(0, array()),
            array(1, array('id' => 1, 'owner' => 0, 'name' => 'main', 'type' => 'default')),
        );
        $Eresus->sections->expects($this->any())->method('get')->will($this->returnValueMap($map));
        $map = array(
            array(0, null, null, array(
                array('id' => 1, 'owner' => 0, 'name' => 'main', 'type' => 'default'))
            ),
            array(1, null, null, array()),
        );
        $Eresus->sections->expects($this->any())->method('children')->will($this->returnValueMap($map));
        $ui = new TClientUI;
        $ui->id = 0;
        $menu = new Menus_Menu($Eresus, $ui, $params, $ids);

        $template = $this->getMock('Template', array('compile'));
        $template->expects($this->once())->method('compile');

        $p_template = new ReflectionProperty('Menus_Menu', 'template');
        $p_template->setAccessible(true);
        $p_template->setValue($menu, $template);

        $m_renderBranch->invoke($menu, -1, 'http://example.org/');
    }
}

