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
class SearchResultItem implements \JsonSerializable {

    use JsonFilterTrait;

    /** @var Schema */
    private $schema;

    /** @var Data */
    private $data;

    /**
     * Constructor.
     *
     * @param Data $data
     */
    public function __construct(Data $data) {
        $this->data = $this->fullSchema()->validate($data);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->jsonFilter($this->data);
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
                'recordType:s?',
                'recordID:s?',
                'name:s?',
                'url' => [
                    'type' => 'string',
                    'format' => 'uri',
                ],
                'breadcrumbs:a?' => new InstanceValidatorSchema(Breadcrumb::class),
            ]);

            $extra = $this->extraSchema();
            if ($extra !== null) {
                $schema = $schema->merge($extra);
            }

            $this->schema = $schema;
        }

        return $this->schema;
    }
}
