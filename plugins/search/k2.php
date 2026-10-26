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
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\Route;

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgSearchK2 extends CMSPlugin
{
    public function onContentSearchAreas()
    {
        return $this->onSearchAreas();
    }

    public function onContentSearch($text, $phrase = '', $ordering = '', $areas = null)
    {
        return $this->onSearch($text, $phrase, $ordering, $areas);
    }

    public function onSearchAreas()
    {
        CMSPlugin::loadLanguage('plg_search_k2', JPATH_ADMINISTRATOR);
        static $areas = ['k2' => 'K2_ITEMS'];
        return $areas;
    }

    public function onSearch($text, $phrase = '', $ordering = '', $areas = null)
    {
        CMSPlugin::loadLanguage('plg_search_k2', JPATH_ADMINISTRATOR);
        jimport('joomla.html.parameter');
        $app = Factory::getApplication();
        $db = Factory::getDbo();
        $jnow = Factory::getDate();
        $now = $jnow->toSql();

        $nullDate = $db->getNullDate();
        $user = Factory::getUser();
        $accessCheck = " IN(" . implode(',', $user->getAuthorisedViewLevels()) . ") ";
        $tagIDs = [];
        $itemIDs = [];

        require_once JPATH_SITE . '/administrator/components/com_search/helpers/search.php';
        require_once JPATH_SITE . '/components/com_k2/helpers/route.php';
        require_once JPATH_SITE . '/media/k2/assets/helpers/global.php';

        $searchText = $text;
        $where = '';
        $rows = [];
        $results = [];

        if (is_array($areas)) {
            if (!array_intersect($areas, array_keys($this->onSearchAreas()))) {
                return [];
            }
        }

        $plugin = PluginHelper::getPlugin('search', 'k2');
        $pluginParams = class_exists('JParameter') ? new JParameter($plugin->params) : new Registry($plugin->params);

        $limit = $pluginParams->def('search_limit', 50);

        if ($limit > 0) {
            $text = StringHelper::trim($text);
            if ($text == '') {
                return [];
            }

            // K2GlobalHelper detects exact phrases by surrounding double quotes
            $helperText = ($phrase == 'exact') ? '"' . StringHelper::trim($text, '"') . '"' : $text;

            $where .= K2GlobalHelper::search($helperText, [
                'i.title',
                'i.alias',
                'i.introtext',
                'i.`fulltext`',
                'i.extra_fields_search',
                'i.image_caption',
                'i.image_credits',
                'i.video_caption',
                'i.video_credits',
                'i.metadesc',
                'i.metakey',
            ]);

            // Search term too short or without usable words: do not list all items
            if ($where === '' || $where === ' AND ()') {
                return [];
            }

            if ($pluginParams->get('search_tags')) {
                $tagQuery = StringHelper::strtolower($text);
                $escaped = $db->escape($tagQuery, true);
                $quoted = $db->Quote('%' . $escaped . '%', false);
                $query = "SELECT id FROM #__k2_tags WHERE published = 1 AND LOWER(name) LIKE " . $quoted;
                $db->setQuery($query);
                $tagIDs = $db->loadColumn();
                if (is_array($tagIDs) && count($tagIDs)) {
                    sort($tagIDs);
                    $query = "SELECT itemID FROM #__k2_tags_xref WHERE tagID IN (" . implode(',', $tagIDs) . ")";
                    $db->setQuery($query);
                    $itemIDs = $db->loadColumn();
                    $itemIDs = array_unique($itemIDs);
                    if (is_array($itemIDs) && count($itemIDs)) {
                        $itemIDs = ArrayHelper::toInteger($itemIDs);
                        sort($itemIDs);
                        // Trim the last closing parenthesis and add OR condition for items with matching tags to close the AND (...) clause
                        $where = substr($where, 0, strlen($where) - 1) . " OR i.id IN (" . implode(',', $itemIDs) . "))";
                    }
                }
            }

            $query = "SELECT i.title AS title,
                    i.alias,
                    i.metadesc,
                    i.metakey,
                    c.name as section,
                    i.image_caption,
                    i.image_credits,
                    i.video_caption,
                    i.video_credits,
                    i.extra_fields_search,
                    i.created,
                    CONCAT(i.introtext, i.fulltext) AS text,
                    CASE WHEN CHAR_LENGTH(i.alias) THEN CONCAT_WS(':', i.id, i.alias) ELSE i.id END as slug,
                    CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(':', c.id, c.alias) ELSE c.id END as catslug
                FROM #__k2_items AS i
                INNER JOIN #__k2_categories AS c ON c.id = i.catid
                WHERE i.trash = 0
                    AND i.published = 1
                    AND i.access {$accessCheck}
                    AND c.published = 1
                    AND c.access {$accessCheck}
                    AND c.trash = 0
                    AND (i.publish_up = " . $db->Quote($nullDate) . " OR i.publish_up <= " . $db->Quote($now) . ")
                    AND (i.publish_down = " . $db->Quote($nullDate) . " OR i.publish_down >= " . $db->Quote($now) . ")
                    {$where}
            ";

            if ($app->isClient('site') && $app->getLanguageFilter()) {
                $languageTag = Factory::getLanguage()->getTag();
                $query .= " AND c.language IN (" . $db->Quote($languageTag) . ", " . $db->Quote('*') . ") AND i.language IN (" . $db->Quote($languageTag) . ", " . $db->Quote('*') . ")";
            }

            switch ($ordering) {
                case 'oldest':
                    $query .= ' ORDER BY i.created ASC';
                    break;

                case 'popular':
                    $query .= ' ORDER BY i.hits DESC';
                    break;

                case 'alpha':
                    $query .= ' ORDER BY i.title ASC';
                    break;

                case 'category':
                    $query .= ' ORDER BY c.name ASC, i.title ASC';
                    break;

                case 'newest':
                default:
                    $query .= ' ORDER BY i.created DESC';
                    break;
            }

            $db->setQuery($query, 0, $limit);
            $list = $db->loadObjectList();

            if (is_array($list)) {
                $limit -= count($list);
                foreach ($list as $key => $item) {
                    $list[$key]->href = Route::_(K2HelperRoute::getItemRoute($item->slug, $item->catslug));
                }
                $rows[] = $list;
            }
        }

        if (is_array($rows) && count($rows)) {
            foreach ($rows as $row) {
                $new_row = [];
                foreach ($row as $key => $item) {
                    $item->browsernav = '';
                    $item->tag = $searchText;
                    if (searchHelper::checkNoHTML($item, $searchText, ['text', 'title', 'metakey', 'metadesc', 'section', 'image_caption', 'image_credits', 'video_caption', 'video_credits', 'extra_fields_search', 'tag'])) {
                        $new_row[] = $item;
                    }
                }
                $results = array_merge($results, (array)$new_row);
            }
        }

        return $results;
    }
}
