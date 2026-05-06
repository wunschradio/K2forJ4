<?php
defined('_JEXEC') or define('_JEXEC', 1);

if (!defined('JPATH_BASE')) {
    $adminPath = dirname(__FILE__);
    while ($adminPath !== dirname($adminPath)) {
        if (file_exists($adminPath . '/includes/app.php') || file_exists($adminPath . '/includes/defines.php')) {
            break;
        }
        $adminPath = dirname($adminPath);
    }
    define('JPATH_BASE', $adminPath);
}

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$app = Factory::getApplication('administrator');

$user = Factory::getUser();
if ($user->guest || !$user->authorise('core.manage', 'com_k2')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$input = $app->input;
$task = $input->getString('task', '');

require_once __DIR__ . '/helper.php';

header('Content-Type: application/json; charset=utf-8');

switch ($task) {
    case 'days':
        $year = $input->getInt('year', 0);
        $month = $input->getInt('month', 0);
        if (!$year || !$month) {
            echo json_encode([]);
            exit;
        }
        $db = Factory::getDbo();
        $query = "SELECT DAY(created) AS dy, SUM(hits) AS total_hits, COUNT(*) AS num_items
            FROM #__k2_items
            WHERE trash = 0 AND published = 1
              AND YEAR(created) = " . (int)$year . "
              AND MONTH(created) = " . (int)$month . "
            GROUP BY DAY(created)
            ORDER BY dy DESC";
        $db->setQuery($query);
        echo json_encode($db->loadObjectList());
        break;

    case 'items':
        $date = $input->getString('date', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode([]);
            exit;
        }
        $db = Factory::getDbo();
        $query = "SELECT i.id, i.title, i.hits, i.created, v.name AS author
            FROM #__k2_items AS i
            LEFT JOIN #__users AS v ON v.id = i.created_by
            WHERE i.trash = 0 AND i.published = 1
              AND DATE(i.created) = " . $db->quote($date) . "
            ORDER BY i.hits DESC";
        $db->setQuery($query, 0, 10);
        echo json_encode($db->loadObjectList());
        break;

    default:
        echo json_encode(['error' => 'Unknown task']);
}
