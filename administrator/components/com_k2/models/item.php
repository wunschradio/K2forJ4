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

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\String\StringHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
require_once JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2filesystem.php';
use K2File as File;
use K2Path as Path;
use Joomla\Registry\Registry;
use K2Folder as Folder;
use Joomla\CMS\Utility\Utility;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

jimport('joomla.application.component.model');

Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_k2/tables');

class K2ModelItem extends K2Model
{
    public function getData()
    {
        $cid = Factory::getApplication()->input->get('cid');
        $row = Table::getInstance('K2Item', 'Table');
        $row->load($cid);
        return $row;
    }

    public function save($front = false)
    {
        $app = Factory::getApplication();
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.archive');
        $db = Factory::getDbo();
        $user = Factory::getUser();
        $row = Table::getInstance('K2Item', 'Table');
        $params = ComponentHelper::getParams('com_k2');
        $nullDate = $db->getNullDate();

        // Plugin Events
        PluginHelper::importPlugin('k2');
        PluginHelper::importPlugin('content');
        PluginHelper::importPlugin('finder');
        /* since J4 compatibility */
        /* JDispatcher removed in J4 */
        /*
                $dispatcher = JDispatcher::getInstance();
        */

        if (!$row->bind(Factory::getApplication()->input->getArray($_POST))) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=items');
        }

        if ($front && $row->id == null) {
            JLoader::register('K2HelperPermissions', JPATH_SITE . '/components/com_k2/helpers/permissions.php');
            if (!K2HelperPermissions::canAddItem($row->catid)) {
                $app->enqueueMessage(Text::_('K2_YOU_ARE_NOT_ALLOWED_TO_POST_TO_THIS_CATEGORY_SAVE_FAILED'), 'error');
                $app->redirect('index.php?option=com_k2&view=item&task=add&tmpl=component');
            }
        }

        $isNew = ($row->id) ? false : true;

        // If the item is not new, retrieve its saved data
        $savedRow = new stdClass();
        if (!$isNew) {
            $id = Factory::getApplication()->input->getInt('id');
            $savedRow = Table::getInstance('K2Item', 'Table');
            $savedRow->load($id);
            // Frontend only
            if ($front) {
                $published = $savedRow->published;
                $featured = $savedRow->featured;
            }
        }

        if ($params->get('mergeEditors')) {
            $text = Factory::getApplication()->input->post->getRaw('text', '');
            if ($params->get('xssFiltering')) {
                $filter = new InputFilter(array(), array(), 1, 1, 0);
                $text = $filter->clean($text);
            }
            $pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
            $tagPos = preg_match($pattern, $text);
            if ($tagPos == 0) {
                $row->introtext = $text;
                $row->fulltext = '';
            } else {
                list($row->introtext, $row->fulltext) = preg_split($pattern, $text, 2);
            }
        } else {
            $row->introtext = Factory::getApplication()->input->post->getRaw('introtext', '');
            $row->fulltext = Factory::getApplication()->input->post->getRaw('fulltext', '');
            if ($params->get('xssFiltering')) {
                $filter = new InputFilter(array(), array(), 1, 1, 0);
                $row->introtext = $filter->clean($row->introtext);
                $row->fulltext = $filter->clean($row->fulltext);
            }
        }

        if ($row->id) {
            $datenow = Factory::getDate();
            $row->modified = $datenow->toSql();
            $row->modified_by = $user->get('id');
        } else {
            $row->ordering = $row->getNextOrder("catid = " . (int)$row->catid . " AND trash = 0");
            if ($row->featured) {
                $row->featured_ordering = $row->getNextOrder("featured = 1 AND trash = 0", 'featured_ordering');
            }
        }

        // Author
        $row->created_by = ($row->created_by) ? $row->created_by : $user->get('id');
        if ($front) {
            $K2Permissions = K2Permissions::getInstance();
            if (!$K2Permissions->permissions->get('editAll')) {
                $row->created_by = $user->get('id');
            }
        }

        if ($row->created && strlen(trim($row->created)) <= 10) {
            $row->created .= ' 00:00:00';
        }

        $config = Factory::getConfig();
        $tzoffset = $config->get('offset');
        $date = Factory::getDate($row->created, $tzoffset);
        $row->created = $date->toSql();

        if (strlen(trim($row->publish_up)) <= 10) {
            $row->publish_up .= ' 00:00:00';
        }

        $date = Factory::getDate($row->publish_up, $tzoffset);
        $row->publish_up = $date->toSql();

        if (trim($row->publish_down) == Text::_('K2_NEVER') || trim($row->publish_down) == '') {
            $row->publish_down = $nullDate;
        } else {
            if (strlen(trim($row->publish_down)) <= 10) {
                $row->publish_down .= ' 00:00:00';
            }
            $date = Factory::getDate($row->publish_down, $tzoffset);
            $row->publish_down = $date->toSql();
        }

        $metadata = Factory::getApplication()->input->get('meta', null, 'post');
        if (is_array($metadata)) {
            $txt = array();
            foreach ($metadata as $k => $v) {
                if ($k == 'description') {
                    $row->metadesc = $v;
                } elseif ($k == 'keywords') {
                    $row->metakey = $v;
                } else {
                    $txt[] = "$k=$v";
                }
            }
            $row->metadata = implode("\n", $txt);
        }

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=item&cid=' . $row->id);
        }

        // Trigger K2 plugins
        /* since J4 compatibility */
        $result = Factory::getApplication()->triggerEvent('onBeforeK2Save', array(&$row, $isNew));

        if (in_array(false, $result, true)) {
            Factory::getApplication()->enqueueMessage($row->getError(), 'ERROR');
            return false;
        }

        // Trigger content & finder plugins before the save event
        /* since J4 compatibility */
        Factory::getApplication()->triggerEvent('onContentBeforeSave', array('com_k2.category', $row, $isNew, ''));
        /* since J4 compatibility */
        Factory::getApplication()->triggerEvent('onFinderBeforeSave', array('com_k2.item', $row, $isNew));


        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=items');
        }

        if (!$params->get('disableCompactOrdering')) {
            $row->reorder("catid = " . (int)$row->catid . " AND trash = 0");
        }
        if ($row->featured && !$params->get('disableCompactOrdering')) {
            $row->reorder("featured = 1 AND trash = 0", 'featured_ordering');
        }

        // Tags
        if ($params->get('taggingSystem') === '0' || $params->get('taggingSystem') === '1') {
            // B/C - Convert old options
            $whichTaggingSystem = ($params->get('taggingSystem')) ? 'free' : 'selection';
            $params->set('taggingSystem', $whichTaggingSystem);
        }
        if ($user->gid < 24 && $params->get('lockTags')) {
            $params->set('taggingSystem', 'selection');
        }
        $db->setQuery("DELETE FROM #__k2_tags_xref WHERE itemID=" . (int)$row->id);
        $db->execute();

        if ($params->get('taggingSystem') == 'free') {
            if ($user->gid < 24 && $params->get('lockTags')) {
                throw new \Exception(Text::_('K2_ALERTNOTAUTH'), 403);
            }

            $tags = Factory::getApplication()->input->get('tags', null, 'POST', 'array');
            if (is_array($tags) && count($tags)) {
                $tags = array_unique($tags);
                foreach ($tags as $tag) {
                    $tag = StringHelper::trim($tag);
                    if ($tag) {
                        $tagID = false;
                        $K2Tag = Table::getInstance('K2Tag', 'Table');
                        $K2Tag->name = $tag;
                        // Tag has been filtered and does not exist
                        if ($K2Tag->check()) {
                            $K2Tag->published = 1;
                            if ($K2Tag->store()) {
                                $tagID = $K2Tag->id;
                            }
                        } // Tag has been filtered and it exists so try to find its ID
                        elseif ($K2Tag->name) {
                            $db->setQuery("SELECT id FROM #__k2_tags WHERE name=" . $db->Quote($K2Tag->name));
                            $tagID = $db->loadResult();
                        }
                        if ($tagID) {
                            $db->setQuery("INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, " . (int)$tagID . ", " . (int)$row->id . ")");
                            $db->execute();
                        }
                    }
                }
            }
        } else {
            $tags = Factory::getApplication()->input->get('selectedTags', null, 'POST', 'array');
            if (is_array($tags) && count($tags)) {
                foreach ($tags as $tagID) {
                    $db->setQuery("INSERT INTO #__k2_tags_xref (`id`, `tagID`, `itemID`) VALUES (NULL, " . (int)$tagID . ", " . (int)$row->id . ")");
                    $db->execute();
                }
            }
        }

        // File Uploads
        $files = Factory::getApplication()->input->files->getArray($_FILES);

        // Load class.upload.php
        require_once JPATH_SITE . '/media/k2/assets/vendors/verot/class.upload.php/src/class.upload.php';

        // Image
        if ((int)$params->get('imageMemoryLimit')) {
            ini_set('memory_limit', (int)$params->get('imageMemoryLimit') . 'M');
        }

        $existingImage = Factory::getApplication()->input->getPath('existingImage');

        if (($files['image']['error'] == 0 || $existingImage) && !Factory::getApplication()->input->getBool('del_image')) {
            if ($files['image']['error'] == 0) {
                $image = $files['image'];
            } else {
                $image = JPATH_SITE . '/' . Path::clean($existingImage);
            }

            try {
                $handle            = new \Verot\Upload\Upload($image);
                $handle->allowed   = ['image/*'];
                $handle->forbidden = ['image/bmp', 'image/tiff'];

                if ($handle->uploaded) {
                    // Image params
                    $category = Table::getInstance('K2Category', 'Table');
                    $category->load($row->catid);
                    $cparams = new Registry($category->params);
                    if ($cparams->get('inheritFrom')) {
                        $masterCategoryID = $cparams->get('inheritFrom');
                        $db->setQuery("SELECT * FROM #__k2_categories WHERE id=" . (int)$masterCategoryID, 0, 1);
                        $masterCategory = $db->loadObject();
                        $cparams = new Registry($masterCategory->params);
                    }
                    $params->merge($cparams);

                    // --- Original image
                    $savepath = JPATH_SITE . '/media/k2/items/src';

                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = md5("Image" . $row->id);
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->jpeg_quality       = 100;

                    $handle->process($savepath);

                    // --- Resized images
                    $savepath = JPATH_SITE . '/media/k2/items/cache';
                    $filename = $handle->file_dst_name_body;

                    // XLarge image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_XL';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    if (Factory::getApplication()->input->getInt('itemImageXL')) {
                        $imageWidth = Factory::getApplication()->input->getInt('itemImageXL');
                    } else {
                        $imageWidth = $params->get('itemImageXL', '900');
                    }
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    // Large image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_L';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    if (Factory::getApplication()->input->getInt('itemImageL')) {
                        $imageWidth = Factory::getApplication()->input->getInt('itemImageL');
                    } else {
                        $imageWidth = $params->get('itemImageL', '600');
                    }
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    // Medium image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_M';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    if (Factory::getApplication()->input->getInt('itemImageM')) {
                        $imageWidth = Factory::getApplication()->input->getInt('itemImageM');
                    } else {
                        $imageWidth = $params->get('itemImageM', '400');
                    }
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    // Small image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_S';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    if (Factory::getApplication()->input->getInt('itemImageS')) {
                        $imageWidth = Factory::getApplication()->input->getInt('itemImageS');
                    } else {
                        $imageWidth = $params->get('itemImageS', '200');
                    }
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    // XSmall image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_XS';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    if (Factory::getApplication()->input->getInt('itemImageXS')) {
                        $imageWidth = Factory::getApplication()->input->getInt('itemImageXS');
                    } else {
                        $imageWidth = $params->get('itemImageXS', '100');
                    }
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    // Generic image
                    $handle->file_auto_rename   = false;
                    $handle->file_new_name_body = $filename . '_Generic';
                    $handle->file_overwrite     = true;
                    $handle->image_convert      = 'jpg';
                    $handle->image_ratio_y      = true;
                    $handle->image_resize       = true;
                    $handle->jpeg_quality       = $params->get('imagesQuality', '90');

                    $imageWidth      = $params->get('itemImageGeneric', '300');
                    $handle->image_x = $imageWidth;

                    $handle->process($savepath);

                    if ($handle->processed) {
                        if ($files['image']['error'] == 0) {
                            $handle->clean();
                        }
                    } else {
                        throw new \RuntimeException($handle->error);
                    }
                }
            } catch (\Exception $e) {
                $app->enqueueMessage(Text::_('K2_COULD_NOT_UPLOAD_YOUR_IMAGE') . $e->getMessage(), 'error');
            }
        }

        if (Factory::getApplication()->input->getBool('del_image')) {
            $filename = md5("Image" . $savedRow->id);

            if (File::exists(JPATH_ROOT . '/media/k2/items/src/' . $filename . '.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/src/' . $filename . '.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_XS.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_XS.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_S.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_S.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_M.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_M.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_L.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_L.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_XL.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_XL.jpg');
            }

            if (File::exists(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_Generic.jpg')) {
                File::delete(JPATH_ROOT . '/media/k2/items/cache/' . $filename . '_Generic.jpg');
            }

            $row->image_caption = '';
            $row->image_credits = '';
        }

        // There is stored gllery
        if (!empty($savedRow->gallery)) {
            $row->gallery = $savedRow->gallery;
        }

        // Bildergalerie aus dem Entwurfsordner übernehmen (com_k2_galleries: Bilder wurden vor dem ersten Speichern hochgeladen)
        $galleryDraft = Factory::getApplication()->input->getCmd('k2gallery_draft');

        if ($galleryDraft && preg_match('/^[a-f0-9]{16}$/', $galleryDraft) && $row->id) {
            $draftDir  = JPATH_ROOT . '/media/k2/galleries/_tmp/' . $galleryDraft;
            $targetDir = JPATH_ROOT . '/media/k2/galleries/' . (int) $row->id;

            if (is_dir($draftDir)) {
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach (array_diff(scandir($draftDir), ['.', '..']) as $draftFile) {
                    if (is_file($draftDir . '/' . $draftFile) && !file_exists($targetDir . '/' . $draftFile)) {
                        rename($draftDir . '/' . $draftFile, $targetDir . '/' . $draftFile);
                    }
                }

                array_map('unlink', array_filter(glob($draftDir . '/*') ?: [], 'is_file'));
                rmdir($draftDir);

                if (glob($targetDir . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE)) {
                    $row->gallery = '{gallery}' . (int) $row->id . '{/gallery}';
                }
            }
        }

        $flickrGallery = Factory::getApplication()->input->getString('flickrGallery');
        if ($flickrGallery) {
            $row->gallery = '{gallery}' . $flickrGallery . '{/gallery}';
        }

        if (isset($files['gallery']) && $files['gallery']['error'] == 0 && !Factory::getApplication()->input->getBool('del_gallery')) {
            try {
                $savepath = JPATH_ROOT . '/media/k2/galleries';

                $handle = new \Verot\Upload\Upload([
                    'name'     => $_FILES['gallery']['name'],
                    'type'     => $_FILES['gallery']['type'],
                    'tmp_name' => $_FILES['gallery']['tmp_name'],
                    'error'    => $_FILES['gallery']['error'],
                    'size'     => $_FILES['gallery']['size']
                ]);
                $handle->allowed = [
                    "application/gnutar",
                    "application/gzip",
                    "application/x-bzip",
                    "application/x-bzip2",
                    "application/x-compressed",
                    "application/x-gtar",
                    "application/x-gzip",
                    "application/x-tar",
                    "application/x-zip-compressed",
                    "application/zip",
                    "multipart/x-gzip",
                    "multipart/x-zip",
                ];
                $handle->file_auto_rename = true;

                if ($handle->uploaded) {
                    $handle->process($savepath);
                    if (!$handle->processed) {
                        throw new \RuntimeException($handle->error);
                    }

                    if (Folder::exists($savepath . '/' . $row->id)) {
                        Folder::delete($savepath . '/' . $row->id);
                    }

                    if (!Folder::create($savepath . '/' . $row->id)) {
                        throw new \RuntimeException(Text::_('K2_GALLERY_UPLOAD_ERROR_CANNOT_EXTRACT_ARCHIVE'));
                    }

                    $zipPath = $savepath . '/' . $handle->file_dst_name;
                    $zip = new ZipArchive();
                    if ($zip->open($zipPath) === true) {
                        $zip->extractTo($savepath . '/' . $row->id);
                        $zip->close();

                        // Dateien umbenennen (Leerzeichen -> _ und File::makeSafe), alles außer Bildern/Captions löschen
                        $imageDir = $savepath . '/' . $row->id;
                        $allowedExts = ['avif', 'gif', 'jpg', 'jpeg', 'png', 'webp', 'txt'];
                        $galleryDir = opendir($imageDir);
                        while (($filename = readdir($galleryDir)) !== false) {
                            if ($filename !== "." && $filename !== "..") {
                                if (is_dir($imageDir . '/' . $filename)) {
                                    // Unterordner aus dem Archiv werden von der Galerie nicht genutzt
                                    Folder::delete($imageDir . '/' . $filename);
                                    continue;
                                }
                                $ext = strtolower(File::getExt($filename));
                                if (in_array($ext, $allowedExts)) {
                                    $file = str_replace(" ", "_", $filename);
                                    $safefilename = File::makeSafe($file);
                                    rename($imageDir . '/' . $filename, $imageDir . '/' . $safefilename);
                                } else {
                                    // Delete any non-gallery file extracted from the archive (prevents RCE via shell.php etc.)
                                    File::delete($imageDir . '/' . $filename);
                                }
                            }
                        }
                        closedir($galleryDir);

                        $row->gallery = '{gallery}' . $row->id . '{/gallery}';
                    } else {
                        if (File::exists($zipPath)) {
                            File::delete($zipPath);
                        }
                        throw new \RuntimeException(Text::_('K2_GALLERY_UPLOAD_ERROR_CANNOT_EXTRACT_ARCHIVE'));
                    }

                    if (File::exists($zipPath)) {
                        File::delete($zipPath);
                    }

                    $handle->clean();
                } else {
                    throw new \RuntimeException($handle->error);
                }
            } catch (\Exception $e) {
                $app->enqueueMessage($e->getMessage(), 'error');
                $app->redirect('index.php?option=com_k2&view=items');
                return;
            }
        }

        if (Factory::getApplication()->input->getBool('del_gallery')) {
            if (Folder::exists(JPATH_ROOT . '/media/k2/galleries/' . $savedRow->id)) {
                Folder::delete(JPATH_ROOT . '/media/k2/galleries/' . $savedRow->id);
            }
            $row->gallery = '';
        }

        // === Media ===

        // Allowed filetypes for uploading
        $videoExtensions = array(
            "avi",
            "m4v",
            "mkv",
            "mp4",
            "ogv",
            "webm"
        );
        $audioExtensions = array(
            "flac",
            "m4a",
            "mp3",
            "oga",
            "ogg",
            "wav"
        );
        $validExtensions = array_merge($videoExtensions, $audioExtensions);

        // No stored media & form fields empty for media
        if (empty($savedRow->video) && !Factory::getApplication()->input->getString('embedVideo') && !Factory::getApplication()->input->getString('videoID') && !Factory::getApplication()->input->getString('remoteVideo') && !Factory::getApplication()->input->get('uploadedVideo')) {
            $row->video = '';
        }

        // There is stored media
        if (!empty($savedRow->video)) {
            $row->video = $savedRow->video;
        }

        // Embed
        // Ein geleertes Embed-Feld entfernt den gespeicherten Embed-Code (upstream #577);
        // hochgeladene, Remote- und Provider-Medien ({...}-Tags) bleiben dabei erhalten
        $embedVideoValue = Factory::getApplication()->input->post->get('embedVideo', null, 'raw');
        if ($embedVideoValue !== null) {
            $savedIsEmbed = !empty($savedRow->video) && StringHelper::substr($savedRow->video, 0, 1) !== '{';
            if ($embedVideoValue !== '' || $savedIsEmbed) {
                $row->video = $embedVideoValue;
            }
        }

        // Third-party Media Service
        if (Factory::getApplication()->input->getString('videoID')) {
            $provider = Factory::getApplication()->input->getWord('videoProvider');
            $videoID = Factory::getApplication()->input->getString('videoID');
            $row->video = '{' . $provider . '}' . $videoID . '{/' . $provider . '}';
        }

        // Browse server or remote media
        if (Factory::getApplication()->input->getString('remoteVideo')) {
            $fileurl = Factory::getApplication()->input->getString('remoteVideo');
            $filetype = File::getExt($fileurl);
            $allVideosTagSuffix = 'remote';
            $row->video = '{' . $filetype . $allVideosTagSuffix . '}' . $fileurl . '{/' . $filetype . $allVideosTagSuffix . '}';
        }

        // Upload media
        if (isset($files['video']) && $files['video']['error'] == 0 && !Factory::getApplication()->input->getBool('del_video')) {
            $filename_suffix = ($row->modified) ? $row->modified : $row->created;
            $filename = File::stripExt($files['video']['name']);
            if (class_exists('Transliterator')) {
                $tlSetup = Transliterator::create('Any-Latin; Latin-ASCII');
                $filename = $tlSetup->transliterate($filename);
                $filename = strtolower($filename);
            }
            $filename = preg_replace('/[^\p{L}\p{N}_]/u', '_', trim($filename));
            $filename = $filename . '_' . HTMLHelper::_('date', $filename_suffix, 'Ymd_Hi');
            $filename = preg_replace('/_+/', '_', $filename);

            $filetype = File::getExt($files['video']['name']);

            if (!in_array($filetype, $validExtensions)) {
                $app->enqueueMessage(Text::_('K2_INVALID_VIDEO_FILE'), 'error');
                $app->redirect('index.php?option=com_k2&view=items');
            }
            if (in_array($filetype, $videoExtensions)) {
                $savepath = JPATH_ROOT . '/media/k2/videos';
            } else {
                $savepath = JPATH_ROOT . '/media/k2/audio';
            }

            File::upload($files['video']['tmp_name'], $savepath . '/' . $filename . '.' . $filetype);

            $row->video = '{' . $filetype . '}' . $filename . '{/' . $filetype . '}';
        }

        // Delete media
        if (Factory::getApplication()->input->getBool('del_video')) {
            preg_match_all("#^{(.*?)}(.*?){#", $savedRow->video, $matches, PREG_PATTERN_ORDER);

            $mediaType = $matches[1][0];
            $mediaFile = $matches[2][0];

            if (in_array($mediaType, $videoExtensions)) {
                if (File::exists(JPATH_ROOT . '/media/k2/videos/' . $mediaFile . '.' . $mediaType)) {
                    File::delete(JPATH_ROOT . '/media/k2/videos/' . $mediaFile . '.' . $mediaType);
                }
            }

            if (in_array($mediaType, $audioExtensions)) {
                if (File::exists(JPATH_ROOT . '/media/k2/audio/' . $mediaFile . '.' . $mediaType)) {
                    File::delete(JPATH_ROOT . '/media/k2/audio/' . $mediaFile . '.' . $mediaType);
                }
            }

            $row->video = '';
        }

        // Media Caption & Credits
        if (!$row->video) {
            $row->video_caption = '';
            $row->video_credits = '';
        }

        // === Extra fields ===
        if ($params->get('showExtraFieldsTab') || $app->isClient('administrator')) {
			// todo: find a better way to get extrafield raw value
	        $objects = [];
	        $variables = Factory::getApplication()->input->getArray($_POST);
	        foreach ($variables as $key => $value) {
		        if (StringHelper::stristr($key, 'K2ExtraField_')) {
			        $object = new stdClass();
			        $object->id = substr($key, 13);
			        $raw_value = Factory::getApplication()->input->post->getRaw($key, '');
			        $value = is_string($raw_value) ? trim($raw_value) : $raw_value;
			        $object->value = $value;
			        $objects[] = $object;
		        }
	        }

            $csvFiles = Factory::getApplication()->input->files->getArray($_FILES);
            foreach ($csvFiles as $key => $file) {
                if ((bool)StringHelper::stristr($key, 'K2ExtraField_')) {
                    $object = new stdClass();
                    $object->id = substr($key, 13);
                    $csvFile = $file['tmp_name'][0];
                    if (!empty($csvFile) && File::getExt($file['name'][0]) == 'csv') {
                        $handle = @fopen($csvFile, 'r');
                        $csvData = array();
                        while (($data = fgetcsv($handle, 1000)) !== false) {
                            $csvData[] = $data;
                        }
                        fclose($handle);
                        $object->value = $csvData;
                    } else {
                        $object->value = json_decode(Factory::getApplication()->input->get('K2CSV_' . $object->id));
                        if (Factory::getApplication()->input->getBool('K2ResetCSV_' . $object->id)) {
                            $object->value = null;
                        }
                    }
                    $objects[] = $object;
                }
            }

            $row->extra_fields = json_encode($objects);

            require_once(JPATH_COMPONENT_ADMINISTRATOR . '/models/extrafield.php');
            $extraFieldModel = K2Model::getInstance('ExtraField', 'K2Model');
            $row->extra_fields_search = '';
            foreach ($objects as $object) {
                $row->extra_fields_search .= $extraFieldModel->getSearchValue($object->id, $object->value);
                $row->extra_fields_search .= ' ';
            }
        }

        // Attachments
        $path = $params->get('attachmentsFolder', null);
        if (is_null($path)) {
            $savepath = JPATH_ROOT . '/media/k2/attachments';
        } else {
            $savepath = $path;
        }

        // Server-executable and sensitive file types are never accepted as attachments
        $blockedExts = [
            'asp',
            'aspx',
            'bash',
            'bat',
            'cgi',
            'htaccess',
            'htm',
            'html',
            'htpasswd',
            'inc',
            'ini',
            'js',
            'jsp',
            'jspx',
            'phar',
            'php',
            'php3',
            'php4',
            'php5',
            'php6',
            'php7',
            'php8',
            'phps',
            'phpt',
            'pht',
            'phtm',
            'phtml',
            'pl',
            'py',
            'rb',
            'sh',
            'sql',
            'wsdl',
        ];

        $attPost = Factory::getApplication()->input->post->get('attachment', [], 'array');
        $attFiles = Factory::getApplication()->input->files->get('attachment', [], 'array');

        if (is_array($attPost) && count($attPost)) {
            foreach ($attPost as $key => $attachment) { /* Use the POST array's key as reference */
                if (!empty($attachment['existing'])) {
                    $src = JPATH_SITE . '/' . Path::clean($attachment['existing']);
                    $realSrc = realpath($src);
                    $realSiteRoot = realpath(JPATH_SITE);
                    // Block path traversal outside the site root
                    if ($realSrc === false || $realSiteRoot === false || !is_file($realSrc) || strpos($realSrc, $realSiteRoot . DIRECTORY_SEPARATOR) !== 0) {
                        $app->enqueueMessage(Text::_('K2_INVALID_ATTACHMENT_PATH'), 'error');
                        continue;
                    }
                    // Block server-executable file types
                    $existingExt = strtolower(File::getExt($realSrc));
                    if (in_array($existingExt, $blockedExts)) {
                        $app->enqueueMessage(Text::_('K2_INVALID_FILE_TYPE'), 'error');
                        continue;
                    }
                    $filename = basename($realSrc);
                    $dest = $savepath . '/' . $filename;

                    if (File::exists($dest)) {
                        $existingFileName = File::getName($dest);
                        $ext = File::getExt($existingFileName);
                        $basename = File::stripExt($existingFileName);
                        $newFilename = $basename . '_' . time() . '.' . $ext;
                        $filename = $newFilename;
                        $dest = $savepath . '/' . $newFilename;
                    }

                    File::copy($realSrc, $dest);

                    $attachmentToSave = Table::getInstance('K2Attachment', 'Table');
                    $attachmentToSave->itemID = $row->id;
                    $attachmentToSave->filename = $filename;
                    $attachmentToSave->title = (empty($attachment['title'])) ? $filename : $attachment['title'];
                    $attachmentToSave->titleAttribute = (empty($attachment['title_attribute'])) ? $filename : $attachment['title_attribute'];
                    $attachmentToSave->store();
                } else {
                    try {
                        $handle = new \Verot\Upload\Upload($attFiles[$key]['upload']['tmp_name']);
                        $filename = $attFiles[$key]['upload']['name'];
                        if ($handle->uploaded) {
                            $uploadExt = strtolower(File::getExt($filename));
                            if (in_array($uploadExt, $blockedExts)) {
                                throw new \RuntimeException(Text::_('K2_INVALID_FILE_TYPE'));
                            }
                            $handle->file_auto_rename = true;
                            $handle->file_new_name_body = File::stripExt($filename);
                            $handle->file_new_name_ext = $uploadExt;
                            $handle->file_safe_name = true;
                            $handle->forbidden = [
                                "application/java-archive",
                                "application/x-httpd-php",
                                "application/x-sh",
                            ];

                            $handle->process($savepath);

                            if ($handle->processed) {
                                $dstName = $handle->file_dst_name;

                                $attachmentToSave = Table::getInstance('K2Attachment', 'Table');
                                $attachmentToSave->itemID = $row->id;
                                $attachmentToSave->filename = $dstName;
                                $attachmentToSave->title = (empty($attachment['title'])) ? $filename : $attachment['title'];
                                $attachmentToSave->titleAttribute = (empty($attachment['title_attribute'])) ? $filename : $attachment['title_attribute'];
                                $attachmentToSave->store();

                                $handle->clean();
                            } else {
                                throw new \RuntimeException($handle->error);
                            }
                        } else {
                            throw new \RuntimeException($handle->error);
                        }
                    } catch (\Exception $e) {
                        $app->enqueueMessage($e->getMessage(), 'error');
                        $app->redirect('index.php?option=com_k2&view=items');
                    }
                }
            }
        }

        // Check publishing permissions in frontend editing
        if ($front) {
            $newPublishedState = $row->published;
            $row->published = 0;

            // "Allow editing of already published items" permission check
            if (!$isNew && K2HelperPermissions::canEditPublished($row->catid)) {
                $row->published = $published;
            }

            // "Publish items" permission check
            if (K2HelperPermissions::canPublishItem($row->catid)) {
                $row->published = $newPublishedState;
            }

            if (!K2HelperPermissions::canEditPublished($row->catid) && !K2HelperPermissions::canPublishItem($row->catid) && $newPublishedState) {
                $app->enqueueMessage(Text::_('K2_YOU_DONT_HAVE_THE_PERMISSION_TO_PUBLISH_ITEMS'), 'notice');
            }
        }

        $query = "UPDATE #__k2_items SET
            image_caption = " . $db->Quote($row->image_caption) . ",
            image_credits = " . $db->Quote($row->image_credits) . ",
            video_caption = " . $db->Quote($row->video_caption) . ",
            video_credits = " . $db->Quote($row->video_credits) . ",
            video = " . $db->Quote($row->video) . ",
            gallery = " . $db->Quote($row->gallery);
        if ($params->get('showExtraFieldsTab') || $app->isClient('administrator')) {
            $query .= ", extra_fields = " . $db->Quote($row->extra_fields) . ", extra_fields_search = " . $db->Quote($row->extra_fields_search);
        }
        $query .= ", published = " . $db->Quote($row->published) . " WHERE id = " . $row->id;

        $db->setQuery($query);
        /* since J4 compatibility */
        try {
            $db->execute();
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), 'ERROR');
            $app->redirect('index.php?option=com_k2&view=items');
        }

        $row->checkin();

        parent::cleanCache('com_k2');
        parent::cleanCache('com_k2_extended');

        // Trigger K2 plugins
        /* since J4 compatibility */
        Factory::getApplication()->triggerEvent('onAfterK2Save', array(&$row, $isNew));

        // Trigger content & finder plugins after the save event
        /* since J4 compatibility */
        Factory::getApplication()->triggerEvent('onContentAfterSave', array('com_k2.item', &$row, $isNew));
        /* since J4 compatiblity */
        $results = Factory::getApplication()->triggerEvent('onFinderAfterSave', array('com_k2.item', $row, $isNew));

        switch (Factory::getApplication()->input->getCmd('task')) {
            case 'apply':
                $msg = Text::_('K2_CHANGES_TO_ITEM_SAVED');
                $link = 'index.php?option=com_k2&view=item&cid=' . $row->id;
                break;
            case 'saveAndNew':
                $msg = Text::_('K2_ITEM_SAVED');
                $link = 'index.php?option=com_k2&view=item';
                break;
            case 'save':
            default:
                $msg = Text::_('K2_ITEM_SAVED');
                if ($front) {
                    $link = 'index.php?option=com_k2&view=item&task=edit&cid=' . $row->id . '&tmpl=component&Itemid=' . Factory::getApplication()->input->getInt('Itemid');
                } else {
                    $link = 'index.php?option=com_k2&view=items';
                }
                break;
        }
        $app->enqueueMessage($msg);
        $app->redirect($link);
    }

    public function cancel()
    {
        $app = Factory::getApplication();
        $cid = Factory::getApplication()->input->getInt('id');
        if ($cid) {
            $row = Table::getInstance('K2Item', 'Table');
            $row->load($cid);
            $row->checkin();
        } else {
            // Cleanup SIGPro
            $sigProFolder = Factory::getApplication()->input->getCmd('sigProFolder');
            if ($sigProFolder && !is_numeric($sigProFolder) && Folder::exists(JPATH_SITE . '/media/k2/galleries/' . $sigProFolder)) {
                Folder::delete(JPATH_SITE . '/media/k2/galleries/' . $sigProFolder);
            }
        }
        $app->redirect('index.php?option=com_k2&view=items');
    }

    public function getVideoProviders()
    {
        jimport('joomla.filesystem.file');

        $file = JPATH_PLUGINS . '/content/jw_allvideos/jw_allvideos/includes/sources.php';

        $providers = array();

        if (File::exists($file)) {
            require $file;
            if (!empty($tagReplace) && is_array($tagReplace)) {
                foreach ($tagReplace as $name => $embed) {
                    if (strpos($embed, '<iframe') !== false || strpos($embed, '<script') !== false) {
                        $providers[] = $name;
                    }
                }
            }
        }

        return $providers;
    }

    public function download()
    {
        $app = Factory::getApplication();
        $user = Factory::getUser();
        jimport('joomla.filesystem.file');
        $params = ComponentHelper::getParams('com_k2');
        $id = Factory::getApplication()->input->getInt('id');

        // Plugin Events
        PluginHelper::importPlugin('k2');
        /* since J4 compatibility */
        /* JDispatcher removed in J4 */
        /*
                $dispatcher = JDispatcher::getInstance();
        */

        $attachment = Table::getInstance('K2Attachment', 'Table');
        if ($app->isClient('site')) {
            $token = Factory::getApplication()->input->get('id');
            $check = StringHelper::substr($token, StringHelper::strpos($token, '_') + 1);
            $hash = ApplicationHelper::getHash($id);
            if ($check != $hash) {
                throw new \Exception(Text::_('K2_NOT_FOUND'), 404);
            }
        }
        $attachment->load($id);

        // Frontend Editing: Ensure the user has access to the item
        if ($app->isClient('site')) {
            $item = Table::getInstance('K2Item', 'Table');
            $item->load($attachment->itemID);
            $category = Table::getInstance('K2Category', 'Table');
            $category->load($item->catid);
            if (!$item->id || !$category->id) {
                throw new \Exception(Text::_('K2_NOT_FOUND'), 404);
            }

            if ((!in_array($category->access, $user->getAuthorisedViewLevels()) || !in_array($item->access, $user->getAuthorisedViewLevels()))) {
                throw new \Exception(Text::_('K2_ALERTNOTAUTH'), 403);
            }
        }

        // Trigger K2 plugins
        /* since J4 compatibility */
        Factory::getApplication()->triggerEvent('onK2BeforeDownload', array(&$attachment, &$params));

        $path = $params->get('attachmentsFolder', null);
        if (is_null($path)) {
            $savepath = JPATH_ROOT . '/media/k2/attachments';
        } else {
            $savepath = $path;
        }
        $file = $savepath . '/' . $attachment->filename;

        if (File::exists($file)) {
            // Trigger K2 plugins
            /* since J4 compatibility */
            Factory::getApplication()->triggerEvent('onK2AfterDownload', array(&$attachment, &$params));

            if ($app->isClient('site')) {
                $attachment->hit();
            }
            $len = filesize($file);
            $filename = basename($file);
            ob_end_clean();
            Factory::getApplication()->clearHeaders();
            Factory::getApplication()->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
            Factory::getApplication()->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '";', true);
            Factory::getApplication()->setHeader('Content-Length', $len, true);
            Factory::getApplication()->setHeader('Content-Transfer-Encoding', 'binary', true);
            Factory::getApplication()->setHeader('Content-Type', 'application/octet-stream', true);
            Factory::getApplication()->setHeader('Expires', '0', true);
            Factory::getApplication()->setHeader('Pragma', 'public', true);
            Factory::getApplication()->sendHeaders();
            readfile($file);
        } else {
            echo Text::_('K2_FILE_DOES_NOT_EXIST');
        }
        $app->close();
    }

    public function getAttachments($itemID)
    {
        $db = Factory::getDbo();
        $db->setQuery("SELECT * FROM #__k2_attachments WHERE itemID=" . (int)$itemID);
        $rows = $db->loadObjectList();
        foreach ($rows as $row) {
            $hash = ApplicationHelper::getHash($row->id);
            $row->link = Route::_('index.php?option=com_k2&view=item&task=download&id=' . $row->id . '_' . $hash);
        }
        return $rows;
    }

    public function deleteAttachment()
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_k2');
        jimport('joomla.filesystem.file');
        $id = Factory::getApplication()->input->getInt('id');
        $itemID = Factory::getApplication()->input->getInt('cid');

        // Plugin Events
        PluginHelper::importPlugin('k2');
        /* since J4 compatibility */
        /* JDispatcher removed in J4 */
        /*
                $dispatcher = JDispatcher::getInstance();
        */

        $db = Factory::getDbo();
        $db->setQuery("SELECT COUNT(*) FROM #__k2_attachments WHERE itemID={$itemID} AND id={$id}");
        $result = $db->loadResult();

        if (!$result) {
            $app->close();
        }

        $row = Table::getInstance('K2Attachment', 'Table');
        $row->load($id);

        $path = $params->get('attachmentsFolder', null);
        if (is_null($path)) {
            $savepath = JPATH_ROOT . '/media/k2/attachments';
        } else {
            $savepath = $path;
        }

        if (File::exists($savepath . '/' . $row->filename)) {
            File::delete($savepath . '/' . $row->filename);
        }

        $row->delete($id);

        // Trigger K2 plugins
        /* since J4 compatibility */
        $result = Factory::getApplication()->triggerEvent('onAfterK2DeleteAttachment', array($id, $savepath));

        $app->close();
    }

    public function getAvailableTags($itemID = null)
    {
        $db = Factory::getDbo();
        $query = "SELECT * FROM #__k2_tags as tags";
        if (!is_null($itemID)) {
            $query .= " WHERE tags.id NOT IN (SELECT tagID FROM #__k2_tags_xref WHERE itemID=" . (int)$itemID . ")";
        }
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public function getCurrentTags($itemID)
    {
        $db = Factory::getDbo();
        $itemID = (int)$itemID;
        $db->setQuery("SELECT tags.* FROM #__k2_tags AS tags JOIN #__k2_tags_xref AS xref ON tags.id = xref.tagID WHERE xref.itemID = " . (int)$itemID . " ORDER BY xref.id ASC");
        $rows = $db->loadObjectList();
        return $rows;
    }

    public function resetHits()
    {
        $app = Factory::getApplication();
        $id = Factory::getApplication()->input->getInt('id');
        $db = Factory::getDbo();
        $db->setQuery("UPDATE #__k2_items SET hits=0 WHERE id={$id}");
        $db->execute();
        if ($app->isClient('administrator')) {
            $url = 'index.php?option=com_k2&view=item&cid=' . $id;
        } else {
            $url = 'index.php?option=com_k2&view=item&task=edit&cid=' . $id . '&tmpl=component';
        }
        $app->enqueueMessage(Text::_('K2_SUCCESSFULLY_RESET_ITEM_HITS'));
        $app->redirect($url);
    }

    public function resetRating()
    {
        $app = Factory::getApplication();
        $id = Factory::getApplication()->input->getInt('id');
        $db = Factory::getDbo();
        $db->setQuery("DELETE FROM #__k2_rating WHERE itemID={$id}");
        $db->execute();
        if ($app->isClient('administrator')) {
            $url = 'index.php?option=com_k2&view=item&cid=' . $id;
        } else {
            $url = 'index.php?option=com_k2&view=item&task=edit&cid=' . $id . '&tmpl=component';
        }
        $app->enqueueMessage(Text::_('K2_SUCCESSFULLY_RESET_ITEM_RATING'));
        $app->redirect($url);
    }

    public function getRating()
    {
        $id = Factory::getApplication()->input->getInt('cid');
        $db = Factory::getDbo();
        $db->setQuery("SELECT * FROM #__k2_rating WHERE itemID={$id}", 0, 1);
        $row = $db->loadObject();
        return $row;
    }

    public function checkSIG()
    {
        $app = Factory::getApplication();
        $check = JPATH_PLUGINS . '/content/jw_sigpro/jw_sigpro.php';
        if (File::exists($check)) {
            return true;
        } else {
            return false;
        }
    }

    public function checkAllVideos()
    {
        $app = Factory::getApplication();
        $check = JPATH_PLUGINS . '/content/jw_allvideos/jw_allvideos.php';
        if (File::exists($check)) {
            return true;
        } else {
            return false;
        }
    }

    public function cleanText($text)
    {
        $text = ComponentHelper::filterText($text);

        return $text;
    }
}
