<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ReportSorter;

use Piwik\Common;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Widget\WidgetsList;

class ReportSorter extends \Piwik\Plugin
{
    public function registerEvents(): array
    {
        return [
            // Widget.filterWidgets fires in WidgetsList::get() after all widgets are built.
            // Using 'after' ensures we run last so we override any default ordering.
            'Widget.filterWidgets'            => ['function' => 'onWidgetsAdded', 'after' => true],
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getCssFiles',
        ];
    }

    public function install(): void
    {
        $table = Common::prefixTable('report_sorter_order');
        Db::exec("
            CREATE TABLE IF NOT EXISTS `$table` (
                `user_login`      VARCHAR(100)  NOT NULL,
                `category_id`     VARCHAR(200)  NOT NULL,
                `subcategory_id`  VARCHAR(200)  NOT NULL,
                `report_ids`      TEXT          NOT NULL,
                PRIMARY KEY (`user_login`, `category_id`(100), `subcategory_id`(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function uninstall(): void
    {
        Db::query("DROP TABLE IF EXISTS `" . Common::prefixTable('report_sorter_order') . "`");
    }

    /**
     * Fired after all plugins have added their widgets.
     * Modifies widget order values based on the current user's saved preferences.
     */
    public function onWidgetsAdded(WidgetsList $widgetsList): void
    {
        if (Piwik::isUserIsAnonymous()) {
            return;
        }

        $login     = Piwik::getCurrentUserLogin();
        $model     = new Model();
        $allOrders = $model->getAllOrdersForUser($login);

        if (empty($allOrders)) {
            return;
        }

        foreach ($widgetsList->getWidgetConfigs() as $widget) {
            $catId    = $widget->getCategoryId();
            $subcatId = $widget->getSubcategoryId();

            if (empty($catId) || empty($subcatId) || !isset($allOrders[$catId][$subcatId])) {
                continue;
            }

            $orderedIds = $allOrders[$catId][$subcatId];
            $orderMap   = array_flip($orderedIds); // [widgetUniqueId => position]
            $uniqueId   = $widget->getUniqueId();

            if (isset($orderMap[$uniqueId])) {
                // Set widget order to the user's saved position.
                // We use 1000+ to keep custom-sorted widgets after any non-sorted ones.
                $widget->setOrder(1000 + (int) $orderMap[$uniqueId]);
            }
        }
    }

    public function getJsFiles(array &$jsFiles): void
    {
        $jsFiles[] = 'plugins/ReportSorter/javascripts/reportSorter.js';
    }

    public function getCssFiles(array &$cssFiles): void
    {
        $cssFiles[] = 'plugins/ReportSorter/stylesheets/reportSorter.css';
    }
}
