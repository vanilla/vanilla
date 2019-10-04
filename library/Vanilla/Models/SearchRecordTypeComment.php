<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Vanilla\Contracts\Search\SearchRecordTypeTrait;

/**
 * Class SearchRecordTypeComment
 * @package Vanilla\Models
 */
class SearchRecordTypeComment implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'advanced';

    const TYPE = 'comment';

    const API_TYPE_KEY = 'comment';

    const SUB_KEY = 'c';

    const CHECKBOX_LABEL = 'comments';

    const SPHINX_DTYPE = 100;

    const SPHINX_INDEX = 'Comment';

    const GUID_OFFSET = 2;

    const GUID_MULTIPLIER = 10;

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        $result = $searchModel->getComments($IDs);
        foreach ($result as &$record) {
            $record['guid'] = $record['PrimaryID'] * self::GUID_MULTIPLIER + self::GUID_OFFSET;
        }
        return $result;
    }
}
