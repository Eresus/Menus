<?php
/**
 * Menus
 *
 * Управление меню
 *
 * @version ${product.version}
 *
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
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
 * $Id$
 */

/**
 * Класс плагина
 *
 * @package Menus
 */
class Menus extends Plugin
{
	/**
	 * Название плагина
	 *
	 * @var string
	 */
	public $title = 'Управление меню';

	/**
	 * Тип плагина
	 *
	 * @var string
	 */
	public $type = 'client,admin';

	/**
	 * Версия плагина
	 *
	 * @var string
	 */
	public $version = '${product.version}';

	/**
	 * Требуемая версия ядра
	 * @var string
	 */
	public $kernel = '3.00b';

	/**
	 * Описание плагина
	 *
	 * @var string
	 */
	public $description = 'Менеджер меню';

	/**
	 * Настройки
	 *
	 * @var array
	 */
	public $settings = array(
	);

	/**
	 * Описание таблицы БД и списка меню
	 *
	 * @var array
	 */
	public $table = array (
		'name' => 'menus',
		'key'=> 'id',
		'sortMode' => 'id',
		'sortDesc' => false,
		'columns' => array(
			array('name' => 'caption', 'caption' => 'Название'),
			array('name' => 'name', 'caption' => 'Имя'),
		),
		'controls' => array (
			'delete' => '',
			'edit' => '',
			'toggle' => '',
		),
		'tabs' => array(
			'width'=>'180px',
			'items'=>array(
				array('caption'=>'Создать меню', 'name'=>'action', 'value'=>'create')
			),
		),
		'sql' => "(
			`id` int(10) unsigned NOT NULL auto_increment,
			`name` varchar(255) default NULL,
			`caption` varchar(255) default NULL,
			`active` tinyint(1) unsigned default NULL,
			`root` int(10) default NULL,
			`rootLevel` int(10) unsigned default 0,
			`invisible` tinyint(1) unsigned default 0,
			`expandLevelAuto` int(10) unsigned default 0,
			`expandLevelMax` int(10) unsigned default 0,
			`template` text,
			PRIMARY KEY  (`id`),
			KEY `client` (`name`, `active`),
			KEY `admin` (`name`)
		);",
	);

	/**
	 * Путь по разделам к текущему разделу
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * Путь по разделам к текущему разделу (только идентификаторы)
	 *
	 * @var array
	 */
	private $ids = array();

	/**
	 * Конструктор
	 *
	 * @return Menus
	 */
	public function __construct()
	{
		parent::__construct();
		$this->listenEvents('clientOnURLSplit', 'clientOnPageRender', 'adminOnMenuRender');
	}
	//------------------------------------------------------------------------------

	/**
	 * Возвращает путь к директории файлов плагина
	 *
	 * @return string
	 *
	 * @since 3.00
	 */
	public function getCodeDir()
	{
		return $this->dirCode;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Вывод АИ плагина
	 *
	 * @return string  HTML
	 */
	public function adminRender()
	{
		global $Eresus, $page;

		$result = '';
		$ctrl = new Menus_Controller_Admin($this, $page);
		switch (true)
		{
			case !is_null(arg('id')):
				$result = $ctrl->editAction();
				break;

			case arg('action') == 'create':
				$result = $ctrl->addAction();
				break;

			case !is_null(arg('toggle')):
				$ctrl->toggleAction(arg('toggle', 'int'));
				break;

			case !is_null(arg('delete')):
				$ctrl->deleteAction(arg('delete', 'dbsafe'));
				break;

			default:
				$result = $ctrl->listAction();
		}
		return $result;
	}
	//------------------------------------------------------------------------------

	/**
	 * Сбор информации о текущем разделе
	 *
	 * @param array  $item
	 * @param string $url
	 *
	 * @return void
	 */
	public function clientOnURLSplit($item, $url)
	{
		$this->pages[] = $item;
		$this->ids[] = $item['id'];
		return;
		$url = $url; // PHPMD hack
	}
	//------------------------------------------------------------------------------

	/**
	 * Поиск и подстановка меню
	 *
	 * @param string $text
	 * @return string
	 */
	public function clientOnPageRender($text)
	{
		$Eresus = $GLOBALS['Eresus'];

		preg_match_all('/\$\(Menus:(.+)?\)/Usi', $text, $menus, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		$delta = 0;

		$relative = substr($Eresus->request['url'], strlen($Eresus->root), 5);

		if ($relative && $relative != 'main/')
		{
			array_shift($this->ids);
		}

		for ($i = 0; $i < count($menus); $i++)
		{
			$params = $this->dbItem('', $menus[$i][1][0], 'name');
			if ($params && isset($params['active']) && $params['active'])
			{
				$menu = new Menus_Menu($Eresus, $GLOBALS['page'], $params, $this->ids);
				$html = $menu->render();
				$text = substr_replace($text, $html, $menus[$i][0][1]+$delta, strlen($menus[$i][0][0]));
				$delta += strlen($html) - strlen($menus[$i][0][0]);
			}
		}
		return $text;
	}
	//------------------------------------------------------------------------------

	/**
	 * Добавление пункта в меню "Расширения"
	 */
	public function adminOnMenuRender()
	{
		global $page;

		$page->addMenuItem(admExtensions, array ('access'  => ADMIN, 'link'  => $this->name,
			'caption'  => $this->title, 'hint'  => $this->description));
	}
	//------------------------------------------------------------------------------

	/**
	 * @see Plugin::install()
	 */
	public function install()
	{
		$this->createTable($this->table);
		parent::install();
	}
	//-----------------------------------------------------------------------------

	/**
	 *
	 * @param unknown_type $table
	 *
	 * @return void
	 *
	 * @since ?.??
	 */
	private function createTable($table)
	{
		global $Eresus;

		$Eresus->db->query('CREATE TABLE IF NOT EXISTS `'.$Eresus->db->prefix.$table['name'].
			'`'.$table['sql']);
	}
	//-----------------------------------------------------------------------------

}
