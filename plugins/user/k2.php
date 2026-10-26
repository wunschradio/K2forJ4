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

use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
require_once JPATH_ADMINISTRATOR . '/components/com_k2/lib/k2filesystem.php';
use K2File as File;
use Joomla\CMS\Router\Route;

jimport('joomla.plugin.plugin');

class plgUserK2 extends CMSPlugin
{
    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        return $this->onAfterStoreUser($user, $isnew, $success, $msg);
    }

    public function onUserLogin($user, $options)
    {
        return $this->onLoginUser($user, $options);
    }

    public function onUserLogout($user)
    {
        return $this->onLogoutUser($user);
    }

    public function onUserAfterDelete($user, $success, $msg)
    {
        return $this->onAfterDeleteUser($user, $success, $msg);
    }

    public function onUserBeforeSave($user, $isNew)
    {
        return $this->onBeforeStoreUser($user, $isNew);
    }

    public function onAfterStoreUser($user, $isnew, $success, $msg)
    {
        jimport('joomla.filesystem.file');
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_k2');
        $task = Factory::getApplication()->input->getCmd('task');

        if ($app->isClient('site') && ($task == 'activate' || $isnew) && $params->get('stopForumSpam')) {
            $this->checkSpammer($user);
        }

        if ($app->isClient('site') && $task != 'activate' && Factory::getApplication()->input->getInt('K2UserForm')) {
            CMSPlugin::loadLanguage('com_k2');
            Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_k2/tables');
            $k2id = $this->getK2UserID($user['id']);
            $row = Table::getInstance('K2User', 'Table');
            if ($k2id) {
                $row->load($k2id);
            }
            // Snapshot server-owned columns from the loaded row BEFORE bind(), so a crafted
            // POST cannot overwrite them. 'image' is only ever set by the upload handler /
            // del_image below; 'notes' is a K2 backend-only annotation (e.g. spammer flag).
            // 'plugins' stays bindable, it carries the K2 user plugin fields.
            $origImage = $row->image;
            $origNotes = $row->notes;

            $row->bind(Factory::getApplication()->input->getArray($_POST));
            // Override columns that must never come from POST
            $row->set('id', $k2id);
            $row->set('userID', $user['id']);
            $row->set('userName', $user['name']);
            $row->set('ip', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
            $row->set('hostname', isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : '');
            $row->set('image', $origImage);
            $row->set('notes', isset($user['notes']) ? $user['notes'] : $origNotes);
            if ($isnew) {
                $row->set('group', $params->get('K2UserGroup', 1));
            } else {
                $row->set('group', null);
                $row->set('gender', Factory::getApplication()->input->getVar('gender', 'n'));
                $url = Factory::getApplication()->input->getString('url');
                $url = StringHelper::str_ireplace(' ', '', $url);
                $url = StringHelper::str_ireplace('"', '', $url);
                $url = StringHelper::str_ireplace('<', '', $url);
                $url = StringHelper::str_ireplace('>', '', $url);
                $url = StringHelper::str_ireplace('\'', '', $url);
                $row->set('url', $url);
            }
            $row->set('description', Factory::getApplication()->input->post->getString('description', ''));
            if ($params->get('xssFiltering')) {
                $filter = new InputFilter([], [], 1, 1, 0);
                $row->description = $filter->clean($row->description);
            }
            $row->store();

            $file = Factory::getApplication()->input->files->getArray($_FILES);

            if (isset($file['image']) && $file['image']['error'] == 0 && !Factory::getApplication()->input->getBool('del_image')) {
                require_once JPATH_SITE . '/media/k2/assets/vendors/verot/class.upload.php/src/class.upload.php';
                $savepath = JPATH_ROOT . '/media/k2/users/';

                try {
                    $handle = new \Verot\Upload\Upload($file['image']);
                    $handle->allowed = ['image/*'];
                    $handle->forbidden = ['image/bmp', 'image/tiff'];

                    if ($handle->uploaded) {
                        $handle->file_auto_rename = false;
                        $handle->file_new_name_body = $row->id;
                        $handle->file_overwrite = true;
                        $handle->image_convert = 'webp';
                        $handle->image_ratio_y = true;
                        $handle->image_resize = true;
                        $handle->image_x = $params->get('userImageWidth', '100');
                        $handle->webp_quality = $params->get('imagesQuality', '90');

                        $handle->process($savepath);

                        if ($handle->processed) {
                            $handle->clean();
                            $image = $handle->file_dst_name;
                        } else {
                            throw new \RuntimeException($handle->error);
                        }
                    }
                } catch (\Exception $e) {
                    $app->enqueueMessage(Text::_('K2_COULD_NOT_UPLOAD_YOUR_IMAGE') . $e->getMessage(), 'error');
                }
            }

            if (Factory::getApplication()->input->getBool('del_image')) {
                $currentImage = basename($row->image);
                if (File::exists(JPATH_ROOT . '/media/k2/users/' . $currentImage)) {
                    File::delete(JPATH_ROOT . '/media/k2/users/' . $currentImage);
                }
                $image = '';
            }
            if (isset($image)) {
                $row->image = $image;
                $row->store();
            }

            $itemid = $params->get('redirect');
            if (!$isnew && $itemid) {
                $menu = $app->getMenu();
                $item = $menu->getItem($itemid);
                $url = Route::_($item->link . '&Itemid=' . $itemid, false);

                $app->setUserState('com_users.edit.profile.redirect', $url);
            }
        }
    }

    public function onLoginUser($user, $options)
    {
        $params = ComponentHelper::getParams('com_k2');
        $app = Factory::getApplication();
        if ($app->isClient('site')) {
            // Get the user id
            $db = Factory::getDbo();
            $db->setQuery("SELECT id FROM #__users WHERE username = " . $db->Quote($user['username']));
            $id = $db->loadResult();

            // If K2 profiles are enabled assign non-existing K2 users to the default K2 group. Update user info for existing K2 users.
            if ($params->get('K2UserProfile') && $id) {
                $k2id = $this->getK2UserID($id);
                Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_k2/tables');
                $row = Table::getInstance('K2User', 'Table');
                if ($k2id) {
                    $row->load($k2id);
                } else {
                    $row->set('userID', $id);
                    $row->set('userName', $user['fullname']);
                    $row->set('group', $params->get('K2UserGroup', 1));
                }
                $row->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
                $row->hostname = isset($_SERVER['REMOTE_ADDR']) ? gethostbyaddr($_SERVER['REMOTE_ADDR']) : '';
                $row->store();
            }

            // Set the Cookie domain for user based on K2 parameters
            if ($params->get('cookieDomain') && $id) {
                setcookie("userID", $id, 0, '/', $params->get('cookieDomain'), 0);
            }
        }
        return true;
    }

    public function onLogoutUser($user)
    {
        $params = ComponentHelper::getParams('com_k2');
        $app = Factory::getApplication();
        if ($app->isClient('site') && $params->get('cookieDomain')) {
            setcookie("userID", "", time() - 3600, '/', $params->get('cookieDomain'), 0);
        }
        return true;
    }

    public function onAfterDeleteUser($user, $succes, $msg)
    {
        $app = Factory::getApplication();
        $db = Factory::getDbo();
        $query = "DELETE FROM #__k2_users WHERE userID={$user['id']}";
        $db->setQuery($query);
        $db->execute();
    }

    public function onBeforeStoreUser($user, $isNew)
    {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_k2');
        $session = Factory::getSession();
        if ($params->get('K2UserProfile') && $isNew && $params->get('recaptchaOnRegistration') && $app->isClient('site') && !$session->get('socialConnectData')) {
            require_once JPATH_SITE . '/components/com_k2/helpers/utilities.php';
            if (!K2HelperUtilities::verifyRecaptcha()) {
                $url = 'index.php?option=com_users&view=registration';
                $app->enqueueMessage(Text::_('K2_COULD_NOT_VERIFY_THAT_YOU_ARE_NOT_A_ROBOT'), 'error');
                $app->redirect($url);
            }
        }
    }

    public function getK2UserID($id)
    {
        $db = Factory::getDbo();
        $query = "SELECT id FROM #__k2_users WHERE userID={$id}";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public function checkSpammer(&$user)
    {
        if (!$user['block']) {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
            $email = urlencode($user['email']);
            $username = urlencode($user['username']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://www.stopforumspam.com/api?ip=' . $ip . '&email=' . $email . '&username=' . $username . '&f=json');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode == 200) {
                $response = json_decode($response);
                if ($response->ip->appears || $response->email->appears || $response->username->appears) {
                    $db = Factory::getDbo();
                    $db->setQuery("UPDATE #__users SET block = 1 WHERE id = " . $user['id']);
                    $db->execute();
                    $user['notes'] = Text::_('K2_POSSIBLE_SPAMMER_DETECTED_BY_STOPFORUMSPAM');
                }
            }
        }
    }
}
