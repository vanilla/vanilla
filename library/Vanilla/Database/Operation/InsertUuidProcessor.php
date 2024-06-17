<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Ramsey\Uuid\Uuid;
use Vanilla\Database\Operation;

/**
 * Processor that fills in UUID fields on insert.
 */
class InsertUuidProcessor implements Processor
{
    /** @var string[] */
    private array $uuidFields;

    /**
     * @param string[] $uuidFields
     */
    public function __construct(array $uuidFields)
    {
        $this->uuidFields = $uuidFields;
    }

    /**
     * @inheritdoc
     */
    public function handle(Operation $operation, callable $stack)
    {
        if ($operation->getType() === Operation::TYPE_INSERT) {
            $set = $operation->getSet();
            foreach ($this->uuidFields as $field) {
                $fieldExists = $operation
                    ->getCaller()
                    ->getWriteSchema()
                    ->getField("properties.{$field}");
                if ($fieldExists && !isset($set[$field])) {
                    $operation->setSetItem($field, Uuid::uuid4()->toString());
                }
            }
        }
        return $stack($operation);
    }
}
