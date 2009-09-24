<?php
/**
 * Menus
 *
 * Eresus 2, PHP 4.3.0
 *
 * ���������� ����
 *
 * @version 1.05
 *
 * @copyright   2007-2008, Eresus Group, http://eresus.ru/
 * @license     http://www.gnu.org/licenses/gpl.txt  GPL License 3
 * @maintainer  Mikhail Krasilnikov <mk@procreat.ru>
 * @author      Mikhail Krasilnikov <mk@procreat.ru>
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
 * $Id$
 */
class TMenus extends TListContentPlugin {
	var $name = 'menus';
	var $title = '���������� ����';
	var $type = 'client,admin';
	var $version = '1.05';
	var $kernel = '2.10rc';
	var $description = '�������� ����';
	var $table = array (
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
			`glue` varchar(63) default '',
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
	var $settings = array(
	);
	var $menu = null;
	var $pages = array(); # ���� �� ���������
	var $ids = array(); # ���� �� ��������� (������ ��������������)

 /**
	* �����������
	*/
	function TMenus()
	{
		global $Eresus;

		parent::TListContentPlugin();
		$Eresus->plugins->events['clientOnURLSplit'][] = $this->name;
		$Eresus->plugins->events['clientOnPageRender'][] = $this->name;
		$Eresus->plugins->events['adminOnMenuRender'][] = $this->name;
	}
	//------------------------------------------------------------------------------
 /**
	* ���������� ����
	*/
	function insert()
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

		$Eresus->db->insert($this->table['name'], $item);
		sendNotify('��������� ����: '.$item['caption']);
		goto(arg('submitURL'));
	}
	//------------------------------------------------------------------------------
	/**
	 * ���������� ����
	 *
	 * @see core/classes/backward/TContentPlugin#update()
	 */
	function update()
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

		$Eresus->db->updateItem($this->table['name'], $item, "`id`='".$item['id']."'");
		sendNotify('�������� ����: '.$item['caption']);
		goto(arG('submitURL'));
	}
	//------------------------------------------------------------------------------
	/**
	 * ������ ��������
	 *
	 * @param string $template  ������
	 * @param array  $item      �������-�������� ������
	 * @return string
	 *
	 * @see core/classes/backward/TPlugin#replaceMacros($template, $item)
	 */
	function replaceMacros($template, $item)
	{
		preg_match_all('|{%selected\?(.*?):(.*?)}|i', $template, $matches);
		for($i = 0; $i < count($matches[0]); $i++)
			$template = str_replace($matches[0][$i], $item['is-selected']?$matches[1][$i]:$matches[2][$i], $template);

		preg_match_all('|{%parent\?(.*?):(.*?)}|i', $template, $matches);
		for($i = 0; $i < count($matches[0]); $i++)
			$template = str_replace($matches[0][$i], $item['is-parent']?$matches[1][$i]:$matches[2][$i], $template);

		$template = parent::replaceMacros($template, $item);

		return $template;
	}
	//------------------------------------------------------------------------------
	/**
	 * ���������� ����� �������� ��� �������� ����������/���������
	 *
	 * @param int $owner[optional]  ������������ ������
	 * @param int $level[optional]  ������� �����������
	 * @return array
	 */
	function adminSectionBranch($owner = 0, $level = 0)
	{
		global $Eresus;

		$result = array(array(), array());
		$items = $Eresus->sections->children($owner, GUEST, SECTIONS_ACTIVE);
		if (count($items)) foreach($items as $item) {
			$result[0][] = str_repeat('- ', $level).$item['caption'];
			$result[1][] = $item['id'];
			$sub = $this->adminSectionBranch($item['id'], $level+1);
			if (count($sub[0])) {
				$result[0] = array_merge($result[0], $sub[0]);
				$result[1] = array_merge($result[1], $sub[1]);
			}
		}
		return $result;
	}
	//------------------------------------------------------------------------------
	/**
	 * ���������� ����� ����
	 *
	 * @param int    $owner[optional]  ������������� ������������� �������
	 * @param string $path[optional]   URI ������������� �������
	 * @param int    $level[optional]  ������� �����������
	 * @return string
	 */
	function menuBranch($owner = 0, $path = '', $level = 1)
	{
		global $Eresus, $page;

		$result = '';
		if (strpos($path, httpRoot) !== false) $path = substr($path, strlen(httpRoot));
		if ($owner == -1) $owner = $page->id;
		# ���������� ���������� ������� �������
		$access = $Eresus->user['auth'] ? $Eresus->user['access'] : GUEST;
		# ���������� ������� ����������
		$flags = SECTIONS_ACTIVE | ( $this->menu['invisible'] ? 0 : SECTIONS_VISIBLE );
		$items = $Eresus->sections->children($owner, $access, $flags);

		if (count($items)) {

			$result = array();
			$counter = 1;

			foreach($items as $item) {

				$template = $this->menu['tmplItem'];
				/*
				 * � �������� ���� 'url' ����������� �������� ���������� URI
				 */
				if ($item['type'] == 'url') {

					$item = $Eresus->sections->get($item['id']);
					$item['url'] = $page->replaceMacros($item['content']);
					if (substr($item['url'], 0, 1) == '/') $item['url'] = httpRoot.substr($item['url'], 1);

				} else {

					$item['url'] = httpRoot.$path.($item['name']=='main'?'':$item['name'].'/');

				}

				$item['level'] = $level;
				$item['is-selected'] = $item['id'] == $page->id;
				$item['is-parent'] = !$item['is-selected'] && in_array($item['id'], $this->ids);
				#var_dump($item['caption']);

				# true ���� ������ ��������� � ��������� �����
				$inSelectedBranch = $item['is-parent'] || $item['is-selected'];
				# true ���� �� ��������� ������������ ������� ������� ������������
				$notMaxExpandLevel = !$this->menu['expandLevelMax'] || $level < $this->menu['expandLevelMax'];
				# true ���� �� ��������� ������������ ������� ��������������� ������������
				$notMaxAutoExpandLevel = !$this->menu['expandLevelAuto'] || $level < $this->menu['expandLevelAuto'];

				if ($notMaxAutoExpandLevel || ($inSelectedBranch && $notMaxExpandLevel)) {
					$item['submenu'] = $this->menuBranch($item['id'], $path.$item['name'].'/', $level+1);
				}
				switch ($this->menu['specialMode']) {
					case 0: # ���
					break;
					case 1: # ������ ��� ���������� ������
						if ($item['is-selected']) $template = $this->menu['tmplSpecial'];
					break;
					case 2: # ��� ���������� ������ ���� ������ ��� ��������
						if ((strpos($Eresus->request['path'], $page->clientURL($item['id'])) === 0) && $item['name'] != 'main') $template = $this->menu['tmplSpecial'];
					break;
					case 3: # ��� �������, ������� ���������
						if (count($Eresus->sections->branch_ids($item['id'])))
							$template = $this->menu['tmplSpecial'];
					break;
				}
				$item['counter'] = $counter++;
				if ($this->menu['counterReset'] && $counter > $this->menu['counterReset']) $counter = 1;
				$result[] = $this->replaceMacros($template, $item);

			}
			$result = implode($this->menu['glue'], $result);
			$result = array('level'=> ($level), 'items'=>$result);
			$result = $this->replaceMacros($this->menu['tmplList'], $result);
		}
		return $result;
	}
	//------------------------------------------------------------------------------
	/**
	 * ������ ���������� ����
	 *
	 * @return string
	 */
	function adminAddItem()
	{
		global $page;

		$sections = $this->adminSectionBranch();
		array_unshift($sections[0], '������� ������');
		array_unshift($sections[1], -1);
		array_unshift($sections[0], '������');
		array_unshift($sections[1], 0);

		$form = array(
			'name' => 'FormCreate',
			'caption' => '������� ����',
			'width' => '500px',
			'fields' => array (
				array('type'=>'hidden','name'=>'action', 'value'=>'insert'),
				array('type'=>'edit','name'=>'name','label'=>'<b>���</b>', 'width' => '100px', 'comment' => '��� ������������� � ��������', 'pattern'=>'/[a-z]\w*/i', 'errormsg'=>'��� ������ ���������� � ����� � ����� ��������� ������ ��������� ����� � �����'),
				array('type'=>'edit','name'=>'caption','label'=>'<b>��������</b>', 'width' => '100%', 'hint' => '��� ����������� �������������', 'pattern'=>'/.+/i', 'errormsg'=>'�������� �� ����� ���� ������'),
				array('type'=>'select','name'=>'root','label'=>'�������� ������', 'values'=>$sections[1], 'items'=>$sections[0], 'extra' =>'onchange="this.form.rootLevel.disabled = this.value != -1"'),
				array('type'=>'edit','name'=>'rootLevel','label'=>'����. �������', 'width' => '20px', 'comment' => '(0 - ������� �������)', 'default' => 0, 'disabled' => true),
				array('type'=>'checkbox','name'=>'invisible','label'=>'���������� ������� �������'),
				array('type'=>'header', 'value'=>'������ ����'),
				array('type'=>'edit','name'=>'expandLevelAuto','label'=>'������ ����������', 'width' => '20px', 'comment' => '������� (0 - ���������� ���)', 'default' => 0),
				array('type'=>'edit','name'=>'expandLevelMax','label'=>'������������� ��������', 'width' => '20px', 'comment' => '������� (0 - ��� �����������)', 'default' => 0),
				array('type'=>'header', 'value'=>'�������'),
				array('type'=>'memo','name'=>'tmplList','label'=>'������ ����� ������ ������ ����', 'height' => '3', 'default' => "<ul>\n\t$(items)\n</ul>"),
				array('type'=>'text', 'value' => '�������:<ul><li><b><li><b>$(level)</b> - ����� �������� ������</li><li><b>$(items)</b> - ������ ����</li></ul>'),
				array('type'=>'edit','name'=>'glue','label'=>'����������� �������', 'width' => '100%', 'maxlength' => 63),
				array('type'=>'memo','name'=>'tmplItem','label'=>'������ ������ ����', 'height' => '3', 'default' => "<li><a href=\"$(url)\">$(caption)</a></li>"),
				array('type'=>'memo','name'=>'tmplSpecial','label'=>'����������� ������ ������ ����', 'height' => '3', 'default' => "<li class=\"selected\"><a href=\"$(url)\">$(caption)</a></li>"),
				array('type'=>'text', 'value' => '������������ ����������� ������'),
				array('type'=>'select','name'=>'specialMode','items'=>array(
					'���',
					'������ ��� ���������� ������',
					'��� ���������� ������ ���� ������ ��� ��������',
					'��� �������, ������� ���������'
					)
				),
				array('type'=>'edit','name'=>'counterReset','label'=>'���������� ������� ��', 'width' => '20px', 'comment' => '0 - �� ����������', 'default' => 0),
				array('type'=>'divider'),
				array('type'=>'text', 'value' =>
					'�������:<ul>'.
					'<li><b>��� �������� ��������</b></li>'.
					'<li><b>$(level)</b> - ����� �������� ������</li>'.
					'<li><b>$(counter)</b> - ���������� ����� �������� ������</li>'.
					'<li><b>$(url)</b> - ������</li>'.
					'<li><b>$(submenu)</b> - ����� ��� ������� �������</li>'.
					'<li><b>{%selected?������1:������2}</b> - ���� ������� ������, �������� ������1, ����� ������2</li>'.
					'<li><b>{%parent?������1:������2}</b> - ���� ������� ��������� ����� ������������ �������� ���������� ��������, �������� ������1, ����� ������2</li>'.
					'</ul>'),
				array('type'=>'divider'),
				array('type'=>'text', 'value' => '��� ������� ���� ����������� ������ <b>$(Menus:���_����)</b>'),
			),
			'buttons' => array('ok', 'cancel'),
		);
		$result = $page->renderForm($form);

		return $result;
	}
	//------------------------------------------------------------------------------
	/**
	 * ������ ��������� ����
	 *
	 * @return string
	 */
	function adminEditItem()
	{
		global $page, $Eresus;

		$item = $Eresus->db->selectItem($this->table['name'], "`id`='".arg('id', 'int')."'");
		$sections = $this->adminSectionBranch();
		array_unshift($sections[0], '������� ������');
		array_unshift($sections[1], -1);
		array_unshift($sections[0], '������');
		array_unshift($sections[1], 0);
		$form = array(
			'name' => 'FormEdit',
			'caption' => '�������� ����',
			'width' => '500px',
			'fields' => array (
				array('type'=>'hidden','name'=>'update', 'value'=>$item['id']),
				array('type'=>'edit','name'=>'name','label'=>'<b>���</b>', 'width' => '100px', 'comment' => '��� ������������� � ��������', 'pattern'=>'/[a-z]\w*/i', 'errormsg'=>'��� ������ ���������� � ����� � ����� ��������� ������ ��������� ����� � �����'),
				array('type'=>'edit','name'=>'caption','label'=>'<b>��������</b>', 'width' => '100%', 'hint' => '��� ����������� �������������', 'pattern'=>'/.+/i', 'errormsg'=>'�������� �� ����� ���� ������'),
				array('type'=>'select','name'=>'root','label'=>'�������� ������', 'values'=>$sections[1], 'items'=>$sections[0], 'extra' =>'onchange="this.form.rootLevel.disabled = this.value != -1"'),
				array('type'=>'edit','name'=>'rootLevel','label'=>'����. �������', 'width' => '20px', 'comment' => '(0 - ������� �������)', 'default' => 0, 'disabled' => $item['root'] != -1),
				array('type'=>'header', 'value'=>'������ ����'),
				array('type'=>'edit','name'=>'expandLevelAuto','label'=>'������ ����������', 'width' => '20px', 'comment' => '������� (0 - ���������� ���)', 'default' => 0),
				array('type'=>'edit','name'=>'expandLevelMax','label'=>'������������� ��������', 'width' => '20px', 'comment' => '������� (0 - ��� �����������)', 'default' => 0),
				array('type'=>'checkbox','name'=>'invisible','label'=>'���������� ������� �������'),
				array('type'=>'header', 'value'=>'�������'),
				array('type'=>'memo','name'=>'tmplList','label'=>'������ ����� ������ ������ ����', 'height' => '3'),
				array('type'=>'text', 'value' => '�������:<ul><li><b><li><b>$(level)</b> - ����� �������� ������</li><li><b>$(items)</b> - ������ ����</li></ul>'),
				array('type'=>'edit','name'=>'glue','label'=>'����������� �������', 'width' => '100%', 'maxlength' => 63),
				array('type'=>'memo','name'=>'tmplItem','label'=>'������ ������ ����', 'height' => '3'),
				array('type'=>'memo','name'=>'tmplSpecial','label'=>'����������� ������ ������ ����', 'height' => '3'),
				array('type'=>'text', 'value' => '������������ ����������� ������'),
				array('type'=>'select','name'=>'specialMode','items'=>array(
					'���',
					'������ ��� ���������� ������',
					'��� ���������� ������ ���� ������ ��� ��������',
					'��� �������, ������� ���������'
					)
				),
				array('type'=>'edit','name'=>'counterReset','label'=>'���������� ������� ��', 'width' => '20px', 'comment' => '0 - �� ����������'),
				array('type'=>'divider'),
				array('type'=>'text', 'value' =>
					'�������:<ul>'.
					'<li><b>��� �������� ��������</b></li>'.
					'<li><b>$(level)</b> - ����� �������� ������</li>'.
					'<li><b>$(counter)</b> - ���������� ����� �������� ������</li>'.
					'<li><b>$(url)</b> - ������</li>'.
					'<li><b>$(submenu)</b> - ����� ��� ������� �������</li>'.
					'<li><b>{%selected?������1:������2}</b> - ���� ������� ������, �������� ������1, ����� ������2</li>'.
					'<li><b>{%parent?������1:������2}</b> - ���� ������� ��������� ����� ������������ �������� ���������� ��������, �������� ������1, ����� ������2</li>'.
					'</ul>'),
				array('type'=>'divider'),
				array('type'=>'text', 'value' => '��� ������� ���� ����������� ������ <b>$(Menus:���_����)</b>'),
			),
			'buttons' => array('ok', 'apply', 'cancel'),
		);
		$result = $page->renderForm($form, $item);
		return $result;
	}
	//------------------------------------------------------------------------------
	/**
	 * ����� �� �������
	 *
	 * @return string
	 */
	function adminRender()
	{
		$result = $this->adminRenderContent();
		return $result;
	}
	//------------------------------------------------------------------------------
	/**
	 * ���� ���������� � ������� �������
	 *
	 * @param array  $item
	 * @param string $url
	 */
	function clientOnURLSplit($item, $url)
	{
		$this->pages[] = $item;
		$this->ids[] = $item['id'];
	}
	//------------------------------------------------------------------------------
	/**
	 * ����� � ����������� ����
	 *
	 * @param string $text
	 * @return string
	 */
	function clientOnPageRender($text)
	{
		global $Eresus, $page;

		preg_match_all('/\$\(Menus:(.+)?\)/Usi', $text, $menus, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		$delta = 0;

		$relative = substr($Eresus->request['url'], strlen($Eresus->root), 5);
		if ($relative && $relative != 'main/') array_shift($this->ids);
		for($i = 0; $i < count($menus); $i++) {
			$this->menu = $Eresus->db->selectItem($this->table['name'], "`name`='".$menus[$i][1][0]."' AND `active` = 1");
			if (!is_null($this->menu)) {
				if ($this->menu['root'] == -1 && $this->menu['rootLevel']) {
					$parents = $Eresus->sections->parents($page->id);
					$level = count($parents);
					if ($level == $this->menu['rootLevel']) $this->menu['root'] = -1;
					elseif ($level > $this->menu['rootLevel']) $this->menu['root'] = $this->menu['root'] = $parents[$this->menu['rootLevel']];
					else $this->menu['root'] = -2;
				}
				$path = $this->menu['root'] > -1 ? $page->clientURL($this->menu['root']) : $Eresus->request['path'];
				$menu = $this->menuBranch($this->menu['root'], $path);
				$text = substr_replace($text, $menu, $menus[$i][0][1]+$delta, strlen($menus[$i][0][0]));
				$delta += strlen($menu) - strlen($menus[$i][0][0]);
			}
		}
		return $text;
	}
	//------------------------------------------------------------------------------
  /**
   * ���������� ������ � ���� "����������"
   */
	function adminOnMenuRender()
	{
		global $page;

		$page->addMenuItem(admExtensions, array ('access'  => ADMIN, 'link'  => $this->name, 'caption'  => $this->title, 'hint'  => $this->description));
	}
	//------------------------------------------------------------------------------
}