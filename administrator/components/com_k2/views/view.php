<?php
/**
 * @version    2.11.x
 * @package    K2
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2006 - 2022 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\Event\Event;

jimport('joomla.application.component.view');

if (!class_exists('K2LoadTemplateEvent')) {
    class K2LoadTemplateEvent extends Event
    {
        public function __construct($view, $tpl = null)
        {
            $app   = Factory::getApplication();
            $input = $app->input;

            $option = $input->getCmd('option', '');
            $vname  = $input->getCmd('view', '');
            $layout = $input->getCmd('layout', '');

            $context = trim($option . '.' . $vname, '.');

            parent::__construct('onLoadTemplate', [
                'view'    => $view,
                'tpl'     => $tpl,
                'context' => $context,
                'layout'  => $layout,
                'option'  => $option,
                'vname'   => $vname,
            ]);
        }

        public function __call($name, $arguments)
        {
            if (strpos($name, 'get') === 0 && strlen($name) > 3) {
                $key = lcfirst(substr($name, 3));
                return $this->getArgument($key);
            }

            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $name));
        }
    }
}

class K2View extends HtmlView
{

    public function _addPath($type, $path)
    {
        $type = (string) $type;
        $path = rtrim((string) $path, DIRECTORY_SEPARATOR);

        if ($path === '') {
            return;
        }

        if (!isset($this->_path)) {
            $this->_path = [];
        }

        if (!isset($this->_path[$type]) || !is_array($this->_path[$type])) {
            $this->_path[$type] = [];
        }

        if (in_array($path, $this->_path[$type], true)) {
            return;
        }

        array_unshift($this->_path[$type], $path);
    }

    public function display($tpl = null)
    {
        $app = Factory::getApplication();

        if ($app->isClient('site') && stripos($app->getTemplate(), 'yootheme') === 0) {

            $dispatcher = $app->getDispatcher();
            if ($dispatcher && method_exists($dispatcher, 'dispatch')) {
                $event = new K2LoadTemplateEvent($this, $tpl);
                $dispatcher->dispatch('onLoadTemplate', $event);
            } else {
                $app->triggerEvent('onLoadTemplate', [$this, $tpl]);
            }

            if (!empty($this->_output)) {
                echo $this->_output;
                return;
            }
        }

        return parent::display($tpl);
    }
}
