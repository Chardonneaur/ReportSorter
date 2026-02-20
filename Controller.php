<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ReportSorter;

use Piwik\Common;
use Piwik\DataTable\Renderer\Json;
use Piwik\Piwik;

class Controller extends \Piwik\Plugin\Controller
{
    private const MAX_PAYLOAD_BYTES = 102400; // 100 KB

    /**
     * Save a custom report order for the current user on a given subcategory page.
     * POST: categoryId, subcategoryId, reportIds (JSON-encoded array of widget uniqueIds),
     *       token_auth, force_api_session=1
     */
    public function saveOrder()
    {
        $this->checkTokenInUrl();
        Piwik::checkUserIsNotAnonymous();

        $categoryId    = Common::getRequestVar('categoryId', '', 'string');
        $subcategoryId = Common::getRequestVar('subcategoryId', '', 'string');

        if (empty($categoryId) || empty($subcategoryId)) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'Category and subcategory are required.']);
        }

        if (!Model::isValidCategoryId($categoryId) || !Model::isValidCategoryId($subcategoryId)) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'Invalid category or subcategory format.']);
        }

        // Read reportIds from raw POST — Common::getRequestVar sanitizes quotes,
        // which corrupts the JSON string.
        $reportIdsRaw = isset($_POST['reportIds']) && is_string($_POST['reportIds'])
            ? $_POST['reportIds']
            : '[]';

        if (strlen($reportIdsRaw) > self::MAX_PAYLOAD_BYTES) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'reportIds payload too large.']);
        }

        $decoded = json_decode($reportIdsRaw, true);
        if (!is_array($decoded)) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'reportIds must be a valid JSON array.']);
        }

        // Only allow Matomo widget uniqueId format (widget + alphanumeric chars).
        // Cap at 200 entries — far more than any real subcategory page has.
        $sanitized = [];
        foreach (array_slice($decoded, 0, 200) as $id) {
            $id = (string) $id;
            if (preg_match('/^widget[A-Za-z0-9._+\-]{1,400}$/', $id)) {
                $sanitized[] = $id;
            }
        }

        $login = Piwik::getCurrentUserLogin();
        $model = new Model();

        if (!$model->hasOrder($login, $categoryId, $subcategoryId)
            && $model->countRowsForUser($login) >= Model::MAX_ROWS_PER_USER
        ) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'Too many saved orders. Please reset some pages first.']);
        }

        $model->saveOrder($login, $categoryId, $subcategoryId, $sanitized);

        Json::sendHeaderJSON();
        return json_encode(['result' => 'success']);
    }

    /**
     * Reset the saved report order for the current user on a given subcategory page.
     * POST: categoryId, subcategoryId, token_auth, force_api_session=1
     */
    public function resetOrder()
    {
        $this->checkTokenInUrl();
        Piwik::checkUserIsNotAnonymous();

        $categoryId    = Common::getRequestVar('categoryId', '', 'string');
        $subcategoryId = Common::getRequestVar('subcategoryId', '', 'string');

        if (empty($categoryId) || empty($subcategoryId)) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'Category and subcategory are required.']);
        }

        if (!Model::isValidCategoryId($categoryId) || !Model::isValidCategoryId($subcategoryId)) {
            Json::sendHeaderJSON();
            return json_encode(['result' => 'error', 'message' => 'Invalid category or subcategory format.']);
        }

        $login = Piwik::getCurrentUserLogin();
        $model = new Model();
        $model->deleteOrder($login, $categoryId, $subcategoryId);

        Json::sendHeaderJSON();
        return json_encode(['result' => 'success']);
    }
}
