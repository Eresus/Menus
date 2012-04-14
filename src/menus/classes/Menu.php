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
	 * Замена макросов
	 *
	 * @param string $template  Шаблон
	 * @param array  $item      Элемент-источник данных
	 *
	 * @return string  HTML
	 */
	protected function replaceMacros($template, $item)
	{
		preg_match_all('|{%selected\?(.*?):(.*?)}|Ui', $template, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$template = str_replace($matches[0][$i], $item['is-selected']?$matches[1][$i]:$matches[2][$i],
				$template);
		}

		preg_match_all('|{%parent\?(.*?):(.*?)}|Ui', $template, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$template = str_replace($matches[0][$i], $item['is-parent']?$matches[1][$i]:$matches[2][$i],
				$template);
		}

		$template = replaceMacros($template, $item);

		return $template;
	}
	//------------------------------------------------------------------------------

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

		$result = '';
		if (strpos($path, $this->Eresus->root) !== false)
		{
			$path = substr($path, strlen($this->Eresus->root));
		}
		if ($ownerId == -1)
		{
			$ownerId = $this->ui->id;
		}
		$rootItem = $sections->get($ownerId);
		if ($this->isMainPage($rootItem))
		{
			$path = 'main/';
		}

		$items = $sections->children($ownerId, $this->accessThreshold, $this->sectionsFilter);

		if (count($items))
		{
			$result = array();
			$counter = 1;

			foreach ($items as $item)
			{
				$item['level'] = $level;
				$item['counter'] = $counter++;
				if ($this->params['counterReset'] && $counter > $this->params['counterReset'])
				{
					$counter = 1;
				}

				$result[] = $this->renderItem($item, $path);
			}
			$result = implode($this->params['glue'], $result);
			$result = array('level' => ($level), 'items' => $result);
			$result = $this->replaceMacros($this->params['tmplList'], $result);
		}
		return $result;
	}
	//------------------------------------------------------------------------------

	/**
	 * Отрисовывает пункт меню
	 *
	 * @param array  $item     описание пункта меню
	 * @param string $rootURL  корневой URL
	 *
	 * @return string
	 *
	 * @since 2.03
	 */
	protected function renderItem(array $item, $rootURL)
	{
		$item['is-main'] = $this->isMainPage($item);
		$item['url'] = $this->buildURL($item, $rootURL);
		$item['is-selected'] = $item['id'] == $this->ui->id;
		$item['is-parent'] = !$item['is-selected'] && in_array($item['id'], $this->ids);

		// true если раздел находится в выбранной ветке
		$inSelectedBranch = $item['is-parent'] || $item['is-selected'];
		// true если не достигнут максимальный уровень ручного развёртывания
		$notMaxExpandLevel = !$this->params['expandLevelMax'] ||
			$item['level'] < $this->params['expandLevelMax'];
		// true если не достигнут максимальный уровень автоматического развёртывания
		$notMaxAutoExpandLevel = !$this->params['expandLevelAuto'] ||
			$item['level'] < $this->params['expandLevelAuto'];

		if ($notMaxAutoExpandLevel || ($inSelectedBranch && $notMaxExpandLevel))
		{
			$item['submenu'] =
				$this->renderBranch($item['id'], $rootURL . $item['name'] . '/', $item['level'] + 1);
		}

		$template = $this->getTemplate($item);

		return $this->replaceMacros($template, $item);
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает true если переданный раздел — главная страница
	 *
	 * @param array $item
	 *
	 * @return bool
	 *
	 * @since 2.03
	 */
	protected function isMainPage(array $item)
	{
		return 'main' == $item['name'] && 0 == $item['owner'];
	}
	//-----------------------------------------------------------------------------

	/**
	 * Возвращает шаблон для пункта меню
	 *
	 * @param array $item  описание пункта меню
	 *
	 * @return string  HTML
	 *
	 * @since 2.03
	 */
	protected function getTemplate(array $item)
	{
		$template = $this->params['tmplItem'];

		switch ($this->params['specialMode'])
		{
			/* только для выбранного пункта */
			case 1:
				if ($item['is-selected'])
				{
					$template = $this->params['tmplSpecial'];
				}
			break;

			/* для выбранного пункта если выбран его подпункт */
			case 2:
				$currentPath = $this->Eresus->request['path'];
				$itemPath = $this->ui->clientURL($item['id']);
				$isItemChildOfCurrent = strpos($currentPath, $itemPath) === 0;
				$isItemAndCurrentAreMain = $this->isMainPage($item) && $currentPath . 'main/' == $itemPath;
				if ($isItemChildOfCurrent || $isItemAndCurrentAreMain)
				{
					$template = $this->params['tmplSpecial'];
				}
			break;

			/* для пунктов, имеющих подпункты */
			case 3:
				if (count($this->Eresus->sections->branch_ids($item['id'])))
				{
					$template = $this->params['tmplSpecial'];
				}
			break;
		}

		return $template;
	}
	//-----------------------------------------------------------------------------

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
