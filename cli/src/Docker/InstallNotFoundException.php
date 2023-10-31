<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker;

/**
 * Exception thrown if we can't find our installation.
 */
class InstallNotFoundException extends \Exception
{
    /**
     * Constructor.
     *
     * @param \Throwable|null $previous
     */
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct("An existing vnla docker install was not found.", 500, $previous);
    }
}
