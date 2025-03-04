<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Ramsey\Uuid\Uuid;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;

/**
 * Processor that fills in UUID fields on insert.
 */
class PrimaryKeyUuidProcessor implements Processor
{
    /**
     * @param string $primaryKey
     */
    public function __construct(private string $primaryKey)
    {
    }

    /**
     * @inheritdoc
     */
    public function handle(Operation $operation, callable $stack)
    {
        if ($operation->getType() === Operation::TYPE_INSERT) {
            $set = $operation->getSet();
            $fieldExists = $operation
                ->getCaller()
                ->getWriteSchema()
                ->getField("properties.{$this->primaryKey}");
            if ($fieldExists && !isset($set[$this->primaryKey])) {
                $operation->setSetItem($this->primaryKey, Uuid::uuid7(CurrentTimeStamp::getDateTime())->toString());
            }

            $primaryKey = $operation->getSetItem($this->primaryKey);
            $result = $stack($operation);
            if ($result == 0) {
                // PDO gave us a successful insert, but it's not an autoincrement (primary key is UUID).
                // Return primary key instead
                return $primaryKey;
            } else {
                return $result;
            }
        }
        return $stack($operation);
    }
}
