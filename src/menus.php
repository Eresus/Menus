<?php
/**
 * Menus
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
 */

/**
 * Класс плагина
 *
 * @package Menus
 */
class Menus extends Eresus_Plugin
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
    public $kernel = '3.01a';

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
    public $settings = array();

    /**
     * Описание таблицы БД и списка меню
     *
     * @var array
     */
    public $table = array(
        'name' => 'menus',
        'key' => 'id',
        'sortMode' => 'id',
        'sortDesc' => false,
        'columns' => array(
            array('name' => 'caption', 'caption' => 'Название'),
            array('name' => 'name', 'caption' => 'Имя'),
        ),
        'controls' => array(
            'delete' => '',
            'edit' => '',
            'toggle' => '',
        ),
        'tabs' => array(
            'width' => '180px',
            'items' => array(
                array('caption' => 'Создать меню', 'name' => 'action', 'value' => 'create')
            ),
        ),
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

    /**
     * Вывод АИ плагина
     *
     * @return string  HTML
     */
    public function adminRender()
    {
        $result = '';
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
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

    /**
     * Сбор информации о текущем разделе
     *
     * @param array $item
     * @param string $url
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameters)
     */
    public function clientOnURLSplit($item, $url)
    {
        $this->pages[] = $item;
        $this->ids[] = $item['id'];
    }

    /**
     * Поиск и подстановка меню
     *
     * @param string $text
     * @return string
     */
    public function clientOnPageRender($text)
    {
        $Eresus = Eresus_CMS::getLegacyKernel();

        preg_match_all('/\$\(Menus:(.+)?\)/Usi', $text, $menus, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        if (count($menus))
        {
            /** @var TClientUI $page */
            $page = Eresus_Kernel::app()->getPage();
            $page->linkStyles($this->getCodeURL() . 'client/menus.css');
            $page->linkJsLib('jquery');
            $page->linkScripts($this->getCodeURL() . 'client/menus.js');

            $delta = 0;

            $relative = substr($Eresus->request['url'], strlen($Eresus->root), 5);

            if ($relative && $relative != 'main/')
            {
                array_shift($this->ids);
            }

            /** @var Menus_Entity_Table_Menu $table */
            $table = ORM::getTable($this, 'Menu');
            for ($i = 0; $i < count($menus); $i++)
            {
                $entity = $table->findByName($menus[$i][1][0]);
                if ($entity && $entity->active)
                {
                    $menu = new Menus_Menu($Eresus, $page, $entity, $this->ids);
                    $html = $menu->render();
                    $text = substr_replace($text, $html, $menus[$i][0][1] + $delta, strlen($menus[$i][0][0]));
                    $delta += strlen($html) - strlen($menus[$i][0][0]);
                }
            }
        }
        return $text;
    }

    /**
     * Добавление пункта в меню "Расширения"
     */
    public function adminOnMenuRender()
    {
        /** @var TAdminUI $page */
        $page = Eresus_Kernel::app()->getPage();
        $page->addMenuItem(admExtensions,
            array('access' => ADMIN, 'link' => $this->name, 'caption' => $this->title,
                'hint' => $this->description));
    }

    /**
     * Действия при установке модуля
     */
    public function install()
    {
        parent::install();
        $driver = ORM::getManager()->getDriver();
        $driver->createTable(ORM::getTable($this, 'Menu'));
    }

    /**
     * Действия при установке модуля
     */
    public function uninstall()
    {
        $driver = ORM::getManager()->getDriver();
        $driver->dropTable(ORM::getTable($this, 'Menu'));
        parent::uninstall();
    }
}

