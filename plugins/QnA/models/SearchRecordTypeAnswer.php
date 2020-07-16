<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeTrait;

/**
 * Class SearchRecordTypeAnswer
 * @package Vanilla\QnA\Models
 */
class SearchRecordTypeAnswer implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'sphinx';

    const INFRASTRUCTURE_TEMPLATE = 'standard';

    const TYPE = 'comment';

    const API_TYPE_KEY = 'answer';

    const SUB_KEY = 'answer';

    const CHECKBOX_LABEL = 'answers';

    const SPHINX_DTYPE = 101;

    const SPHINX_INDEX = 'Comment';

    const GUID_OFFSET = 2;

    const GUID_MULTIPLIER = 10;

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        $result = $searchModel->getComments($IDs);
        foreach ($result as &$record) {
            $record['type'] = self::SUB_KEY;
            $record['guid'] = $record['PrimaryID'] * self::GUID_MULTIPLIER + self::GUID_OFFSET;
        }
        return $result;
    }
}
