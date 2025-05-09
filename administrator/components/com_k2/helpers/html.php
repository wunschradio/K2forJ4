<?php
/**
 * @version    2.11.x
 * @package    K2
 * @author     JoomlaWorks https://www.joomlaworks.net
 * @copyright  Copyright (c) 2006 - 2022 JoomlaWorks Ltd. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;
use Joomla\CMS\Filesystem\File;

class K2HelperHTML
{
    public static function activeMenu($current)
    {
        $view = Factory::getApplication()->input->getCmd('view', 'items');
        if ($current === $view) {
            return ' class="active"';
        }
    }

    public static function sidebarMenu()
    {
        $params = ComponentHelper::getParams('com_k2');
        $user = Factory::getUser();
        $view = Factory::getApplication()->input->getCmd('view');

        $editForms = array('item', 'category', 'tag', 'user', 'usergroup', 'extrafield', 'extrafieldsgroup');

        $sidebarMenu = '';

        if (in_array($view, $editForms)) {
            $sidebarMenu = '
            <ul class="k2-disabled">
                <li>
                    <span>' . Text::_('K2_ITEMS') . '</span>
                </li>
                <li>
                    <span>' . Text::_('K2_CATEGORIES') . '</span>
                </li>
            ';
            if (!$params->get('lockTags') || $user->gid > 23) {
                $sidebarMenu .= '
                <li>
                    <span>' . Text::_('K2_TAGS') . '</span>
                </li>
                ';
            }
            $sidebarMenu .= '
                <li>
                    <span>' . Text::_('K2_COMMENTS') . '</span>
                </li>
            ';
            if ($user->gid > 23) {
                $sidebarMenu .= '
                <li>
                    <span>' . Text::_('K2_USERS') . '</span>
                </li>
                <li>
                    <span>' . Text::_('K2_USER_GROUPS') . '</span>
                </li>
                <li>
                    <span>' . Text::_('K2_EXTRA_FIELDS') . '</span>
                </li>
                <li>
                    <span>' . Text::_('K2_EXTRA_FIELD_GROUPS') . '</span>
                </li>
                ';
            }
            $sidebarMenu .= '
                <li>
                    <span>' . Text::_('K2_MEDIA_MANAGER') . '</span>
                </li>
                <li>
                    <span>' . Text::_('K2_INFORMATION') . '</span>
                </li>
            ';
            if ($user->gid > 23) {
                $sidebarMenu .= '
                <li>
                    <span>' . Text::_('K2_SETTINGS') . '</span>
                </li>
                ';
            }
            $sidebarMenu .= '
            </ul>
            ';
        } else {
            $sidebarMenu = '
            <ul>
                <li' . self::activeMenu('items') . '>
                    <a href="index.php?option=com_k2&amp;view=items">' . Text::_('K2_ITEMS') . '</a>
                </li>
                <li' . self::activeMenu('categories') . '>
                    <a href="index.php?option=com_k2&amp;view=categories">' . Text::_('K2_CATEGORIES') . '</a>
                </li>
            ';
            if (!$params->get('lockTags') || $user->gid > 23) {
                $sidebarMenu .= '
                <li' . self::activeMenu('tags') . '>
                    <a href="index.php?option=com_k2&amp;view=tags">' . Text::_('K2_TAGS') . '</a>
                </li>
                ';
            }
            $sidebarMenu .= '
                <li' . self::activeMenu('comments') . '>
                    <a href="index.php?option=com_k2&amp;view=comments">' . Text::_('K2_COMMENTS') . '</a>
                </li>
            ';
            if ($user->gid > 23) {
                $sidebarMenu .= '
                <li' . self::activeMenu('users') . '>
                    <a href="index.php?option=com_k2&amp;view=users">' . Text::_('K2_USERS') . '</a>
                </li>
                <li' . self::activeMenu('usergroups') . '>
                    <a href="index.php?option=com_k2&amp;view=usergroups">' . Text::_('K2_USER_GROUPS') . '</a>
                </li>
                <li' . self::activeMenu('extrafields') . '>
                    <a href="index.php?option=com_k2&amp;view=extrafields">' . Text::_('K2_EXTRA_FIELDS') . '</a>
                </li>
                <li' . self::activeMenu('extrafieldsgroups') . '>
                    <a href="index.php?option=com_k2&amp;view=extrafieldsgroups">' . Text::_('K2_EXTRA_FIELD_GROUPS') . '</a>
                </li>
                ';
            }
            $sidebarMenu .= '
                <li' . self::activeMenu('media') . '>
                    <a href="index.php?option=com_k2&amp;view=media">' . Text::_('K2_MEDIA_MANAGER') . '</a>
                </li>
                <li' . self::activeMenu('info') . '>
                    <a href="index.php?option=com_k2&amp;view=info">' . Text::_('K2_INFORMATION') . '</a>
                </li>
            ';
            if ($user->gid > 23) {
                $settingsURL = 'index.php?option=com_config&view=component&component=com_k2&path=&return=' . urlencode(base64_encode(Uri::getInstance()->toString()));
                $settingsURLAttributes = '';
                $sidebarMenu .= '
                <li>
                    <a href="' . $settingsURL . '"' . $settingsURLAttributes . '>' . Text::_('K2_SETTINGS') . '</a>
                </li>
                ';
            }
            $sidebarMenu .= '
            </ul>
            ';
        }

        return $sidebarMenu;
    }

    public static function mobileMenu()
    {
        $params = ComponentHelper::getParams('com_k2');
        $user = Factory::getUser();
        $view = Factory::getApplication()->input->getCmd('view');
        $context = Factory::getApplication()->input->getCmd('context');

        $editForms = array('item', 'category', 'tag', 'user', 'usergroup', 'extrafield', 'extrafieldsgroup', 'media');

        $mobileMenu = '';

        if (!in_array($view, $editForms) && $context != 'modalselector' && $view != 'settings') {
            $mobileMenu = '
            <div id="k2AdminMobileMenu">
                <ul>
                    <li' . self::activeMenu('items') . '>
                        <a href="index.php?option=com_k2&amp;view=items"><i class="fa fa-list-alt" aria-hidden="true"></i><span>' . Text::_('K2_ITEMS') . '</span></a>
                    </li>
                    <li' . self::activeMenu('categories') . '>
                        <a href="index.php?option=com_k2&amp;view=categories"><i class="fa fa-folder-open-o" aria-hidden="true"></i><span>' . Text::_('K2_CATEGORIES') . '</span></a>
                    </li>
                    <li class="k2ui-add">
                        <a href="index.php?option=com_k2&amp;view=item"><i class="fa fa-plus-square-o" aria-hidden="true"></i><span>' . Text::_('K2_ADD_ITEM') . '</span></a>
                    </li>
            ';
            if (!$params->get('lockTags') || $user->gid > 23) {
                $mobileMenu .= '
                    <li' . self::activeMenu('tags') . '>
                        <a href="index.php?option=com_k2&amp;view=tags"><i class="fa fa-tags" aria-hidden="true"></i><span>' . Text::_('K2_TAGS') . '</span></a>
                    </li>
                ';
            }
            $mobileMenu .= '
                    <li' . self::activeMenu('comments') . '>
                        <a href="index.php?option=com_k2&amp;view=comments"><i class="fa fa-comments-o" aria-hidden="true"></i><span>' . Text::_('K2_COMMENTS') . '</span></a>
                    </li>
                </ul>
            </div>
            ';
        }

        return $mobileMenu;
    }

    public static function subMenu()
    {
        return; /* Disable the old sidebar menu */

        $params = ComponentHelper::getParams('com_k2');
        $user = Factory::getUser();
        $view = Factory::getApplication()->input->getCmd('view');

        JHtmlSidebar::addEntry(Text::_('K2_ITEMS'), 'index.php?option=com_k2&view=items', $view == 'items');
        JHtmlSidebar::addEntry(Text::_('K2_CATEGORIES'), 'index.php?option=com_k2&view=categories', $view == 'categories');
        if (!$params->get('lockTags') || $user->gid > 23) {
            JHtmlSidebar::addEntry(Text::_('K2_TAGS'), 'index.php?option=com_k2&view=tags', $view == 'tags');
        }
        JHtmlSidebar::addEntry(Text::_('K2_COMMENTS'), 'index.php?option=com_k2&view=comments', $view == 'comments');
        if ($user->gid > 23) {
            JHtmlSidebar::addEntry(Text::_('K2_USERS'), 'index.php?option=com_k2&view=users', $view == 'users');
            JHtmlSidebar::addEntry(Text::_('K2_USER_GROUPS'), 'index.php?option=com_k2&view=usergroups', $view == 'usergroups');
            JHtmlSidebar::addEntry(Text::_('K2_EXTRA_FIELDS'), 'index.php?option=com_k2&view=extrafields', $view == 'extrafields');
            JHtmlSidebar::addEntry(Text::_('K2_EXTRA_FIELD_GROUPS'), 'index.php?option=com_k2&view=extrafieldsgroups', $view == 'extrafieldsgroups');
        }
        JHtmlSidebar::addEntry(Text::_('K2_MEDIA_MANAGER'), 'index.php?option=com_k2&view=media', $view == 'media');
        JHtmlSidebar::addEntry(Text::_('K2_INFORMATION'), 'index.php?option=com_k2&view=info', $view == 'info');
    }

    public static function stateToggler(&$row, $key, $property = 'published', $tasks = array('publish', 'unpublish'), $labels = array('K2_PUBLISH', 'K2_UNPUBLISH'))
    {
        $task = $row->$property ? $tasks[1] : $tasks[0];
        $action = $row->$property ? Text::_($labels[1]) : Text::_($labels[0]);
        $class = 'k2Toggler';
        $status = $row->$property ? 'k2Active' : 'k2Inactive';
        $href = '<a class="' . $class . ' ' . $status . '" href="javascript:void(0);" onclick="return Joomla.listItemTask(\'cb' . $key . '\',\'' . $task . '\')" title="' . $action . '">' . $action . '</a>';
        return $href;
    }

    public static function loadHeadIncludes($loadFramework = false, $jQueryUI = false, $adminHeadIncludes = false, $adminModuleIncludes = false)
    {
        $app = Factory::getApplication();
        $document = Factory::getDocument();
        $user = Factory::getUser();

        $params = K2HelperUtilities::getParams('com_k2');

        $option = Factory::getApplication()->input->getCmd('option');
        $view = strtolower(Factory::getApplication()->input->getWord('view', 'items'));
        $task = Factory::getApplication()->input->getCmd('task');

        $getSiteLanguage = Factory::getLanguage();
        $languageTag = substr($getSiteLanguage->getTag(), 0, 2);

        $jQueryHandling = $params->get('jQueryHandling', '1.9.1');

        if ($document->getType() == 'html') {
            // JS framework loading

            if ($loadFramework && $view != 'media') {
                // removed in j4 HTMLHelper::_('behavior.framework');
            }

            HTMLHelper::_('jquery.framework');

            // jQueryUI
            if ($jQueryUI) {
                // Load version 1.8.24 for tabs & sortables (called the "old" way)...
                if (($option == 'com_k2' && ($view == 'item' || $view == 'category')) || $option == 'com_menus') {
                    $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.8.24/jquery-ui.min.js');
                }

                // Load latest version for the "media" view & modules only
                if (($option == 'com_k2' && $view == 'media') || $option == 'com_modules' || $option == 'com_advancedmodules') {
                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.min.css');
                    $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js');
                }
            }

            // Everything else...
            if ($app->isClient('administrator') || $adminHeadIncludes) {
                // JS
                $isBackend = ($app->isClient('administrator')) ? ' k2IsBackend' : '';
                $isTask = ($task) ? ' k2TaskIs' . ucfirst($task) : '';
                $cssClass = 'isJ' . K2_JVERSION . ' k2ViewIs' . ucfirst($view) . '' . $isTask . '' . $isBackend;
                $document->addScriptDeclaration("

                    // Set K2 version as global JS variable
                    K2Version = '" . K2_JVERSION . "';

                    // Set Joomla version as class in the 'html' tag
                    (function(){
                        var addedClass = '" . $cssClass . "';
                        if (document.getElementsByTagName('html')[0].className !== '') {
                            document.getElementsByTagName('html')[0].className += ' '+addedClass;
                        } else {
                            document.getElementsByTagName('html')[0].className = addedClass;
                        }
                    })();

                    // K2 Language Strings
                    var K2_THE_ENTRY_IS_ALREADY_IN_THE_LIST = '" . Text::_('K2_THE_ENTRY_IS_ALREADY_IN_THE_LIST', true) . "';
                    var K2_REMOVE_THIS_ENTRY = '" . Text::_('K2_REMOVE_THIS_ENTRY', true) . "';
                    var K2_THE_ENTRY_WAS_ADDED_IN_THE_LIST = '" . Text::_('K2_THE_ENTRY_WAS_ADDED_IN_THE_LIST', true) . "';

                ");
                $document->addScript(URI::root(true) . '/media/k2/assets/js/k2.backend.js?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID . '&sitepath=' . URI::root(true) . '/');

                // NicEdit
                if ($option == 'com_k2' && $view == 'item') {
                    $document->addScript(URI::root(true) . '/media/k2/assets/vendors/bkirchoff/nicedit/nicEdit.js?v=' . K2_CURRENT_VERSION);
                }

                // Media (elFinder)
                if ($view == 'media') {
                    $document->addStyleSheet(URI::root(true) . '/media/k2/assets/vendors/studio-42/elfinder/css/elfinder.min.css?v=' . K2_CURRENT_VERSION);
                    $document->addStyleSheet(URI::root(true) . '/media/k2/assets/vendors/studio-42/elfinder/css/theme.css?v=' . K2_CURRENT_VERSION);
                    $document->addScript(URI::root(true) . '/media/k2/assets/vendors/studio-42/elfinder/js/elfinder.min.js?v=' . K2_CURRENT_VERSION);
                } else {
                    HTMLHelper::_('bootstrap.tooltip');
                    if ($params->get('taggingSystem') === '0' || $params->get('taggingSystem') === '1') {
                        // B/C - Convert old options
                        $whichTaggingSystem = ($params->get('taggingSystem')) ? 'free' : 'selection';
                        $params->set('taggingSystem', $whichTaggingSystem);
                    }
                    // avoid chosen css conflicts on joomla 4
                    if (version_compare(JVERSION, '4.0.0-dev', 'lt')){
                        if ($view == 'item' && $params->get('taggingSystem') == 'selection') {
                            HTMLHelper::_('formbehavior.chosen', 'select:not(#selectedTags, #tags)');
                        } else {
                            HTMLHelper::_('formbehavior.chosen', 'select');
                        }
                    }
                }

                // Flatpickr
                if ($view == 'item' || $view == 'extrafield') {
                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.5.7/flatpickr.min.css');
                    $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.5.7/flatpickr.min.js');
                    if ($languageTag != 'en') {
                        if ($languageTag == 'el') {
                            $languageTag = 'gr';
                        }
                        $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.5.7/l10n/' . $languageTag . '.js');
                        $document->addScriptDeclaration('
                            /* K2 - Flatpickr Localization */
                            flatpickr.localize(flatpickr.l10ns.' . $languageTag . ');
                        ');
                    }
                    $document->addCustomTag('<!--[if IE 9]><link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.5.7/ie.css" /><![endif]-->');
                }

                // Magnific Popup
                $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css');
                $document->addStyleDeclaration('
                    /* K2 - Magnific Popup Overrides */
                    .mfp-iframe-holder {padding:10px;}
                    .mfp-iframe-holder .mfp-content {max-width:100%;width:100%;height:100%;}
                    .mfp-iframe-scaler iframe {background:#fff;padding:10px;box-sizing:border-box;box-shadow:none;}
                ');
                $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js');

                // Fancybox
                if (in_array($view, array('item', 'items', 'category', 'categories', 'user', 'users'))) {
                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css');
                    $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js');
                }

                // CSS
                if ($option == 'com_k2' || $adminModuleIncludes) {
                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
                }
                if ($option == 'com_k2') {
                    $document->addStyleSheet('https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,600;1,700;1,800&display=swap');
                    $document->addStyleSheet(URI::root(true) . '/media/k2/assets/css/k2.backend.css?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID);
                    if (version_compare(JVERSION, '4.0.0-dev', 'ge'))
                    {

                        $document->addStyleSheet(URI::root(true) . '/media/k2/assets/css/k2.j4.backend.css?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID);
                    }
                }
                if ($adminModuleIncludes) {
                    $document->addStyleSheet(URI::root(true) . '/media/k2/assets/css/k2.global.css?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID);
                }
            }

            // Frontend only
            if ($app->isClient('site')) {
                // Magnific Popup
//                if (!$user->guest || ($option == 'com_k2' && $view == 'item') || defined('K2_JOOMLA_MODAL_REQUIRED')) {
//                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css');
//                    $document->addStyleDeclaration('
//                        /* K2 - Magnific Popup Overrides */
//                        .mfp-iframe-holder {padding:10px;}
//                        .mfp-iframe-holder .mfp-content {max-width:100%;width:100%;height:100%;}
//                        .mfp-iframe-scaler iframe {background:#fff;padding:10px;box-sizing:border-box;box-shadow:none;}
//                    ');
//                    $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js');
//                }

                // JS
                $document->addScript(URI::root(true) . '/media/k2/assets/js/k2.frontend.js?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID . '&sitepath=' . URI::root(true) . '/');

                // Add related CSS to the <head>
                if ($params->get('enable_css')) {
                    jimport('joomla.filesystem.file');
                    $template = Factory::getApplication()->input->getCmd('template');

                    // Simple Line Icons
                    $document->addStyleSheet('https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css');

                    // k2.css
                    if (isset($template) && File::exists(JPATH_SITE . '/templates/' . $template . '/css/k2.css')) {
                        $document->addStyleSheet(URI::root(true) . '/templates/' . $template . '/css/k2.css?v=' . K2_CURRENT_VERSION);
                    } elseif (File::exists(JPATH_SITE . '/templates/' . $app->getTemplate() . '/css/k2.css')) {
                        $document->addStyleSheet(URI::root(true) . '/templates/' . $app->getTemplate() . '/css/k2.css?v=' . K2_CURRENT_VERSION);
                    } else {
                        $document->addStyleSheet(URI::root(true) . '/components/com_k2/css/k2.css?v=' . K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID);
                    }

                    // k2.print.css
                    if (Factory::getApplication()->input->getInt('print') == 1) {
                        if (isset($template) && File::exists(JPATH_SITE . '/templates/' . $template . '/css/k2.print.css')) {
                            HTMLHelper::stylesheet(URI::root() . '/templates/' . $template . '/css/k2.print.css', ['relative' => true, 'version' => K2_CURRENT_VERSION]);
                        } elseif (File::exists(JPATH_SITE . '/templates/' . $app->getTemplate() . '/css/k2.print.css')) {
                            HTMLHelper::stylesheet(URI::root() . '/templates/' . $app->getTemplate() . '/css/k2.print.css', ['relative' => true, 'version' => K2_CURRENT_VERSION]);
                        } else {
                            HTMLHelper::stylesheet(URI::root() . 'components/com_k2/css/k2.print.css', ['relative' => true, 'version' => K2_CURRENT_VERSION . '&b=' . K2_BUILD_ID]);
                        }
                    }
                }
            }
        }
    }
}
