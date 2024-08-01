<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Exception;

use Vanilla\Cli\Utils\ShellProfile;

/**
 * Exception if someone tries a shell operation that wasn't supported.
 */
class UnsupportedShellException extends \Exception
{
    /**
     * Constructor.
     *
     * @param string $shell The name of the unsupported shell.
     */
    public function __construct(string $shell)
    {
        $msg = "Unsupported shell '$shell'. Expected one of:\n";
        $msg .= implode(", ", ShellProfile::SUPPORTED_SHELLS);
        parent::__construct($msg, 500);
    }
}
