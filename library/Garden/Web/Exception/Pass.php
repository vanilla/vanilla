<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * This exception is thrown from within a dispatched method to tell the application
 * to move on and try matching the rest of the routes.
 */
class Pass extends \Exception {
}
