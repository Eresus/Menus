<?php
/**
 * Таблица меню
 *
 * @version ${product.version}
 *
 * @copyright 2013, ООО "Два слона", http://dvaslona.ru/
 * @license http://www.gnu.org/licenses/gpl.txt GPL License 3
 * @author Михаил Красильников <mk@dvaslona.ru>
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
 * Таблица меню
 *
 * @package Menus
 * @since x.xx
 */
class Menus_Entity_Table_Menu extends ORM_Table
{
    /**
     * Описание таблицы
     */
    protected function setTableDefinition()
    {
        $this->setTableName($this->plugin->name);
        $this->hasColumns(array(
            'id' => array(
                'type' => 'integer',
                'unsigned' => true,
                'autoincrement' => true
            ),
            'name' => array(
                'type' => 'string',
                'length' => 255,
            ),
            'caption' => array(
                'type' => 'string',
                'length' => 255,
            ),
            'active' => array(
                'type' => 'boolean',
            ),
            'root' => array(
                'type' => 'integer',
                'unsigned' => true,
            ),
            'rootLevel' => array(
                'type' => 'integer',
                'unsigned' => true,
                'default' => 0,
            ),
            'invisible' => array(
                'type' => 'boolean',
                'default' => 0  ,
            ),
            'expandLevelAuto' => array(
                'type' => 'integer',
                'unsigned' => true,
                'default' => 0,
            ),
            'expandLevelMax' => array(
                'type' => 'integer',
                'unsigned' => true,
                'default' => 0,
            ),
            'template' => array(
                'type' => 'string',
                'length' => 65535,
            ),
        ));
        $this->index('client', array('fields' => array('name', 'active')));
        $this->index('admin', array('fields' => array('name')));
    }

    /**
     * Возвращает меню с указанным именем
     *
     * @param string $name
     *
     * @return Menus_Entity_Menu|null
     */
    public function findByName($name)
    {
        $q = $this->createSelectQuery();
        $q->where($q->expr->eq('name', $q->bindValue($name, ':name')));
        return $this->loadOneFromQuery($q);
    }
}

