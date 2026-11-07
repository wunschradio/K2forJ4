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
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

?>
<style>
    #k2QuickIcons .icon i {
        font-size: 28px; /* statt z.B. 36px */
        display: block;
        margin: 5px auto 5px auto;
    }
    a[target="_blank"]::after,
    a[target="_blank"]::before {
        display: none !important;
        content: none !important;
    }
</style>

<div class="clr"></div>

<?php if($modLogo): ?>
<div id="k2QuickIconsTitle">
    <a class="dashicon k2logo" href="<?php echo Route::_('index.php?option=com_k2&amp;view=items&amp;filter_featured=-1&amp;filter_trash=0'); ?>" title="<?php echo Text::_('K2_DASHBOARD'); ?>">
        <span>K2</span>
    </a>
</div>
<?php endif; ?>

<div id="k2QuickIcons" <?php if(!$modLogo): ?> class="k2NoLogo" <?php endif; ?>>
    <div class="px-2 pt-2 pb-0">
        <div class="nav flex-wrap">

<?php
// Welche Symbole erscheinen, steuern die Schalter im Modul (Standard: Neuer Artikel, Artikel, Kategorien,
// Schlagwörter, Medien-Manager). Gilt für diese und die Joomla-Darstellung.
$k2qiAdmin = $user->gid > 23;
$k2qiIcons = [
    ['show_add_item', 1, 'index.php?option=com_k2&view=item', 'item-new', 'K2_ADD_NEW_ITEM', true],
    ['show_items', 1, 'index.php?option=com_k2&view=items&filter_featured=-1&filter_trash=0', 'items', 'K2_ITEMS', true],
    ['show_featured', 0, 'index.php?option=com_k2&view=items&filter_featured=1&filter_trash=0', 'items-featured', 'K2_FEATURED_ITEMS', true],
    ['show_trashed', 0, 'index.php?option=com_k2&view=items&filter_featured=-1&filter_trash=1', 'items-trashed', 'K2_TRASHED_ITEMS', true],
    ['show_categories', 1, 'index.php?option=com_k2&view=categories&filter_trash=0', 'categories', 'K2_CATEGORIES', true],
    ['show_trashed_categories', 0, 'index.php?option=com_k2&view=categories&filter_trash=1', 'categories-trashed', 'K2_TRASHED_CATEGORIES', true],
    ['show_tags', 1, 'index.php?option=com_k2&view=tags', 'tags', 'K2_TAGS', !$componentParams->get('lockTags') || $k2qiAdmin],
    ['show_comments', 0, 'index.php?option=com_k2&view=comments', 'comments', 'K2_COMMENTS', true],
    ['show_extra_fields', 0, 'index.php?option=com_k2&view=extrafields', 'extra-fields', 'K2_EXTRA_FIELDS', $k2qiAdmin],
    ['show_extra_field_groups', 0, 'index.php?option=com_k2&view=extrafieldsgroups', 'extra-field-groups', 'K2_EXTRA_FIELD_GROUPS', $k2qiAdmin],
    ['show_media_manager', 1, 'index.php?option=com_k2&view=media', 'mediamanager', 'K2_MEDIA_MANAGER', true],
    ['show_documentation', 0, 'https://getk2.org/documentation/', 'documentation', 'K2_DOCS_AND_TUTORIALS', true],
];
?>
<?php foreach ($k2qiIcons as [$k2qiParam, $k2qiDefault, $k2qiLink, $k2qiIcon, $k2qiLabel, $k2qiAllowed]): ?>
    <?php if (!$k2qiAllowed || !(int) $params->get($k2qiParam, $k2qiDefault)) continue; ?>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo strpos($k2qiLink, 'http') === 0 ? $k2qiLink : Route::_($k2qiLink); ?>"<?php echo strpos($k2qiLink, 'http') === 0 ? ' target="_blank"' : ''; ?>>
                        <i class="dashicon <?php echo $k2qiIcon; ?>"></i>
                        <span><?php echo Text::_($k2qiLabel); ?></span>
                    </a>
                </div>
            </div>
<?php endforeach; ?>
            <div style="clear: both;"></div>
        </div>
    </div>

</div>

