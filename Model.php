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

class Model
{
    private function getTable(): string
    {
        return Common::prefixTable('report_sorter_order');
    }

    public function saveOrder(string $userLogin, string $categoryId, string $subcategoryId, array $reportIds): void
    {
        Db::query(
            "INSERT INTO `" . $this->getTable() . "` (`user_login`, `category_id`, `subcategory_id`, `report_ids`)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `report_ids` = VALUES(`report_ids`)",
            [$userLogin, $categoryId, $subcategoryId, json_encode(array_values($reportIds))]
        );
    }

    public function getOrder(string $userLogin, string $categoryId, string $subcategoryId): array
    {
        $row = Db::fetchOne(
            "SELECT `report_ids` FROM `" . $this->getTable() . "`
             WHERE `user_login` = ? AND `category_id` = ? AND `subcategory_id` = ?",
            [$userLogin, $categoryId, $subcategoryId]
        );

        if (empty($row)) {
            return [];
        }

        return json_decode($row, true) ?? [];
    }

    /**
     * Returns all saved orders for a user as a nested array:
     * [ categoryId => [ subcategoryId => [reportId, ...] ] ]
     */
    public function getAllOrdersForUser(string $userLogin): array
    {
        $rows = Db::fetchAll(
            "SELECT `category_id`, `subcategory_id`, `report_ids`
             FROM `" . $this->getTable() . "`
             WHERE `user_login` = ?",
            [$userLogin]
        );

        $result = [];
        foreach ($rows as $row) {
            $ids = json_decode($row['report_ids'], true);
            if (!empty($ids)) {
                $result[$row['category_id']][$row['subcategory_id']] = $ids;
            }
        }

        return $result;
    }
}
