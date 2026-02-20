<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ReportSorter;

use Piwik\Piwik;

class API extends \Piwik\Plugin\API
{
    private Model $model;

    public function __construct()
    {
        $this->model = new Model();
    }

    /**
     * Save a custom report order for the current user on a given subcategory page.
     *
     * @param string $categoryId    e.g. "General_Visitors"
     * @param string $subcategoryId e.g. "DevicesDetection_Software"
     * @param string $reportIds     JSON-encoded array of widget uniqueIds in desired order
     */
    public function saveReportOrder(string $categoryId, string $subcategoryId, string $reportIds = '[]'): void
    {
        Piwik::checkUserIsNotAnonymous();

        $categoryId    = trim($categoryId);
        $subcategoryId = trim($subcategoryId);

        if (empty($categoryId) || empty($subcategoryId)) {
            throw new \InvalidArgumentException('Category and subcategory are required.');
        }

        if (!Model::isValidCategoryId($categoryId) || !Model::isValidCategoryId($subcategoryId)) {
            throw new \InvalidArgumentException('Invalid category or subcategory format.');
        }

        $decoded = json_decode($reportIds, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('reportIds must be a valid JSON array.');
        }

        $sanitized = [];
        foreach (array_slice($decoded, 0, 200) as $id) {
            $id = (string) $id;
            if (preg_match('/^widget[A-Za-z0-9._+\-]{1,400}$/', $id)) {
                $sanitized[] = $id;
            }
        }

        $login = Piwik::getCurrentUserLogin();

        if (!$this->model->hasOrder($login, $categoryId, $subcategoryId)
            && $this->model->countRowsForUser($login) >= Model::MAX_ROWS_PER_USER
        ) {
            throw new \RuntimeException('Too many saved orders. Please reset some pages first.');
        }

        $this->model->saveOrder($login, $categoryId, $subcategoryId, $sanitized);
    }

    /**
     * Get the saved report order for the current user on a given subcategory page.
     *
     * @param string $categoryId
     * @param string $subcategoryId
     * @return array  Ordered list of widget uniqueIds, or empty array if no custom order.
     */
    public function getReportOrder(string $categoryId, string $subcategoryId): array
    {
        Piwik::checkUserIsNotAnonymous();

        $categoryId    = trim($categoryId);
        $subcategoryId = trim($subcategoryId);

        if (!Model::isValidCategoryId($categoryId) || !Model::isValidCategoryId($subcategoryId)) {
            throw new \InvalidArgumentException('Invalid category or subcategory format.');
        }

        $login = Piwik::getCurrentUserLogin();
        return $this->model->getOrder($login, $categoryId, $subcategoryId);
    }

    /**
     * Reset the saved report order for the current user on a given subcategory page.
     *
     * @param string $categoryId
     * @param string $subcategoryId
     */
    public function resetReportOrder(string $categoryId, string $subcategoryId): void
    {
        Piwik::checkUserIsNotAnonymous();

        $categoryId    = trim($categoryId);
        $subcategoryId = trim($subcategoryId);

        if (!Model::isValidCategoryId($categoryId) || !Model::isValidCategoryId($subcategoryId)) {
            throw new \InvalidArgumentException('Invalid category or subcategory format.');
        }

        $login = Piwik::getCurrentUserLogin();
        $this->model->deleteOrder($login, $categoryId, $subcategoryId);
    }
}
