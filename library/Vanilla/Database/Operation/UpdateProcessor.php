<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\Database\Operation;

/**
 * A processor to run a callback whenever an update operation is performed.
 */
class UpdateProcessor implements Processor
{
    /** @var callable(Operation, callable): void */
    private $onUpdate;

    /**
     * Constructor.
     *
     * @param callable(Operation, callable): void $onUpdate A callable to handle the operation.
     * The operation and another callable taking the operation and returning the result is given.
     */
    public function __construct(callable $onUpdate)
    {
        $this->onUpdate = $onUpdate;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Operation $operation, callable $stack)
    {
        switch ($operation->getType()) {
            case Operation::TYPE_UPDATE:
                return call_user_func($this->onUpdate, $operation, $stack);
            default:
                return $stack($operation);
        }
    }
}
