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
     * @param Plugin $owner
     * @param TAdminUI $ui
     *
     * @since 3.00
     */
    public function __construct(Plugin $owner, TAdminUI $ui)
    {
        $this->plugin = $owner;
        $this->ui = $ui;
    }

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

    /**
     * Добавление меню
     *
     * @return string
     */
    public function addAction()
    {
        $req = Eresus_CMS::getLegacyKernel()->request;

        if ('POST' == $req['method'])
        {
            $menu = new Menus_Entity_Menu($this->plugin);
            $menu->name = arg('name', 'word');
            $menu->caption = arg('caption', 'dbsafe');
            $menu->active = true;
            $menu->root = arg('root', 'int');
            $menu->rootLevel = arg('rootLevel', 'int');
            $menu->invisible = arg('invisible', 'int');
            $menu->expandLevelAuto = arg('expandLevelAuto', 'int');
            $menu->expandLevelMax = arg('expandLevelMax', 'int');
            $menu->dropDown = arg('dropDown', 'int');
            $menu->template = arg('template', 'dbsafe');

            /** @var Menus_Entity_Table_Menu $table */
            $table = $menu->getTable();
            if (is_null($table->findByName($menu->name)))
            {
                $table->persist($menu);
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

    /**
     * Изменение меню
     *
     * @return string  HTML
     */
    public function editAction()
    {
        /** @var Menus_Entity_Table_Menu $table */
        $table = ORM::getTable($this->plugin, 'Menu');
        /** @var Menus_Entity_Menu $menu */
        $menu = $table->find(arg('id', 'int'));

        if (is_null($menu))
        {
            return 'Такое меню не найдено.';
        }

        $req = Eresus_CMS::getLegacyKernel()->request;

        if ('POST' == $req['method'])
        {
            $menu->name = arg('name', 'word');
            $menu->caption = arg('caption', 'dbsafe');
            $menu->active = true;
            $menu->root = arg('root', 'int');
            $menu->rootLevel = arg('rootLevel', 'int');
            $menu->invisible = arg('invisible', 'int');
            $menu->expandLevelAuto = arg('expandLevelAuto', 'int');
            $menu->expandLevelMax = arg('expandLevelMax', 'int');
            $menu->dropDown = arg('dropDown', 'int');
            $menu->template = arg('template', 'dbsafe');

            $test = $table->findByName($menu->name);
            if (is_null($test) || $test->id == $menu->id)
            {
                $table->update($menu);
                HTTP::redirect('admin.php?mod=ext-menus&id=' . $menu->id);
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
    public function toggleAction($id)
    {
        /** @var Menus_Entity_Table_Menu $table */
        $table = ORM::getTable($this->plugin, 'Menu');
        /** @var Menus_Entity_Menu $menu */
        $menu = $table->find($id);

        if (!is_null($menu))
        {
            $menu->active = !$menu->active;
            $table->update($menu);
        }

        HTTP::redirect(str_replace('&amp;', '&', $this->ui->url()));
    }

    /**
     * Удаляет меню
     *
     * @param int $id  идентификатор удаляемого меню
     *
     * @return void
     */
    public function deleteAction($id)
    {
        /** @var Menus_Entity_Table_Menu $table */
        $table = ORM::getTable($this->plugin, 'Menu');
        /** @var Menus_Entity_Menu $menu */
        $menu = $table->find($id);

        if (!is_null($menu))
        {
            $table->delete($menu);
        }
        HTTP::redirect(str_replace('&amp;', '&', $this->ui->url()));
    }

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
        $items = Eresus_CMS::getLegacyKernel()->sections->children($owner, GUEST, SECTIONS_ACTIVE);
        if (count($items))
        {
            foreach ($items as $item)
            {
                $result [] = array(
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
}

