<?php
/**
 * Menus
 *
 * ���������� ����
 *
 * @version ${product.version}
 *
 * @copyright 2007, Eresus Group, http://eresus.ru/
 * @copyright 2010, ��� "��� �����", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @author Mikhail Krasilnikov <mk@procreat.ru>
 *
 * ������ ��������� �������� ��������� ����������� ������������. ��
 * ������ �������������� �� �/��� �������������� � ������������ �
 * ��������� ������ 3 ���� �� ������ ������ � ��������� ����� �������
 * ������ ����������� ������������ �������� GNU, �������������� Free
 * Software Foundation.
 *
 * �� �������������� ��� ��������� � ������� �� ��, ��� ��� ����� ���
 * ��������, ������ �� ������������� �� ��� ������� ��������, � ���
 * ����� �������� ��������� ��������� ��� ������� � ����������� ���
 * ������������� � ���������� �����. ��� ��������� ����� ���������
 * ���������� ������������ �� ����������� ������������ ��������� GNU.
 *
 * @package Menus
 *
 * $Id$
 */

/**
 * ����� �������
 *
 * @package Menus
 */
class Menus extends Plugin
{
	/**
	 * �������� �������
	 *
	 * @var string
	 */
	public $title = '���������� ����';

	/**
	 * ��� �������
	 *
	 * @var string
	 */
	public $type = 'client,admin';

	/**
	 * ������ �������
	 *
	 * @var string
	 */
	public $version = '${product.version}';

	/**
	 * ��������� ������ ����
	 * @var string
	 */
	public $kernel = '2.12';

	/**
	 * �������� �������
	 *
	 * @var string
	 */
	public $description = '�������� ����';

	/**
	 * ���������
	 *
	 * @var array
	 */
	public $settings = array(
	);

	/**
	 * �������� ������� �� � ������ ����
	 *
	 * @var array
	 */
	private $table = array (
		'name' => 'menus',
		'key'=> 'id',
		'sortMode' => 'id',
		'sortDesc' => false,
		'columns' => array(
			array('name' => 'caption', 'caption' => '��������'),
			array('name' => 'name', 'caption' => '���'),
		),
		'controls' => array (
			'delete' => '',
			'edit' => '',
			'toggle' => '',
		),
		'tabs' => array(
			'width'=>'180px',
			'items'=>array(
				array('caption'=>'������� ����', 'name'=>'action', 'value'=>'create')
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
	 * ���� �� �������� � �������� �������
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * ���� �� �������� � �������� ������� (������ ��������������)
	 *
	 * @var array
	 */
	private $ids = array();

	/**
	 * �����������
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
	 * ����� �� �������
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
	 * ���� ���������� � ������� �������
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
	 * ����� � ����������� ����
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
	 * ���������� ������ � ���� "����������"
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
	 * ���������� ����
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
			ErrorMessage('���� � ����� ������ ��� ����');
			HTTP::goback();
		}
		$Eresus->db->insert($this->table['name'], $item);
		HTTP::redirect(arg('submitURL'));
	}
	//------------------------------------------------------------------------------

	/**
	 * ���������� ����
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
			ErrorMessage('���� � ����� ������ ��� ����');
			HTTP::goback();
		}

		$Eresus->db->updateItem($this->table['name'], $item, "`id`='".$item['id']."'");
		HTTP::redirect(arg('submitURL'));
	}
	//------------------------------------------------------------------------------

	/**
	 * ������ ��������� ��� �������� ����������/��������� ����
	 *
	 * @return array
	 *
	 * @since 2.03
	 */
	private function createDialogTemplate()
	{
		$sections = $this->adminSectionBranch();
		array_unshift($sections[0], '������� ������');
		array_unshift($sections[1], -1);
		array_unshift($sections[0], '������');
		array_unshift($sections[1], 0);

		$form = array(
			'name' => 'MenusForm',
			'caption' => '',
			'width' => '500px',
			'fields' => array(
				array('type' => 'edit', 'name' => 'name', 'label' => '<b>���</b>', 'width' => '100px',
					'comment' => '��� ������������� � ��������', 'pattern'=>'/^[a-z]\w*$/i',
					'errormsg'=>'��� ������ ���������� � ����� � ����� ��������� ������ ��������� ����� ' .
					'� �����'),
				array('type' => 'edit', 'name' => 'caption', 'label' => '<b>��������</b>',
					'width' => '100%', 'hint' => '��� ����������� �������������', 'pattern'=>'/^.+$/',
					'errormsg'=>'�������� �� ����� ���� ������'),
				array('type' => 'select', 'name' => 'root', 'label' => '�������� ������',
					'values' => $sections[1], 'items' => $sections[0],
					'extra' =>'onchange="this.form.rootLevel.disabled = this.value != -1"'),
				array('type' =>'edit','name' => 'rootLevel', 'label' => '����. �������', 'width' => '20px',
					'comment' => '(0 - ������� �������)', 'default' => 0, 'disabled' => true),
				array('type' => 'checkbox', 'name' => 'invisible', 'label' => '���������� ������� �������'),
				array('type' => 'header', 'value' => '������ ����'),
				array('type' => 'edit', 'name' => 'expandLevelAuto', 'label' => '������ ����������',
					'width' => '20px', 'comment' => '������� (0 - ���������� ���)', 'default' => 0),
				array('type' => 'edit', 'name' => 'expandLevelMax', 'label' => '������������� ��������',
					'width' => '20px', 'comment' => '������� (0 - ��� �����������)', 'default' => 0),
				array('type' => 'header', 'value' => '�������'),
				array('type' => 'memo', 'name' => 'tmplList', 'label' => '������ ����� ������ ������ ����',
					'height' => '3', 'default' => "<ul>\n\t$(items)\n</ul>"),
				array('type' => 'text',
					'value' => '�������:<ul><li><b>$(level)</b> - ����� �������� ' .
					'������</li><li><b>$(items)</b> - ������ ����</li></ul>'),
				array('type' => 'edit', 'name' => 'glue', 'label' => '����������� �������',
					'width' => '100%', 'maxlength' => 255),
				array('type' => 'memo', 'name' => 'tmplItem', 'label' => '������ ������ ����',
					'height' => '3', 'default' => "<li><a href=\"$(url)\">$(caption)</a>$(submenu)</li>"),
				array('type' => 'memo', 'name' => 'tmplSpecial',
					'label' => '����������� ������ ������ ����', 'height' => '3',
					'default' => "<li class=\"selected\"><a href=\"$(url)\">$(caption)</a>$(submenu)</li>"),
				array('type' => 'text', 'value' => '������������ ����������� ������'),
				array('type' => 'select', 'name' => 'specialMode', 'items' => array(
						'���',
						'������ ��� ���������� ������',
						'��� �������, ���� ������ �� ��� ��� ��������',
						'��� �������, ������� ���������'
					)
				),
				array('type' => 'edit', 'name' => 'counterReset', 'label' => '���������� ������� ��',
					'width' => '20px', 'comment' => '0 - �� ����������', 'default' => 0),
				array('type' => 'divider'),
				array('type'=>'text', 'value' =>
					'�������:<ul>'.
					'<li><b>��� �������� ��������</b></li>'.
					'<li><b>$(level)</b> - ����� �������� ������</li>'.
					'<li><b>$(counter)</b> - ���������� ����� �������� ������</li>'.
					'<li><b>$(url)</b> - ������</li>'.
					'<li><b>$(submenu)</b> - ����� ��� ������� �������</li>'.
					'<li><b>{%selected?������1:������2}</b> - ���� ������� ������, �������� ������1, '.
						'����� ������2</li>'.
					'<li><b>{%parent?������1:������2}</b> - ���� ������� ��������� ����� ������������ '.
						'�������� ���������� ��������, �������� ������1, ����� ������2</li>'.
					'</ul>'),
				array('type' => 'divider'),
				array('type' => 'text',
					'value' => '��� ������� ���� ����������� ������ <b>$(Menus:���_����)</b>'),
				),
			'buttons' => array('ok', 'cancel'),
		);
		return $form;
	}
	//------------------------------------------------------------------------------

	/**
	 * ������ ���������� ����
	 *
	 * @return string
	 *
	 * @uses TAdminUI
	 */
	private function adminAddItem()
	{
		$form = $this->createDialogTemplate();

		$form['caption'] = '������� ����';
		$form['fields'] []= array('type' => 'hidden', 'name' => 'action', 'value' => 'insert');
		$result = $GLOBALS['page']->renderForm($form);

		return $result;
	}
	//------------------------------------------------------------------------------

	/**
	 * ������ ��������� ����
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
		$form['caption'] = '�������� ����';
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
	 * ������������ ������ �� ������������ ���������� ����
	 *
	 * @param int $id  ID ����
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
	 * ������� ����
	 *
	 * @param int $id  ������������� ���������� ����
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
	 * ���������� ����� �������� ��� �������� ����������/���������
	 *
	 * @param int $owner[optional]  ������������ ������
	 * @param int $level[optional]  ������� �����������
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
