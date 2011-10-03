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
 * @author Mikhail Krasilnikov <mk@procreat.ru>
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
	public $kernel = '2.12';

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
	private $table = array (
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
			`name` varchar(31) default NULL,
			`caption` varchar(255) default NULL,
			`active` tinyint(1) unsigned default NULL,
			`root` int(10) default NULL,
			`rootLevel` int(10) unsigned default 0,
			`expandLevelAuto` int(10) unsigned default 0,
			`expandLevelMax` int(10) unsigned default 0,
			`glue` varchar(255) default '',
			`tmplList` text,
			`tmplItem` text,
			`tmplSpecial` text,
			`specialMode` tinyint(3) unsigned default 0,
			`invisible` tinyint(1) unsigned default 0,
			`counterReset` int(10) unsigned default 0,
			PRIMARY KEY  (`id`),
			KEY `name` (`name`),
			KEY `active` (`active`)
		) TYPE=MyISAM COMMENT='Menu collection';",
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
	 * Вывод АИ плагина
	 *
	 * @return string  HTML
	 */
	public function adminRender()
	{
		global $Eresus, $page;

		$result = '';
		if (!is_null(arg('id')))
		{
			$item = $Eresus->db->selectItem($this->table['name'],
				"`".$this->table['key']."` = '".arg('id', 'dbsafe')."'");
			$page->title .= empty($item['caption'])?'':' - '.$item['caption'];
		}
		switch (true)
		{
			case !is_null(arg('update')):
				$result = $this->update();
			break;
			case !is_null(arg('toggle')):
				$result = $this->toggle(arg('toggle', 'dbsafe'));
			break;
			case !is_null(arg('delete')):
				$result = $this->delete(arg('delete', 'dbsafe'));
			break;
			case !is_null(arg('up')):
				$result = $this->table['sortDesc'] ?
					$this->down(arg('up', 'dbsafe')) :
					$this->up(arg('up', 'dbsafe'));
			break;
			case !is_null(arg('down')):
				$result = $this->table['sortDesc'] ?
					$this->up(arg('down', 'dbsafe')) :
					$this->down(arg('down', 'dbsafe'));
			break;
			case !is_null(arg('id')):
				$result = $this->adminEditItem();
			break;
			case !is_null(arg('action')):
				switch (arg('action'))
				{
					case 'create':
						$result = $this->adminAddItem();
					break;
					case 'insert':
						$result = $this->insert();
					break;
				}
			break;
			default:
				if (!is_null(arg('section')))
				{
					$this->table['condition'] = "`section`='".arg('section', 'int')."'";
				}
				$result = $page->renderTable($this->table);
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

		include $this->dirCode . 'classes/Menu.php';

		for ($i = 0; $i < count($menus); $i++)
		{
			$params = $this->dbItem('', $menus[$i][1][0], 'name');
			if ($params && isset($params['active']) && $params['active'])
			{
				$menu = new Menus_Menu($params, $this->ids, $Eresus->root);
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
	 * Добавление меню
	 *
	 * @return void
	 *
	 * @uses Eresus
	 * @uses HTTP::redirect()
	 * @uses arg()
	 */
	private function insert()
	{
		global $Eresus;

		$item = array(
				'name' => arg('name', 'word'),
				'caption' => arg('caption', 'dbsafe'),
				'active' => true,
				'root' => arg('root', 'int'),
				'rootLevel' => arg('rootLevel', 'int'),
				'expandLevelAuto' => arg('expandLevelAuto', 'int'),
				'expandLevelMax' => arg('expandLevelMax', 'int'),
				'glue' => arg('glue', 'dbsafe'),
				'tmplList' => arg('tmplList', 'dbsafe'),
				'tmplItem' => arg('tmplItem', 'dbsafe'),
				'tmplSpecial' => arg('tmplSpecial', 'dbsafe'),
				'specialMode' => arg('specialMode', 'dbsafe'),
				'invisible' => arg('invisible', 'int'),
				'counterReset' => arg('counterReset', 'int'),
		);

		if ($Eresus->db->selectItem($this->table['name'], "`name`='".$item['name']."'"))
		{
			ErrorMessage('Меню с таким именем уже есть');
			HTTP::goback();
		}
		$Eresus->db->insert($this->table['name'], $item);
		HTTP::redirect(arg('submitURL'));
	}
	//------------------------------------------------------------------------------

	/**
	 * Обновление меню
	 *
	 * @return void
	 *
	 * @uses Eresus
	 * @uses HTTP::redirect()
	 * @uses arg()
	 */
	private function update()
	{
		global $Eresus;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('update', 'int')."'");

		$item['name'] = arg('name', 'word');
		$item['caption'] = arg('caption', 'dbsafe');
		$item['root'] = arg('root', 'int');
		$item['rootLevel'] = arg('rootLevel', 'int');
		$item['expandLevelAuto'] = arg('expandLevelAuto', 'int');
		$item['expandLevelMax'] = arg('expandLevelMax', 'int');
		$item['glue'] = arg('glue', 'dbsafe');
		$item['tmplList'] = arg('tmplList', 'dbsafe');
		$item['tmplItem'] = arg('tmplItem', 'dbsafe');
		$item['tmplSpecial'] = arg('tmplSpecial', 'dbsafe');
		$item['specialMode'] = arg('specialMode', 'dbsafe');
		$item['invisible'] = arg('invisible', 'int');
		$item['counterReset'] = arg('counterReset', 'int');

		if ($Eresus->db->selectItem($this->table['name'],
				"`name`='{$item['name']}' AND `id` <> {$item['id']}"))
		{
			ErrorMessage('Меню с таким именем уже есть');
			HTTP::goback();
		}

		$Eresus->db->updateItem($this->table['name'], $item, "`id`='".$item['id']."'");
		HTTP::redirect(arg('submitURL'));
	}
	//------------------------------------------------------------------------------

	/**
	 * Создаёт заготовку для диалогов добавления/изменения меню
	 *
	 * @return array
	 *
	 * @since 2.03
	 */
	private function createDialogTemplate()
	{
		$sections = $this->adminSectionBranch();
		array_unshift($sections[0], 'ТЕКУЩИЙ РАЗДЕЛ');
		array_unshift($sections[1], -1);
		array_unshift($sections[0], 'КОРЕНЬ');
		array_unshift($sections[1], 0);

		$form = array(
			'name' => 'MenusForm',
			'caption' => '',
			'width' => '500px',
			'fields' => array(
				array('type' => 'edit', 'name' => 'name', 'label' => '<b>Имя</b>', 'width' => '100px',
					'comment' => 'для использования в макросах', 'pattern'=>'/^[a-z]\w*$/i',
					'errormsg'=>'Имя должно начинаться с буквы и может содержать только латинские буквы ' .
					'и цифры'),
				array('type' => 'edit', 'name' => 'caption', 'label' => '<b>Название</b>',
					'width' => '100%', 'hint' => 'Для внутреннего использования', 'pattern'=>'/^.+$/',
					'errormsg'=>'Название не может быть пустым'),
				array('type' => 'select', 'name' => 'root', 'label' => 'Корневой раздел',
					'values' => $sections[1], 'items' => $sections[0],
					'extra' =>'onchange="this.form.rootLevel.disabled = this.value != -1"'),
				array('type' =>'edit','name' => 'rootLevel', 'label' => 'Фикс. уровень', 'width' => '20px',
					'comment' => '(0 - текущий уровень)', 'default' => 0, 'disabled' => true),
				array('type' => 'checkbox', 'name' => 'invisible', 'label' => 'Показывать скрытые разделы'),
				array('type' => 'header', 'value' => 'Уровни меню'),
				array('type' => 'edit', 'name' => 'expandLevelAuto', 'label' => 'Всегда показывать',
					'width' => '20px', 'comment' => 'уровней (0 - развернуть все)', 'default' => 0),
				array('type' => 'edit', 'name' => 'expandLevelMax', 'label' => 'Разворачивать максимум',
					'width' => '20px', 'comment' => 'уровней (0 - без ограничений)', 'default' => 0),
				array('type' => 'header', 'value' => 'Шаблоны'),
				array('type' => 'memo', 'name' => 'tmplList', 'label' => 'Шаблон блока одного уровня меню',
					'height' => '3', 'default' => "<ul>\n\t$(items)\n</ul>"),
				array('type' => 'text',
					'value' => 'Макросы:<ul><li><b>$(level)</b> - номер текущего ' .
					'уровня</li><li><b>$(items)</b> - пункты меню</li></ul>'),
				array('type' => 'edit', 'name' => 'glue', 'label' => 'Разделитель пунктов',
					'width' => '100%', 'maxlength' => 255),
				array('type' => 'memo', 'name' => 'tmplItem', 'label' => 'Шаблон пункта меню',
					'height' => '3', 'default' => "<li><a href=\"$(url)\">$(caption)</a>$(submenu)</li>"),
				array('type' => 'memo', 'name' => 'tmplSpecial',
					'label' => 'Специальный шаблон пункта меню', 'height' => '3',
					'default' => "<li class=\"selected\"><a href=\"$(url)\">$(caption)</a>$(submenu)</li>"),
				array('type' => 'text', 'value' => 'Использовать специальный шаблон'),
				array('type' => 'select', 'name' => 'specialMode', 'items' => array(
						'нет',
						'только для выбранного пункта',
						'для раздела, если выбран он или его подпункт',
						'для пунктов, имеющих подпункты'
					)
				),
				array('type' => 'edit', 'name' => 'counterReset', 'label' => 'Сбрасывать счётчик на',
					'width' => '20px', 'comment' => '0 - не сбрасывать', 'default' => 0),
				array('type' => 'divider'),
				array('type'=>'text', 'value' =>
					'Макросы:<ul>'.
					'<li><b>Все элементы страницы</b></li>'.
					'<li><b>$(level)</b> - номер текущего уровня</li>'.
					'<li><b>$(counter)</b> - порядковый номер текущего пункта</li>'.
					'<li><b>$(url)</b> - ссылка</li>'.
					'<li><b>$(submenu)</b> - место для вставки подменю</li>'.
					'<li><b>{%selected?строка1:строка2}</b> - если элемент выбран, вставить строка1, '.
						'иначе строка2</li>'.
					'<li><b>{%parent?строка1:строка2}</b> - если элемент находится среди родительских '.
						'разделов выбранного элемента, вставить строка1, иначе строка2</li>'.
					'</ul>'),
				array('type' => 'divider'),
				array('type' => 'text',
					'value' => 'Для вставки меню используйте макрос <b>$(Menus:имя_меню)</b>'),
				),
			'buttons' => array('ok', 'cancel'),
		);
		return $form;
	}
	//------------------------------------------------------------------------------

	/**
	 * Диалог добавления меню
	 *
	 * @return string
	 *
	 * @uses TAdminUI
	 */
	private function adminAddItem()
	{
		$form = $this->createDialogTemplate();

		$form['caption'] = 'Создать меню';
		$form['fields'] []= array('type' => 'hidden', 'name' => 'action', 'value' => 'insert');
		$result = $GLOBALS['page']->renderForm($form);

		return $result;
	}
	//------------------------------------------------------------------------------

	/**
	 * Диалог изменения меню
	 *
	 * @return string
	 *
	 * @uses Eresus
	 * @uses TAdminUI
	 */
	private function adminEditItem()
	{
		$item = $this->dbItem('', arg('id', 'int'));

		$form = $this->createDialogTemplate();
		$form['caption'] = 'Изменить меню';
		$form['fields'] []= array('type' => 'hidden', 'name' => 'update', 'value' => $item['id']);
		$form['buttons'] = array('ok', 'apply', 'cancel');
		foreach ($form['fields'] as &$field)
		{
			if ('rootLevel' == $field['name'])
			{
				$field['disabled'] = $item['root'] != -1;
				break;
			}
		}

		$result = $GLOBALS['page']->renderForm($form, $item);
		return $result;
	}
	//------------------------------------------------------------------------------

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

	/**
	 * Обрабатывает запрос на переключение активности меню
	 *
	 * @param int $id  ID меню
	 *
	 * @return void
	 *
	 * @uses DB::getHandler
	 * @uses DB::execute
	 * @uses HTTP::redirect
	 */
	private function toggle($id)
	{
		global $page;

		$q = DB::getHandler()->createUpdateQuery();
		$e = $q->expr;
		$q->update($this->table['name'])
			->set('active', $e->not('active'))
			->where($e->eq('id', $q->bindValue($id, null, PDO::PARAM_INT)));
		DB::execute($q);

		HTTP::redirect(str_replace('&amp;', '&', $page->url()));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Удаляет меню
	 *
	 * @param int $id  идентификатор удаляемого меню
	 *
	 * @return void
	 *
	 * @since ?.??
	 */
	private function delete($id)
	{
		global $Eresus, $page;

		$Eresus->db->delete($this->table['name'], "`".$this->table['key']."`='".$id."'");
		HTTP::redirect(str_replace('&amp;', '&', $page->url()));
	}
	//-----------------------------------------------------------------------------

	/**
	 * Построение ветки разделов для диалогов добавления/изменения
	 *
	 * @param int $owner[optional]  Родительский раздел
	 * @param int $level[optional]  Уровень вложенности
	 * @return array
	 *
	 * @uses Eresus
	 */
	private function adminSectionBranch($owner = 0, $level = 0)
	{
		global $Eresus;

		$result = array(array(), array());
		$items = $Eresus->sections->children($owner, GUEST, SECTIONS_ACTIVE);
		if (count($items))
		{
			foreach ($items as $item)
			{
				$result[0][] = str_repeat('- ', $level).$item['caption'];
				$result[1][] = $item['id'];
				$sub = $this->adminSectionBranch($item['id'], $level+1);
				if (count($sub[0]))
				{
					$result[0] = array_merge($result[0], $sub[0]);
					$result[1] = array_merge($result[1], $sub[1]);
				}
			}
		}
		return $result;
	}
	//------------------------------------------------------------------------------

}
