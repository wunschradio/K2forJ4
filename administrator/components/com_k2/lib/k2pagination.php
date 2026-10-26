<?php
/**
 * @version    2.x (rolling release)
 * @package    K2
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2009 - 2026 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL: https://gnu.org/licenses/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Pagination\Pagination;

/**
 * K2 Pagination Class
 * Extends Joomla's Pagination to provide custom limit box options
 */
class K2Pagination extends Pagination
{
    /**
     * Create and return the pagination limit box, with custom K2 limit values.
     *
     * @return  string  Pagination limit box HTML.
     */
    public function getLimitBox()
    {
        $app = Factory::getApplication();

        $limits = [];
        foreach ([20, 50, 100, 200, 300, 400, 500, 1000] as $value) {
            $limits[] = HTMLHelper::_('select.option', (string) $value, (string) $value);
        }

        // Expose option "ALL" to site administrators only.
        // This is to prevent browser crashes for less experienced users attempting to list thousands of items in one go.
        if ($app->getIdentity() && $app->getIdentity()->authorise('core.admin')) {
            $limits[] = HTMLHelper::_('select.option', '0', Text::_('JALL'));
        }

        $selected = $this->viewall ? 0 : $this->limit;

        // Build the select list
        $onchange = $app->isClient('administrator') ? 'Joomla.submitform();' : 'this.form.submit()';

        return HTMLHelper::_(
            'select.genericlist',
            $limits,
            $this->prefix . 'limit',
            'class="form-select inputbox input-mini" size="1" onchange="' . $onchange . '"',
            'value',
            'text',
            $selected
        );
    }
}
