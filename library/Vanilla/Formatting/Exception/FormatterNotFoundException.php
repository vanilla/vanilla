<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Exception;

/**
 * Exception thrown when looking up a format that has not been registered.
 */
class FormatterNotFoundException extends \Garden\Web\Exception\ServerException {
}
