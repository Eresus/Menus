<?php
/**
 * Menus
 *
 * Контролёр АИ
 *
 * @version ${product.version}
 *
 * @copyright 2012, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
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
 *
 * $Id: Menu.php 1448 2012-04-14 09:44:38Z mk $
 */


/**
 * Контролёр АИ
 *
 * @package Menus
 * @since 3.00
 */
class Menus_Controller_Admin
{
	/**
	 * Объект плагина
	 *
	 * @var Menus
	 * @since 3.00
	 */
	protected $plugin;

	/**
	 * Объект интерфейса
	 *
	 * @var TAdminUI
	 * @since 3.00
	 */
	protected $ui;

	/**
	 * Конструктор контролёра
	 *
	 * @param Plugin   $owner
	 * @param TAdminUI $ui
	 *
	 * @since 3.00
	 */
	public function __construct(Plugin $owner, TAdminUI $ui)
	{
		$this->plugin = $owner;
		$this->ui = $ui;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает разметку списка меню
	 *
	 * @return string  HTML
	 *
	 * @since 3.00
	 */
	public function listAction()
	{
		$table = $this->plugin->table;
		if (!is_null(arg('section')))
		{
			$table['condition'] = "`section`='" . arg('section', 'int') . "'";
		}
		$html = $this->ui->renderTable($table);
		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Добавление меню
	 *
	 * @return string
	 *
	 * @uses TAdminUI::renderForm()
	 */
	public function addAction()
	{
		$form = $this->plugin->createDialogTemplate();

		$form['caption'] = 'Создать меню';
		$form['fields'] []= array('type' => 'hidden', 'name' => 'action', 'value' => 'insert');
		$html = $this->ui->renderForm($form);

		return $html;
	}
	//------------------------------------------------------------------------------
}
