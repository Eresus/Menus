<?php
/**
 * Menus
 *
 * Меню
 *
 * @version ${product.version}
 *
 * @copyright 2011, ООО "Два слона", http://dvaslona.ru/
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
 * $Id$
 */


/**
 * Меню
 *
 * Класс отвечает за построение и отрисовку конкретного меню в КИ.
 *
 * @package Menus
 * @since 2.03
 */
class Menus_Menu
{
	/**
	 * Объект Ereus
	 *
	 * @var Eresus
	 * @since 3.00
	 */
	protected $Eresus;

	/**
	 * Объект КИ
	 *
	 * @var TClientUI
	 * @since 3.00
	 */
	protected $ui;

	/**
	 * Параметры меню
	 *
	 * @var array
	 * @since 2.03
	 */
	protected $params;

	/**
	 * …
	 *
	 * @var array
	 * @since 2.03
	 */
	protected $ids;

	/**
	 * Порог доступа к разделам
	 *
	 * @var int
	 */
	protected $accessThreshold;

	/**
	 * Флаги фильтраии разделов
	 *
	 * @var int
	 */
	protected $sectionsFilter;

	/**
	 * Шаблон одного уровня меню
	 *
	 * @var Menus_Template
	 * @since 3.00
	 */
	protected $template;

	/**
	 * Конструктор меню
	 *
	 * @param Eresus    $Eresus
	 * @param TClientUI $ui
	 * @param array     $params   параметры меню
	 * @param array     $ids      …
	 *
	 * @return Menus_Menu
	 *
	 * @since 2.03
	 */
	public function __construct(Eresus $Eresus, TClientUI $ui, array $params, array $ids)
	{
		$this->Eresus = $Eresus;
		$this->ui = $ui;
		$this->params = $params;
		$this->ids = $ids;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает разметку меню
	 *
	 * @return string  HTML
	 *
	 * @since 2.03
	 */
	public function render()
	{
		$this->template = new Menus_Template();
		$this->template->loadFromString($this->params['template']);

		$this->detectRoot();
		$path = $this->params['root'] > -1 ?
			$this->ui->clientURL($this->params['root']) :
			$this->Eresus->request['path'];

		/* Определяем допустимый уровень доступа */
		$user = $this->Eresus->user;
		$this->accessThreshold = $user['auth'] ? $user['access'] : GUEST;

		// Определяем условия фильтрации
		$this->sectionsFilter = SECTIONS_ACTIVE | ( $this->params['invisible'] ? 0 : SECTIONS_VISIBLE );

		$html = $this->renderBranch($this->params['root'], $path);

		return $html;
	}
	//-----------------------------------------------------------------------------

	/**
	 * Определяет идентифкатор корневого раздела меню
	 *
	 * @return void
	 *
	 * @since 2.03
	 */
	protected function detectRoot()
	{
		if ($this->params['root'] == -1 && $this->params['rootLevel'])
		{
			$parents = $this->Eresus->sections->parents($this->ui->id);
			$level = count($parents);
			if ($level == $this->params['rootLevel'])
			{
				$this->params['root'] = -1;
			}
			elseif ($level > $this->params['rootLevel'])
			{
				$this->params['root'] = $this->params['root'] = $parents[$this->params['rootLevel']];
			}
			else
			{
				$this->params['root'] = -2;
			}
		}
	}
	//-----------------------------------------------------------------------------

	/**
	 * Построение ветки меню
	 *
	 * @param int    $ownerId  идентификатор родительского раздела
	 * @param string $path     URL родительского раздела
	 * @param int    $level    Уровень вложенности
	 *
	 * @return string  HTML
	 *
	 * @uses Eresus
	 * @uses TClientUI
	 */
	protected function renderBranch($ownerId = 0, $path = '', $level = 1)
	{
		$sections = $this->Eresus->sections;

		if (strpos($path, $this->Eresus->root) !== false)
		{
			$path = substr($path, strlen($this->Eresus->root));
		}
		if ($ownerId == -1)
		{
			$ownerId = $this->ui->id;
		}
		$rootItem = $sections->get($ownerId);
		if ($rootItem && 0 == $rootItem['owner'] && 'main' == $rootItem['name'])
		{
			$path = 'main/';
		}

		$vars = array('menuName' => $this->params['name'], 'level' => $level);
		$vars['items'] = $sections->children($ownerId, $this->accessThreshold, $this->sectionsFilter);

		$html = '';
		if (count($vars['items']))
		{

			foreach ($vars['items'] as &$item)
			{
				$item['level'] = $level;
				$item['url'] = $this->buildURL($item, $path);
				$item['isCurrent'] = $item['id'] == $this->ui->id;
				$item['isOpened'] = !$item['isCurrent'] && in_array($item['id'], $this->ids);

				// true если раздел находится в выбранной ветке
				$inSelectedBranch = $item['isOpened'] || $item['isCurrent'];
				// true если не достигнут максимальный уровень ручного развёртывания
				$notMaxExpandLevel = !$this->params['expandLevelMax'] ||
				$item['level'] < $this->params['expandLevelMax'];
				// true если не достигнут максимальный уровень автоматического развёртывания
				$notMaxAutoExpandLevel = !$this->params['expandLevelAuto'] ||
				$item['level'] < $this->params['expandLevelAuto'];

				if ($notMaxAutoExpandLevel || ($inSelectedBranch && $notMaxExpandLevel))
				{
					$item['submenu'] =
						$this->renderBranch($item['id'], $path . $item['name'] . '/', $item['level'] + 1);
				}
			}
			$html = $this->template->compile($vars);
		}
		return $html;
	}
	//------------------------------------------------------------------------------

	/**
	 * Строит URL для пункта меню
	 *
	 * @param array  $item     описание пункта меню
	 * @param string $rootURL  корневой URL
	 *
	 * @return string
	 *
	 * @since 2.03
	 */
	protected function buildURL(array $item, $rootURL)
	{
		/* У разделов типа 'url' собственный механизм построения URL */
		if ($item['type'] == 'url')
		{
			$item = $this->Eresus->sections->get($item['id']);
			$url = $this->ui->replaceMacros($item['content']);
			if (substr($url, 0, 1) == '/')
			{
				$url = $this->Eresus->root . substr($url, 1);
			}
		}
		else
		{
			$url = $this->Eresus->root . $rootURL . ($item['name'] == 'main' ? '' : $item['name'] . '/');
		}

		return $url;
	}
	//-----------------------------------------------------------------------------
}
