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
    public const MAX_CATEGORY_LEN  = 200;
    public const MAX_ROWS_PER_USER = 500;

    public static function isValidCategoryId(string $value): bool
    {
        return strlen($value) <= self::MAX_CATEGORY_LEN
            && preg_match('/^[A-Za-z0-9_.\-]+$/', $value) === 1;
    }

    private function getTable(): string
    {
        return Common::prefixTable('report_sorter_order');
    }

    public function saveOrder(string $userLogin, string $categoryId, string $subcategoryId, array $reportIds): void
    {
        $json = json_encode(array_values($reportIds));
        Db::query(
            "INSERT INTO `" . $this->getTable() . "` (`user_login`, `category_id`, `subcategory_id`, `report_ids`)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE `report_ids` = ?",
            [$userLogin, $categoryId, $subcategoryId, $json, $json]
        );
    }

    public function deleteOrder(string $userLogin, string $categoryId, string $subcategoryId): void
    {
        Db::query(
            "DELETE FROM `" . $this->getTable() . "`
             WHERE `user_login` = ? AND `category_id` = ? AND `subcategory_id` = ?",
            [$userLogin, $categoryId, $subcategoryId]
        );
    }

    public function hasOrder(string $userLogin, string $categoryId, string $subcategoryId): bool
    {
        return !empty(Db::fetchOne(
            "SELECT 1 FROM `" . $this->getTable() . "`
             WHERE `user_login` = ? AND `category_id` = ? AND `subcategory_id` = ?",
            [$userLogin, $categoryId, $subcategoryId]
        ));
    }

    public function countRowsForUser(string $userLogin): int
    {
        return (int) Db::fetchOne(
            "SELECT COUNT(*) FROM `" . $this->getTable() . "` WHERE `user_login` = ?",
            [$userLogin]
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
