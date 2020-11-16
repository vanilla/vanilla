<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\JsonFilterTrait;
use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Utility\ModelUtils;

/**
 * Class to hold a search result.
 */
class SearchResultItem implements \JsonSerializable, \ArrayAccess {

    const FIELD_SCORE = "searchScore";
    const FIELD_SUBQUERY_COUNT = "subqueryMatchCount";

    use JsonFilterTrait;

    /** @var Schema */
    protected $schema;

    /** @var Data */
    protected $data;

    /** @var int */
    protected $siteID;

    /** @var string[]|bool|string */
    protected $expandFields = [];

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $this->fullSchema()->validate($data);
    }

    /**
     * Extra schema for your
     *
     * This will be added to the base schema. Any fields not specified here will be stripped.
     *
     * @example
     * The base structure looks like this.
     * [
     *     'url:s',
     *     'recordID:s',
     *     'recordType:s',
     *     'name:s'
     *     // Whatever you specify here.
     * ]
     *
     * @return Schema
     */
    protected function extraSchema(): ?Schema {
        return null;
    }

    /**
     * @return Schema
     */
    protected function fullSchema(): Schema {
        if ($this->schema === null) {
            $countSchema = Schema::parse([
                'count:i',
                'labelCode:s',
            ]);
            $schema = Schema::parse([
                'recordType:s',
                'type:s',
                'legacyType:s?',
                'body:s?',
                'bodyRaw:s?',
                'excerpt:s?',
                'image:s?',
                'recordID:i',
                'categoryID:i?',
                'altRecordID:i?',
                'siteID:i?',
                'siteDomain:s?',
                'name:s',
                'url' => [
                    'type' => 'string',
                    'format' => 'uri',
                ],
                'dateInserted:dt',
                'breadcrumbs:a?' => new InstanceValidatorSchema(Breadcrumb::class),
                "insertUserID:i?",
                "updateUserID:i?",
                "format:s?",
                'status:s?',
                'isForeign:b?' => [
                    'default' => false,
                ],
                "counts:a?" => $countSchema,

                // Search result specific.
                'searchScore:f?',
                'subqueryMatchCount:i?' => [
                    'default' => 1,
                ],
                'subqueryExtraParams:o?',
            ]);

            $extra = $this->extraSchema();
            if ($extra !== null) {
                $schema = $schema->merge($extra);
            }

            $this->schema = $schema;
        }

        return $this->schema;
    }

    /**
     * @return string
     */
    public function getRecordType(): string {
        return $this->data['recordType'];
    }

    /**
     * @return string
     */
    public function getType(): string {
        return $this->data['type'];
    }

    /**
     * @return int
     */
    public function getRecordID(): int {
        return $this->data['recordID'];
    }

    /**
     * @return int
     */
    public function getCategoryID(): ?int {
        return $this->data['categoryID'] ?? null;
    }

    /**
     * @return int|null
     */
    public function getAltRecordID(): ?int {
        return $this->data['altRecordID'] ?? null;
    }

    /**
     * @param string $domain
     */
    public function setSiteDomain(string $domain): void {
        $this->data['siteDomain'] = $domain;
    }

    /**
     * @return ?int
     */
    public function getSiteID(): ?int {
        return $this->data['siteID'] ?? null;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['name'];
    }

    /**
     * @return bool
     */
    public function isForeign(): bool {
        return $this->data['isForeign'];
    }

    /**
     * @param bool $isForeign
     */
    public function setIsForeign(bool $isForeign): void {
        $this->data['isForeign'] = $isForeign;
    }

    /**
     * @return int|null
     */
    public function getSubqueryMatchCount(): ?int {
        return $this->data[self::FIELD_SUBQUERY_COUNT] ?? null;
    }

    /**
     * @param int|null $count
     */
    public function setSubqueryMatchCount(?int $count): void {
        $this->data[self::FIELD_SUBQUERY_COUNT] = $count;
    }

    /**
     * @return float|null
     */
    public function getSearchScore(): ?float {
        return $this->data[self::FIELD_SCORE] ?? null;
    }

    /**
     * @param float|null $score
     */
    public function setSearchScore(?float $score): void {
        $this->data[self::FIELD_SCORE] = $score;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->data['url'];
    }

    /**
     * @return string|null
     */
    public function getExcerpt(): ?string {
        if (array_key_exists('excerpt', $this->data)) {
            return $this->data['excerpt'];
        }

        if ($this->getBody() !== null && $this->getFormat() !== null) {
            return \Gdn::formatService()->renderExcerpt($this->getBody(), $this->getFormat());
        }

        return null;
    }

    /**
     * @return array|null
     */
    public function getImage(): ?array {
        if (array_key_exists('image', $this->data)) {
            $image = $this->data['image'];
            if (is_array($image)) {
                return $image;
            } elseif (is_string($image)) {
                return [
                    'url' => $image,
                    'alt' => t('Untitled'),
                ];
            }
        }
        if ($this->getBody() !== null && $this->getFormat() !== null) {
            return \Gdn::formatService()->parseImages($this->getBody(), $this->getFormat())[0] ?? null;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getImages(): array {
        if (array_key_exists('images', $this->data)) {
            return $this->data['images'];
        }
        if ($this->getBody() !== null && $this->getFormat() !== null) {
            return \Gdn::formatService()->parseImages($this->getBody(), $this->getFormat());
        }
        return [];
    }


    /**
     * @return string|null
     */
    public function getBody(): ?string {
        return $this->data['body'] ?? null;
    }
    /**
     * @return string|null
     */
    public function getBodyRaw(): ?string {
        return $this->data['bodyRaw'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getFormat(): string {
        // Workaround because sometimes we get an already formatted body.
        $format = $this->data['format'] ?? '';
        if (!$format) {
            $format = WysiwygFormat::FORMAT_KEY;
        }

        if ($format === 'rich' || $format === 'Rich' && !stringBeginsWith('[{', $this->getBody())) {
            $format = WysiwygFormat::FORMAT_KEY;
        }

        return $format;
    }

    /**
     * Set the expanded fields to output on the record.
     *
     * @param string[]|string|bool $expandFields
     */
    public function setExpands($expandFields) {
        $this->expandFields = $expandFields;
    }

    /**
     * Filter data to only include expanded fields.
     *
     * @return array The filtered data.
     */
    protected function getFilteredOutput(): array {
        // Copy so we don't modify internally.
        $data = $this->data;

        if (!ModelUtils::isExpandOption('body', $this->expandFields)) {
            unset($data['body']);
        }

        if (ModelUtils::isExpandOption('excerpt', $this->expandFields)) {
            $data['excerpt'] = $this->getExcerpt();
        } else {
            unset($data['excerpt']);
        }

        if (ModelUtils::isExpandOption('image', $this->expandFields)) {
            $data['image'] = $this->getImage();
        }

        if (!ModelUtils::isExpandOption('collapse', $this->expandFields)) {
            unset($data['subqueryMatchCount']);
            unset($data['subqueryExtraParams']);
        }

        if ($this->isForeign()) {
            unset($data['insertUser']);
            unset($data['updateUser']);
        }

        unset($data['format']);
        unset($data['rawBody']);
        return $data;
    }

    ///
    /// PHP Interfaces
    ///

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        $output = $this->getFilteredOutput();

        return $this->jsonFilter($output);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset) {
        return $this->data[$offset] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }

        $this->fullSchema()->validate($this->data);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
        $this->fullSchema()->validate($this->data);
    }

    /**
     * Return in a format that's compatible with older search pages.
     *
     * @return array
     */
    public function asLegacyArray(): array {
        $date = $this->data['dateInserted'] ?? null;
        $dateString = $date ? $date->format(\DateTime::ATOM) : null;
        $dateHtml = $dateString ? \Gdn::dateTimeFormatter()->formatDate($dateString) : null;
        $summary = $this->getExcerpt();
        // These have emoji converted.
        $summary = \Emoji::instance()->translateToHtml($summary);

        $notes = null;
        if (debug()) {
            $notes = 'Score: ' . $this->getSearchScore();
        }

        return [
            'PrimaryID' => $this->getRecordID(),
            'CategoryID' => $this->getCategoryID() ?? 0, // 0 fallback needed for compatibility.'
            'DiscussionID' => in_array($this->getRecordType(), ['discussion', 'comment']) ? $this->data['discussionID'] ?? null : null,
            'RecordType' => $this->getRecordType(),
            'Type' => $this->data['legacyType'] ?? $this->getType(),
            'Format' => HtmlFormat::FORMAT_KEY, // Forced to HTML for compatibility.
            'Summary' => $summary,
            'Url' => $this->getUrl(),
            'Title' => htmlspecialchars($this->getName()), // Encoded for legacy reasons.

            'Notes' => $notes,

            // Dates
            'DateInserted' => $dateString,
            'DateHtml' => $dateHtml,
            'Breadcrumbs' => isset($this->data['breadcrumbs']) ? BreadcrumbModel::crumbsAsArray($this->data['breadcrumbs']) : [],
            'Score' => 0,
            'Count' => $this->getSubqueryMatchCount(),

            'Media' => [],
            'images' => $this->getImages(),

            // User data.
            'UserID' => $this->data['insertUserID'] ?? -1,
            'Name' => $this->data['insertUser']['name'] ?? 'Unknown',
            'Photo' => $this->data['insertUser']['photoUrl'],
        ];
    }

    /**
     * @return Schema
     */
    public static function legacySchema(): Schema {
        return Schema::parse([
            'PrimaryID:i',
            'CategoryID:i',
            'DiscussionID:i?',
            'RecordType:s',
            'Type:s',
            'Format:s',
            'Summary:s',
            'Url:s',
            'Title:s',
            'DateInserted:s?',
            'DateHtml:s?',
            'Breadcrumbs:a' => Schema::parse([
                'Name:s',
                'Url:s',
            ]),
            'Notes:s?', // Extra metadata, eg. relevance/scoring.
            'Score:i',
            'Count:i?', // Subquery matches.

            // Left behind for compatibility with existing view overrides.
            // They won't see the media previews unless they are updated, but at least they won't break.
            'Media:a',

            // This is where the actual images live.
            'images:a' => Schema::parse([
                'url:s',
                'alt:s',
            ]),

            // User data
            'UserID:s',
            'Name:s',
            'Photo:s',
        ]);
    }
}
