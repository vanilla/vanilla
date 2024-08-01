<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

/**
 * Class used to wrap the arguments for the next run in a long runner.
 */
final class LongRunnerNextArgs implements \JsonSerializable
{
    /** @var array */
    private $args;

    /**
     * Constructor.
     *
     * @param array $args The arguments to pass for the next invocation of the method.
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->getArgs();
    }
}
