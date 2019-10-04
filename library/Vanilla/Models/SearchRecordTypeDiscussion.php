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
 * Class SearchRecordTypeDiscussion
 * @package Vanilla\Models
 */
class SearchRecordTypeDiscussion implements SearchRecordTypeInterface {
    use SearchRecordTypeTrait;

    const PROVIDER_GROUP = 'advanced';

    const TYPE = 'discussion';

    const API_TYPE_KEY = 'discussion';

    const SUB_KEY = 'd';

    const CHECKBOX_LABEL = 'discussions';

    const SPHINX_DTYPE = 0;

    const SPHINX_INDEX = 'Discussion';

    const GUID_OFFSET = 1;

    const GUID_MULTIPLIER = 10;

    /**
     * @inheritdoc
     */
    public function getDocuments(array $IDs, \SearchModel $searchModel): array {
        $result = $searchModel->getDiscussions($IDs);
        foreach ($result as &$record) {
            $record['guid'] = $record['PrimaryID'] * self::GUID_MULTIPLIER + self::GUID_OFFSET;
        }
        return $result;
    }
}
