<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Vanilla\ApiUtils;
use Vanilla\DateFilterSchema;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;

/**
 * Parameter schema for the discussions API controller index.
 */
class DiscussionsApiIndexSchema extends Schema {

    /**
     * Setup the schema.
     *
     * @param int $defaultLimit The default limit of items ot be returned.
     */
    public function __construct(int $defaultLimit) {
        parent::__construct($this->parseInternal([
            'discussionID?' => \Vanilla\Schema\RangeExpression::createSchema([':int'])->setField('x-filter', ['field' => 'd.discussionID']),
            'categoryID:i?' => [
                'description' => 'Filter by a category.',
                'x-filter' => [
                    'field' => 'd.CategoryID'
                ],
                'x-control' => SchemaForm::dropDown(
                    new FormOptions('Category', 'Display discussions from this category.'),
                    new ApiFormChoices(
                        "/api/v2/categories?query=%s&limit=30",
                        "/api/v2/categories/%s",
                        "categoryID",
                        "name"
                    )
                ),
            ],
            'dateInserted?' => new DateFilterSchema([
                'description' => 'When the discussion was created.',
                'x-filter' => [
                    'field' => 'd.DateInserted',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateUpdated?' => new DateFilterSchema([
                'description' => 'When the discussion was updated.',
                'x-filter' => [
                    'field' => 'd.DateUpdated',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'dateLastComment?' => new DateFilterSchema([
                'description' => 'When the last comment was posted.',
                'x-filter' => [
                    'field' => 'd.DateLastComment',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
            'type:s?' => [
                'description' => 'Filter by discussion type.',
                'x-filter' => [
                    'field' => 'd.Type'
                ],
                'x-control' => SchemaForm::dropDown(
                    new FormOptions('Discussion Type', 'Choose a specific type of discussions to display.'),
                    new StaticFormChoices($this->discussionTypesEnumValues())
                )
            ],
            'followed:b' => [
                'default' => false,
                'description' => 'Only fetch discussions from followed categories. Pinned discussions are mixed in.'
            ],
            'pinned:b?' => [
                'default' => false,
                'x-control' => SchemaForm::toggle(new FormOptions(
                    'Announcements',
                    'Only fetch announcements.'
                ))
            ],
            'pinOrder:s?' => [
                'default' => 'first',
                'enum' => ['first', 'mixed'],
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'Announcement Pinning',
                        'Choose how announcements display.'
                    ),
                    new StaticFormChoices([
                        'first' => 'Announcements display first.',
                        'mixed' => 'Announcements are displayed in the default sort order with other discussions.'
                    ])
                )
            ],
            'dirtyRecords:b?',
            'siteSectionID:s?',
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1
            ],
            'sort:s?' => [
                'enum' => ApiUtils::sortEnum('dateLastComment', 'dateInserted', 'discussionID', 'score', 'hot'),
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'Sort Order',
                        'Choose the order items are sorted.'
                    ),
                    new StaticFormChoices([
                        '-dateLastComment' => 'Recently commented',
                        '-dateInserted' => 'Recently added',
                        '-score' => 'Top',
                        '-hot' => 'Hot (score + activity)'
                    ])
                )
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $defaultLimit,
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'Limit',
                        'Choose how many discussions to display.'
                    ),
                    new StaticFormChoices([
                        '3' => 3,
                        '5' => 5,
                        '10' => 10,
                    ])
                )
            ],
            'insertUserID:i?' => [
                'description' => 'Filter by author.',
                'x-filter' => [
                    'field' => 'd.InsertUserID',
                ],
            ],
            'expand?' => \DiscussionExpandSchema::commonExpandDefinition()
        ]));
    }

    /**
     * Return ['apiType' => 'label']
     *
     * @return array
     */
    private function discussionTypesEnumValues(): array {
        $rawTypes = \DiscussionModel::discussionTypes();
        $result = [];
        foreach ($rawTypes as $rawType) {
            $apiType = $rawType['apiType'] ?? null;
            $label = $rawType['Singular'] ?? null;
            if ($apiType === null || $label === null) {
                continue;
            }

            $result[$apiType] = $label;
        }
        return $result;
    }
}
