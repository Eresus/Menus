<?php
/**
 * Menus
 *
 * Меню
 *
 * @version ${product.version}
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
 * @author Михаил Красильников <mihalych@vsepofigu.ru>
 *
 * $Id$
 */


/**
 * Меню
 *
 * @package Menus
 * @since 2.03
 */
class Menus_Menu
{
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
	 * Корневой URL сайта
	 *
	 * @var string
	 */
	protected $rootURL;

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
	 * @param array  $params   параметры меню
	 * @param array  $ids      …
	 * @param string $rootURL  корневой URL сайта
	 *
	 * @return Menus_Menu
	 *
	 * @since 2.03
	 */
	public function __construct(array $params, array $ids, $rootURL)
	{
		$this->params = $params;
		$this->ids = $ids;
		$this->rootURL = $rootURL;
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
			$GLOBALS['page']->clientURL($this->params['root']) :
			$GLOBALS['Eresus']->request['path'];

		/* Определяем допустимый уровень доступа */
		$user = $GLOBALS['Eresus']->user;
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
		preg_match_all('|{%selected\?(.*?):(.*?)}|i', $template, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			$template = str_replace($matches[0][$i], $item['is-selected']?$matches[1][$i]:$matches[2][$i],
				$template);
		}

		preg_match_all('|{%parent\?(.*?):(.*?)}|i', $template, $matches);
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
			$parents = $GLOBALS['Eresus']->sections->parents($GLOBALS['page']->id);
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
		$sections = $GLOBALS['Eresus']->sections;
		$page = $GLOBALS['page'];

		$result = '';
		if (strpos($path, $this->rootURL) !== false)
		{
			$path = substr($path, strlen($this->rootURL));
		}
		if ($ownerId == -1)
		{
			$ownerId = $page->id;
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
		$item['is-selected'] = $item['id'] == $GLOBALS['page']->id;
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
				$currentPath = $GLOBALS['Eresus']->request['path'];
				$itemPath = $GLOBALS['page']->clientURL($item['id']);
				$isItemChildOfCurrent = strpos($currentPath, $itemPath) === 0;
				$isItemAndCurrentAreMain = $this->isMainPage($item) && $currentPath . 'main/' == $itemPath;
				if ($isItemChildOfCurrent || $isItemAndCurrentAreMain)
				{
					$template = $this->params['tmplSpecial'];
				}
			break;

			/* для пунктов, имеющих подпункты */
			case 3:
				if (count($GLOBALS['Eresus']->sections->branch_ids($item['id'])))
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
			$item = $GLOBALS['Eresus']->sections->get($item['id']);
			$url = $GLOBALS['page']->replaceMacros($item['content']);
			if (substr($url, 0, 1) == '/')
			{
				$url = $this->rootURL . substr($url, 1);
			}
		}
		else
		{
			$url = $this->rootURL . $rootURL . ($item['name'] == 'main' ? '' : $item['name'] . '/');
		}

		return $url;
	}
	//-----------------------------------------------------------------------------
}
