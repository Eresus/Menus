<?php
/**
 * Раздел сайта
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
 * Раздел сайта
 *
 * @package Menus
 * @since 3.01
 */
class Menus_Site_Section
{
    /**
     * Свойства раздела сайта, полученные из БД
     *
     * @var array
     * @since 3.01
     */
    private $data;

    /**
     * Конструктор модели раздела сайта
     *
     * @param array $properties  свойства раздела сайта, полученные из БД
     *
     * @since 3.01
     */
    public function __construct(array $properties)
    {
        $this->data = $properties;
    }

    /**
     * Возвращает URL раздела
     *
     * @return string
     *
     * @since 3.01
     */
    public function getUrl()
    {
        $legacyKernel = Eresus_Kernel::app()->getLegacyKernel();
        /** @var TClientUI $page */
        $page = Eresus_Kernel::app()->getPage();
        /* У разделов типа 'url' собственный механизм построения URL */
        if ('url' == $this->data['type'])
        {
            $item = $legacyKernel->sections->get($this->data['id']);
            $url = $page->replaceMacros($item['content']);
            if (substr($url, 0, 1) == '/')
            {
                $url = $legacyKernel->root . substr($url, 1);
            }
        }
        else
        {
            $url = $page->clientURL($this->data['id']);
            if ($legacyKernel->root . 'main/' == $url)
            {
                $url = $legacyKernel->root;
            }
        }

        return $url;
    }
}

