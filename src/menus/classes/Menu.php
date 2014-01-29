<?php
/**
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
 */


/**
 * Меню
 *
 * Отвечает за построение и отрисовку конкретного меню в КИ.
 *
 * @package Menus
 * @since 2.03
 */
class Menus_Menu
{
    /**
     * Объект Eresus
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
     * Меню
     *
     * @var Menus_Entity_Menu
     * @since 2.03
     */
    protected $menu;

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
     * Флаги фильтрации разделов
     *
     * @var int
     */
    protected $sectionsFilter;

    /**
     * Шаблон одного уровня меню
     *
     * @var Eresus_Template
     * @since 3.00
     */
    protected $template;

    /**
     * Конструктор меню
     *
     * @param Eresus            $Eresus
     * @param TClientUI         $ui
     * @param Menus_Entity_Menu $menu
     * @param array             $ids
     *
     * @return Menus_Menu
     *
     * @since 2.03
     */
    public function __construct(Eresus $Eresus, TClientUI $ui, Menus_Entity_Menu $menu, array $ids)
    {
        $this->Eresus = $Eresus;
        $this->ui = $ui;
        $this->menu = $menu;
        $this->ids = $ids;
    }

    /**
     * Возвращает разметку меню
     *
     * @return string  HTML
     *
     * @since 2.03
     */
    public function render()
    {
        $this->template = new Eresus_Template();
        $this->template->setSource($this->menu->template);

        $this->detectRoot();
        $path = $this->menu->root > -1 ?
            $this->ui->clientURL($this->menu->root) :
            $this->Eresus->request['path'];

        /* Определяем допустимый уровень доступа */
        $user = $this->Eresus->user;
        $this->accessThreshold = $user['auth'] ? $user['access'] : GUEST;
        // Определяем условия фильтрации
        $this->sectionsFilter = SECTIONS_ACTIVE | ($this->menu->invisible ? 0 : SECTIONS_VISIBLE);

        $html = $this->renderBranch($this->menu->root, $path);

        return $html;
    }

    /**
     * Определяет идентификатор корневого раздела меню
     *
     * @return void
     *
     * @since 2.03
     */
    protected function detectRoot()
    {
        if ($this->menu->root == -1 && $this->menu->rootLevel)
        {
            $parents = $this->Eresus->sections->parents($this->ui->id);
            $level = count($parents);
            if ($level == $this->menu->rootLevel)
            {
                $this->menu->root = -1;
            }
            elseif ($level > $this->menu->rootLevel)
            {
                $this->menu->root = $parents[$this->menu->rootLevel];
            }
            else
            {
                $this->menu->root = -2;
            }
        }
    }

    /**
     * Построение ветки меню
     *
     * @param int $ownerId  идентификатор родительского раздела
     * @param string $path     URL родительского раздела
     * @param int $level    Уровень вложенности
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

        $vars = array('menuName' => $this->menu->name, 'level' => $level);
        $vars['isDropDown'] = $this->menu->dropDown > 1 && $level >= $this->menu->dropDown;
        $vars['items'] = $sections->children($ownerId, $this->accessThreshold, $this->sectionsFilter);

        $html = '';
        if (count($vars['items']))
        {

            foreach ($vars['items'] as &$item)
            {
                $section = new Menus_Site_Section($item);
                $item['level'] = $level;
                $item['url'] = $section->getUrl();
                $item['isCurrent'] = $item['id'] == $this->ui->id;
                $item['isOpened'] = !$item['isCurrent'] && in_array($item['id'], $this->ids);

                // true если раздел находится в выбранной ветке
                $inSelectedBranch = $item['isOpened'] || $item['isCurrent'];
                // true если не достигнут максимальный уровень ручного развёртывания
                $notMaxExpandLevel = !$this->menu->expandLevelMax ||
                    $item['level'] < $this->menu->expandLevelMax;
                // true если не достигнут максимальный уровень автоматического развёртывания
                $notMaxAutoExpandLevel = !$this->menu->expandLevelAuto ||
                    $item['level'] < $this->menu->expandLevelAuto;

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
}

