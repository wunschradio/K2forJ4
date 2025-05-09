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

            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=item'); ?>">
                        <i class="dashicon item-new"></i>
                        <span><?php echo Text::_('K2_ADD_NEW_ITEM'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=items&amp;filter_featured=-1&amp;filter_trash=0'); ?>">
                        <i class="dashicon items"></i>
                        <span><?php echo Text::_('K2_ITEMS'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=items&amp;filter_featured=1&amp;filter_trash=0'); ?>">
                        <i class="dashicon items-featured"></i>
                        <span><?php echo Text::_('K2_FEATURED_ITEMS'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=items&amp;filter_featured=-1&amp;filter_trash=1'); ?>">
                        <i class="dashicon items-trashed"></i>
                        <span><?php echo Text::_('K2_TRASHED_ITEMS'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=categories&amp;filter_trash=0'); ?>">
                        <i class="dashicon categories"></i>
                        <span><?php echo Text::_('K2_CATEGORIES'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=categories&amp;filter_trash=1'); ?>">
                        <i class="dashicon categories-trashed"></i>
                        <span><?php echo Text::_('K2_TRASHED_CATEGORIES'); ?></span>
                    </a>
                </div>
            </div>
			<?php if(!$componentParams->get('lockTags') || $user->gid>23): ?>
                <div class="icon-wrapper">
                    <div class="icon">
                        <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=tags'); ?>">
                            <i class="dashicon tags"></i>
                            <span><?php echo Text::_('K2_TAGS'); ?></span>
                        </a>
                    </div>
                </div>
			<?php endif; ?>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=comments'); ?>">
                        <i class="dashicon comments"></i>
                        <span><?php echo Text::_('K2_COMMENTS'); ?></span>
                    </a>
                </div>
            </div>
			<?php if ($user->gid>23): ?>
                <div class="icon-wrapper">
                    <div class="icon">
                        <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=extrafields'); ?>">
                            <i class="dashicon extra-fields"></i>
                            <span><?php echo Text::_('K2_EXTRA_FIELDS'); ?></span>
                        </a>
                    </div>
                </div>
                <div class="icon-wrapper">
                    <div class="icon">
                        <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=extrafieldsgroups'); ?>">
                            <i class="dashicon extra-field-groups"></i>
                            <span><?php echo Text::_('K2_EXTRA_FIELD_GROUPS'); ?></span>
                        </a>
                    </div>
                </div>
			<?php endif; ?>
            <div class="icon-wrapper">
                <div class="icon">
                    <a href="<?php echo Route::_('index.php?option=com_k2&amp;view=media'); ?>">
                        <i class="dashicon mediamanager"></i>
                        <span><?php echo Text::_('K2_MEDIA_MANAGER'); ?></span>
                    </a>
                </div>
            </div>
            <div class="icon-wrapper">
                <div class="icon">
                    <a  target="_blank" href="https://getk2.org/documentation/">
                        <i class="dashicon documentation"></i>
                        <span><?php echo Text::_('K2_DOCS_AND_TUTORIALS'); ?></span>
                    </a>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

</div>

