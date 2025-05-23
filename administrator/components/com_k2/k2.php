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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$params = ComponentHelper::getParams('com_k2');
$user = Factory::getUser();

$option = Factory::getApplication()->input->getCmd('option');
$view = Factory::getApplication()->input->getCmd('view', 'items');
$task = Factory::getApplication()->input->getCmd('task');
$tmpl = Factory::getApplication()->input->getCmd('tmpl');
$context = Factory::getApplication()->input->getCmd('context');

JLoader::register('K2HelperPermissions', JPATH_SITE.'/administrator/components/com_k2/helpers/permissions.php');
K2HelperPermissions::checkPermissions();

// Compatibility for gid variable
if ($user->authorise('core.admin', 'com_k2')) {
    $user->gid = 1000;
} else {
    $user->gid = 1;
}

if (
    ($params->get('lockTags') && !$user->authorise('core.admin', 'com_k2') && ($view=='tags' || $view=='tag')) ||
    (!$user->authorise('core.admin', 'com_k2')) && (
        $view=='extrafield' ||
        $view=='extrafields' ||
        $view=='extrafieldsgroup' ||
        $view=='extrafieldsgroups' ||
        $view=='user' ||
        ($view=='users' && $context != 'modalselector') ||
        $view=='usergroup' ||
        $view=='usergroups'
    )
) {
    throw new \Exception(Text::_('K2_ALERTNOTAUTH'), 403);
}

$document = Factory::getDocument();

$document->setMetadata('theme-color', '#10223e');

K2HelperHTML::loadHeadIncludes(true, true);

// Container CSS class definition
if (K2_JVERSION == '30') {
    $k2CSSContainerClass = ' isJ30'; // isJ25 isJ30
} else {
    $k2CSSContainerClass = '';
}

if (Factory::getApplication()->input->getCmd('context') == "modalselector" || ($view == 'media' && $tmpl == 'component') || $view == 'settings') {
    $k2CSSContainerClass .= ' inModalSelector';
    $k2FooterClass = 'inModalSelector';
} else {
    $k2FooterClass = '';
}

$editForms = array('item', 'category', 'tag', 'user', 'usergroup', 'extrafield', 'extrafieldsgroup');
if (in_array($view, $editForms)) {
    $k2CSSContainerClass .= ' isEditForm';
}

if (
    $document->getType() != 'raw' &&
    $task != 'deleteAttachment' &&
    $task != 'connector' &&
    $task != 'tag' &&
    $task != 'tags' &&
    $task != 'extraFields' &&
    $task != 'download' &&
    $task != 'saveComment' &&
    $context != 'ajax'
) {
    $k2ComponentHeader = '
    <div id="k2AdminContainer" class="K2AdminView'.ucfirst($view).$k2CSSContainerClass.'">
        <div id="k2Sidebar" style="visibility:hidden;">
            <button aria-expanded="false" aria-controls="menu" id="k2ui-menu-control">&#8801;</button>
            '.K2HelperHTML::sidebarMenu().'
            <div id="k2Copyrights">
                <a target="_blank" href="https://getk2.org/">K2 v'.K2_CURRENT_VERSION.K2_BUILD.'</a>
                <div>
                    Copyright &copy; 2006-'.date('Y').' <a target="_blank" href="https://www.joomlaworks.net/">JoomlaWorks Ltd.</a>
                </div>
            </div>
        </div>
        <div id="k2ContentView">
    ';
    $k2ComponentFooter = '
            <div class="k2clr"></div>
        </div>
        '.K2HelperHTML::mobileMenu().'
    </div>';
} else {
    $k2ComponentHeader = '';
    $k2ComponentFooter = '';
}

// Output
echo $k2ComponentHeader;

JLoader::register('K2Controller', JPATH_COMPONENT.'/controllers/controller.php');
JLoader::register('K2View', JPATH_COMPONENT.'/views/view.php');
JLoader::register('K2Model', JPATH_COMPONENT.'/models/model.php');

$controller = strtolower($view);
require_once(JPATH_COMPONENT.'/controllers/'.$controller.'.php');
$classname = 'K2Controller'.ucfirst($controller);
$controller = new $classname();
$controller->registerTask('saveAndNew', 'save');
$controller->execute($task);
$controller->redirect();

echo $k2ComponentFooter;
