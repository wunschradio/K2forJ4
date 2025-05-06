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

class modK2StatsHelper
{
    public static function getLatestItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT *, v.name AS author
            FROM #__k2_items AS i
            LEFT JOIN #__users AS v ON v.id = i.created_by
            WHERE i.trash = 0
            ORDER BY i.id DESC";
        $query = str_ireplace('#__groups', '#__viewlevels', $query);
        $query = str_ireplace('g.name', 'g.title', $query);
        $db->setQuery($query, 0, 10);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getPopularItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT i.*, v.name AS author
            FROM #__k2_items AS i
            LEFT JOIN #__users AS v ON v.id = i.created_by
            WHERE i.trash = 0
            ORDER BY i.hits DESC";
        $db->setQuery($query, 0, 10);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getPopularItems30()
    {
        $db = Factory::getDbo();
        $query = "SELECT i.*, v.name AS author
            FROM #__k2_items AS i
            LEFT JOIN #__users AS v ON v.id = i.created_by
            WHERE i.trash = 0 AND i.created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY i.hits DESC";
        $db->setQuery($query, 0, 10);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getMostCommentedItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT i.*, v.name AS author, (SELECT COUNT(*) FROM #__k2_comments WHERE itemID = i.id) AS numOfComments
            FROM #__k2_items AS i
            LEFT JOIN #__k2_categories AS c ON c.id = i.catid
            LEFT JOIN #__users AS v ON v.id = i.created_by
            WHERE i.trash = 0 AND c.trash = 0
            ORDER BY numOfComments DESC";
        $db->setQuery($query, 0, 10);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getLatestComments()
    {
        $db = Factory::getDbo();
        $query = "SELECT * FROM #__k2_comments ORDER BY commentDate DESC";
        $db->setQuery($query, 0, 10);
        $rows = $db->loadObjectList();
        return $rows;
    }

    public static function getStatistics()
    {
        $statistics = new stdClass;
        $statistics->numOfItems = self::countItems();
        $statistics->numOfTrashedItems = self::countTrashedItems();
        $statistics->numOfFeaturedItems = self::countFeaturedItems();
        $statistics->numOfComments = self::countComments();
        $statistics->numOfCategories = self::countCategories();
        $statistics->numOfTrashedCategories = self::countTrashedCategories();
        $statistics->numOfUsers = self::countUsers();
        $statistics->numOfUserGroups = self::countUserGroups();
        $statistics->numOfTags = self::countTags();
        return $statistics;
    }

    public static function countItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_items";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countTrashedItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_items WHERE trash=1";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countFeaturedItems()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_items WHERE featured=1";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countComments()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_comments";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countCategories()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_categories";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countTrashedCategories()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_categories WHERE trash=1";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countUsers()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_users";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countUserGroups()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_user_groups";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }

    public static function countTags()
    {
        $db = Factory::getDbo();
        $query = "SELECT COUNT(*) FROM #__k2_tags";
        $db->setQuery($query);
        $result = $db->loadResult();
        return $result;
    }
}
