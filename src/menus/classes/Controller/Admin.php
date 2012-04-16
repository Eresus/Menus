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
	 */
	public function addAction()
	{
		$req = $GLOBALS['Eresus']->request;

		if ('POST' == $req['method'])
		{
			$menu = array(
				'name' => arg('name', 'word'),
				'caption' => arg('caption', 'dbsafe'),
				'active' => true,
				'root' => arg('root', 'int'),
				'rootLevel' => arg('rootLevel', 'int'),
				'invisible' => arg('invisible', 'int'),
				'expandLevelAuto' => arg('expandLevelAuto', 'int'),
				'expandLevelMax' => arg('expandLevelMax', 'int'),
				'template' => arg('template', 'dbsafe'),
			);

			if (!$this->plugin->dbItem('', $menu['name'], 'name'))
			{
				$this->plugin->dbInsert('', $menu);
				HTTP::redirect('admin.php?mod=ext-menus');
			}
			else
			{
				ErrorMessage('Меню с таким именем уже есть');
			}
		}
		else
		{
			$menu = null;
		}

		$form = new EresusForm('ext/' . $this->plugin->name . '/templates/form.html');
		$form->setValue('sections', $this->adminSectionBranch());
		$form->setValue('action', 'create');
		$form->setValue('menu', $menu);
		$html = $form->compile();

		return $html;
	}
	//------------------------------------------------------------------------------

	/**
	 * Изменение меню
	 *
	 * @return string  HTML
	 */
	public function editAction()
	{
		$menu = $this->plugin->dbItem('', arg('id', 'int'));

		if (!$menu)
		{
			return 'Такое меню не найдено.';
		}

		$req = $GLOBALS['Eresus']->request;

		if ('POST' == $req['method'])
		{
			$menu['name'] = arg('name', 'word');
			$menu['caption'] = arg('caption', 'dbsafe');
			$menu['active'] = arg('active');
			$menu['root'] = arg('root', 'int');
			$menu['rootLevel'] = arg('rootLevel', 'int');
			$menu['invisible'] = arg('invisible', 'int');
			$menu['expandLevelAuto'] = arg('expandLevelAuto', 'int');
			$menu['expandLevelMax'] = arg('expandLevelMax', 'int');
			$menu['template'] = arg('template', 'dbsafe');

			if (!$this->plugin->dbSelect('', "name = '{$menu['name']}' AND id <> {$menu['id']}"))
			{
				$this->plugin->dbUpdate('', $menu);
				HTTP::redirect('admin.php?mod=ext-menus');
			}
			else
			{
				ErrorMessage('Меню с таким именем уже есть');
			}
		}

		$form = new EresusForm('ext/' . $this->plugin->name . '/templates/form.html');
		$form->setValue('sections', $this->adminSectionBranch());
		$form->setValue('action', 'edit');
		$form->setValue('menu', $menu);
		$html = $form->compile();

		return $html;
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
	 * Построение ветки разделов для диалогов добавления/изменения
	 *
	 * @param int $owner  Родительский раздел
	 * @param int $level  Уровень вложенности
	 * @return array
	 */
	private function adminSectionBranch($owner = 0, $level = 0)
	{
		$result = array();
		$items = $GLOBALS['Eresus']->sections->children($owner, GUEST, SECTIONS_ACTIVE);
		if (count($items))
		{
			foreach ($items as $item)
			{
				$result []= array(
					'caption' => str_repeat('- ', $level) . $item['caption'],
					'id' => $item['id']
				);
				$sub = $this->adminSectionBranch($item['id'], $level + 1);
				if (count($sub))
				{
					$result = array_merge($result, $sub);
				}
			}
		}
		return $result;
	}
	//------------------------------------------------------------------------------

}
