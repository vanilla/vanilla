<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Exception;

use Garden\Web\Exception\ServerException;

/**
 * An exception for when access to a disabled feature is attempted.
 */
class FeatureNotEnabledException extends ServerException {
}
