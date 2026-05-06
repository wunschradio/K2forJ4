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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Quick and dirty fix for Joomla 3.0 missing CSS tabs when creating tabs using the API.
// Should be removed when Joomla fixes that...
$document = Factory::getDocument();
$document->addStyleDeclaration('
		dl.tabs {float:left;margin:10px 0 -1px 0;z-index:50;}
		dl.tabs dt {float:left;padding:4px 10px;border:1px solid #ccc;margin-left:3px;background:#e9e9e9;color:#666;}
		dl.tabs dt.open {background:#f9f9f9;border-bottom:1px solid #f9f9f9;z-index:100;color:#000;}
		div.current {clear:both;border:1px solid #ccc;padding:10px 10px;background:#f9f9f9;}
		dl.tabs h3 {font-size:12px;line-height:12px;margin:4px;}
	');

// Import Joomla tabs
jimport('joomla.html.pane');

$selector = 'k2StatsTabs'.$module->id;
?>

<div class="clr"></div>


<?php echo HTMLHelper::_('uitab.startTabSet', $selector, ['active' => 'latestItemsTab', 'recall' => true]); ?>
<?php if($params->get('latestItems', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'latestItemsTab'.$module->id, Text::_('K2_LATEST_ITEMS')); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_TITLE'); ?></td>
            <td class="title"><?php echo Text::_('K2_CREATED'); ?></td>
            <td class="title"><?php echo Text::_('K2_AUTHOR'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach($latestItems as $latest): ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_k2&view=item&cid='.$latest->id); ?>"><?php echo $latest->title; ?></a></td>
                <td><?php echo HTMLHelper::_('date', $latest->created , Text::_('K2_DATE_FORMAT')); ?></td>
                <td><?php echo $latest->author; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('popularItems30', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'popularItemsTab'.$module->id, Text::_('K2_POPULAR_ITEMS').' ('.Text::_('JMONTH').')'); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_TITLE'); ?></td>
            <td class="title"><?php echo Text::_('K2_HITS'); ?></td>
            <td class="title"><?php echo Text::_('K2_CREATED'); ?></td>
            <td class="title"><?php echo Text::_('K2_AUTHOR'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach($popularItems30 as $popular): ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_k2&view=item&cid='.$popular->id); ?>"><?php echo $popular->title; ?></a></td>
                <td><?php echo $popular->hits; ?></td>
                <td><?php echo HTMLHelper::_('date', $popular->created , Text::_('K2_DATE_FORMAT')); ?></td>
                <td><?php echo $popular->author; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('popularItems', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'popularItemsTab'.$module->id, Text::_('K2_POPULAR_ITEMS')); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_TITLE'); ?></td>
            <td class="title"><?php echo Text::_('K2_HITS'); ?></td>
            <td class="title"><?php echo Text::_('K2_CREATED'); ?></td>
            <td class="title"><?php echo Text::_('K2_AUTHOR'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach($popularItems as $popular): ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_k2&view=item&cid='.$popular->id); ?>"><?php echo $popular->title; ?></a></td>
                <td><?php echo $popular->hits; ?></td>
                <td><?php echo HTMLHelper::_('date', $popular->created , Text::_('K2_DATE_FORMAT')); ?></td>
                <td><?php echo $popular->author; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('mostCommentedItems', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'mostCommentedItemsTab'.$module->id, Text::_('K2_MOST_COMMENTED_ITEMS')); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_TITLE'); ?></td>
            <td class="title"><?php echo Text::_('K2_COMMENTS'); ?></td>
            <td class="title"><?php echo Text::_('K2_CREATED'); ?></td>
            <td class="title"><?php echo Text::_('K2_AUTHOR'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach($mostCommentedItems as $mostCommented): ?>
            <tr>
                <td><a href="<?php echo Route::_('index.php?option=com_k2&view=item&cid='.$mostCommented->id); ?>"><?php echo $mostCommented->title; ?></a></td>
                <td><?php echo $mostCommented->numOfComments; ?></td>
                <td><?php echo HTMLHelper::_('date', $mostCommented->created , Text::_('K2_DATE_FORMAT')); ?></td>
                <td><?php echo $mostCommented->author; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('latestComments', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'latestCommentsTab'.$module->id, Text::_('K2_LATEST_COMMENTS')); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_COMMENT'); ?></td>
            <td class="title"><?php echo Text::_('K2_ADDED_ON'); ?></td>
            <td class="title"><?php echo Text::_('K2_POSTED_BY'); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php foreach($latestComments as $latest): ?>
            <tr>
                <td><?php echo $latest->commentText; ?></td>
                <td><?php echo HTMLHelper::_('date', $latest->commentDate , Text::_('K2_DATE_FORMAT')); ?></td>
                <td><?php echo $latest->userName; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('hitsPerMonth', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'hitsPerMonthTab'.$module->id, Text::_('K2_HITS')); ?>
    <?php
    $monthNames = ['', 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $monthNamesFull = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $chartLabels = [];
    $chartData = [];
    foreach ($hitsPerMonth as $row) {
        $chartLabels[] = $monthNames[(int)$row->mn] . ' ' . substr($row->yr, 2);
        $chartData[] = (int)$row->total_hits;
    }
    ?>
    <div id="k2HitsPerMonth<?php echo $module->id; ?>">
        <div style="height:250px;margin-bottom:15px;"><canvas id="k2HitsChart<?php echo $module->id; ?>"></canvas></div>
        <div id="k2HitsBreadcrumb<?php echo $module->id; ?>" style="margin-bottom:10px;font-weight:bold;"></div>
        <table class="adminlist table table-striped" id="k2HitsTable<?php echo $module->id; ?>">
            <thead>
            <tr>
                <td class="title">Monat</td>
                <td class="title" style="text-align:right"><?php echo Text::_('K2_HITS'); ?></td>
                <td class="title" style="text-align:right">Artikel</td>
                <td class="title" style="text-align:right">&#8709; / Artikel</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach(array_reverse($hitsPerMonth) as $row):
                $avg = (int)$row->num_items > 0 ? round((int)$row->total_hits / (int)$row->num_items) : 0;
            ?>
                <tr>
                    <td><a href="#" class="k2-drill-month" data-year="<?php echo (int)$row->yr; ?>" data-month="<?php echo (int)$row->mn; ?>"><?php echo $monthNamesFull[(int)$row->mn] . ' ' . (int)$row->yr; ?></a></td>
                    <td style="text-align:right"><?php echo number_format((int)$row->total_hits, 0, ',', '.'); ?></td>
                    <td style="text-align:right"><?php echo (int)$row->num_items; ?></td>
                    <td style="text-align:right"><?php echo number_format($avg, 0, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    $daysByMonth = [];
    foreach ($hitsPerDay as $d) {
        $key = (int)$d->yr . '-' . (int)$d->mn;
        $daysByMonth[$key][] = $d;
    }
    $itemsByDay = [];
    foreach ($topItemsPerDay as $date => $items) {
        $itemsByDay[$date] = [];
        foreach ($items as $item) {
            $itemsByDay[$date][] = [
                'id' => $item->id,
                'title' => $item->title,
                'hits' => (int)$item->hits,
                'author' => $item->author
            ];
        }
    }
    $itemsByMonth = [];
    foreach ($topItemsPerMonth as $key => $items) {
        $itemsByMonth[$key] = [];
        foreach ($items as $item) {
            $itemsByMonth[$key][] = [
                'id' => $item->id,
                'title' => $item->title,
                'hits' => (int)$item->hits,
                'author' => $item->author
            ];
        }
    }
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    (function(){
        var modId = <?php echo $module->id; ?>;
        var monthNames = <?php echo json_encode($monthNamesFull); ?>;
        var hitsLabel = <?php echo json_encode(Text::_('K2_HITS')); ?>;
        var dayData = <?php echo json_encode($daysByMonth); ?>;
        var itemData = <?php echo json_encode($itemsByDay); ?>;
        var monthItemData = <?php echo json_encode($itemsByMonth); ?>;
        var tableEl = document.getElementById('k2HitsTable' + modId);
        var originalHTML = tableEl.innerHTML;
        var chartWrapper = document.getElementById('k2HitsChart' + modId).parentNode;
        var chartCanvas = document.getElementById('k2HitsChart' + modId);

        var chart = new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: hitsLabel,
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return ctx.parsed.y.toLocaleString('de-DE') + ' ' + hitsLabel; }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v) { return v.toLocaleString('de-DE'); } }
                    }
                }
            }
        });

        function setBreadcrumb(html) {
            document.getElementById('k2HitsBreadcrumb' + modId).innerHTML = html;
        }

        function removeTopDiv() {
            var el = document.getElementById('k2MonthTop' + modId);
            if (el) el.remove();
        }

        function resetToMonths() {
            removeTopDiv();
            tableEl.innerHTML = originalHTML;
            setBreadcrumb('');
            chartWrapper.style.display = '';
            bindMonthLinks();
        }

        function bindMonthLinks() {
            tableEl.querySelectorAll('.k2-drill-month').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    drillMonth(parseInt(this.dataset.year), parseInt(this.dataset.month));
                });
            });
        }

        function drillMonth(year, month) {
            removeTopDiv();
            chartWrapper.style.display = 'none';
            var key = year + '-' + month;
            var data = dayData[key] || [];

            setBreadcrumb('<a href="#" id="k2BackToMonths' + modId + '">Monate</a> &raquo; ' + monthNames[month] + ' ' + year);
            document.getElementById('k2BackToMonths' + modId).addEventListener('click', function(e) { e.preventDefault(); resetToMonths(); });

            var thead = '<tr><td class="title">Tag</td><td class="title" style="text-align:right">' + hitsLabel + '</td><td class="title" style="text-align:right">Artikel</td><td class="title" style="text-align:right">&#8709; / Artikel</td></tr>';
            var tbody = '';
            data.forEach(function(row) {
                var hits = Number(row.total_hits);
                var items = Number(row.num_items);
                var avg = items > 0 ? Math.round(hits / items) : 0;
                var dateStr = year + '-' + String(month).padStart(2,'0') + '-' + String(row.dy).padStart(2,'0');
                tbody += '<tr><td><a href="#" class="k2-drill-day" data-date="' + dateStr + '" data-year="' + year + '" data-month="' + month + '" data-day="' + row.dy + '">' + row.dy + '. ' + monthNames[month] + '</a></td>';
                tbody += '<td style="text-align:right">' + hits.toLocaleString('de-DE') + '</td>';
                tbody += '<td style="text-align:right">' + items + '</td>';
                tbody += '<td style="text-align:right">' + avg.toLocaleString('de-DE') + '</td></tr>';
            });
            if (!tbody) tbody = '<tr><td colspan="4">Keine Daten</td></tr>';

            var topItems = monthItemData[key] || [];
            var topHtml = '';
            if (topItems.length) {
                topHtml = '<h4 style="margin:20px 0 8px">Top 10 Artikel – ' + monthNames[month] + ' ' + year + '</h4>';
                topHtml += '<table class="adminlist table table-striped"><thead><tr><td class="title">Titel</td><td class="title" style="text-align:right">' + hitsLabel + '</td><td class="title">Autor</td></tr></thead><tbody>';
                topItems.forEach(function(row) {
                    topHtml += '<tr><td><a href="index.php?option=com_k2&view=item&cid=' + row.id + '">' + row.title + '</a></td>';
                    topHtml += '<td style="text-align:right">' + Number(row.hits).toLocaleString('de-DE') + '</td>';
                    topHtml += '<td>' + (row.author || '') + '</td></tr>';
                });
                topHtml += '</tbody></table>';
            }

            tableEl.innerHTML = '<thead>' + thead + '</thead><tbody>' + tbody + '</tbody>';
            tableEl.insertAdjacentHTML('afterend', '<div id="k2MonthTop' + modId + '">' + topHtml + '</div>');

            tableEl.querySelectorAll('.k2-drill-day').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    drillDay(this.dataset.date, parseInt(this.dataset.year), parseInt(this.dataset.month), parseInt(this.dataset.day));
                });
            });
        }

        function drillDay(dateStr, year, month, day) {
            removeTopDiv();
            var data = itemData[dateStr] || [];

            setBreadcrumb(
                '<a href="#" id="k2BackToMonths2' + modId + '">Monate</a> &raquo; ' +
                '<a href="#" id="k2BackToMonth' + modId + '">' + monthNames[month] + ' ' + year + '</a> &raquo; ' +
                day + '. ' + monthNames[month]
            );
            document.getElementById('k2BackToMonths2' + modId).addEventListener('click', function(e) { e.preventDefault(); resetToMonths(); });
            document.getElementById('k2BackToMonth' + modId).addEventListener('click', function(e) { e.preventDefault(); drillMonth(year, month); });

            var thead = '<tr><td class="title">Titel</td><td class="title" style="text-align:right">' + hitsLabel + '</td><td class="title">Autor</td></tr>';
            var tbody = '';
            data.forEach(function(row) {
                tbody += '<tr><td><a href="index.php?option=com_k2&view=item&cid=' + row.id + '">' + row.title + '</a></td>';
                tbody += '<td style="text-align:right">' + Number(row.hits).toLocaleString('de-DE') + '</td>';
                tbody += '<td>' + (row.author || '') + '</td></tr>';
            });
            if (!tbody) tbody = '<tr><td colspan="3">Keine Artikel an diesem Tag</td></tr>';
            tableEl.innerHTML = '<thead>' + thead + '</thead><tbody>' + tbody + '</tbody>';
        }

        bindMonthLinks();
    })();
    </script>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>

<?php if($params->get('statistics', 1)): ?>
    <?php echo HTMLHelper::_('uitab.addTab', $selector, 'statsTab'.$module->id, Text::_('K2_STATISTICS')); ?>
    <table class="adminlist table table-striped">
        <thead>
        <tr>
            <td class="title"><?php echo Text::_('K2_TYPE'); ?></td>
            <td class="title"><?php echo Text::_('K2_COUNT'); ?></td>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><?php echo Text::_('K2_ITEMS'); ?></td>
            <td><?php echo $statistics->numOfItems; ?> (<?php echo $statistics->numOfFeaturedItems.' '.Text::_('K2_FEATURED').' - '.$statistics->numOfTrashedItems.' '.Text::_('K2_TRASHED'); ?>)</td>
        </tr>
        <tr>
            <td><?php echo Text::_('K2_CATEGORIES'); ?></td>
            <td><?php echo $statistics->numOfCategories; ?> (<?php echo $statistics->numOfTrashedCategories.' '.Text::_('K2_TRASHED'); ?>)</td>
        </tr>
        <tr>
            <td><?php echo Text::_('K2_TAGS'); ?></td>
            <td><?php echo $statistics->numOfTags; ?></td>
        </tr>
        <tr>
            <td><?php echo Text::_('K2_COMMENTS'); ?></td>
            <td><?php echo $statistics->numOfComments; ?></td>
        </tr>
        <tr>
            <td><?php echo Text::_('K2_USERS'); ?></td>
            <td><?php echo $statistics->numOfUsers; ?></td>
        </tr>
        <tr>
            <td><?php echo Text::_('K2_USER_GROUPS'); ?></td>
            <td><?php echo $statistics->numOfUserGroups; ?></td>
        </tr>
        </tbody>
    </table>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
