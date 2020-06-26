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
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Class to hold a search result.
 */
class SearchResultItem implements \JsonSerializable, \ArrayAccess {

    use JsonFilterTrait;

    /** @var Schema */
    private $schema;

    /** @var Data */
    private $data;

    /** @var int */
    private $altRecordID;

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $this->fullSchema()->validate($data);
    }

    /**
     * Set an alternative recordID that may be used to identify the item.
     *
     * @param int $altRecordID
     */
    public function setAltRecordID(int $altRecordID): void {
        $this->altRecordID = $altRecordID;
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
            $schema = Schema::parse([
                'recordType:s',
                'type:s',
                'body:s?',
                'recordID:i',
                'name:s',
                'url' => [
                    'type' => 'string',
                    'format' => 'uri',
                ],
                'dateInserted:dt',
                'breadcrumbs:a?' => new InstanceValidatorSchema(Breadcrumb::class),
                "insertUserID:i"
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
    public function getAltRecordID(): ?int {
        return $this->altRecordID;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->data['name'];
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->data['url'];
    }

    ///
    /// PHP Interfaces
    ///

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->jsonFilter($this->data);
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
}
