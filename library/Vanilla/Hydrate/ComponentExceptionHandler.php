<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Hydrate;

use Garden\Hydrate\ExceptionHandlerInterface;

/**
 * A hydrate exception handler that handles exceptions on component boundaries, switching them to error components.
 *
 * @todo This is just a sample, the component boundary and the properties of an error component still need to be defined.
 */
class ComponentExceptionHandler implements ExceptionHandlerInterface {
    /**
     * {@inheritDoc}
     */
    public function handleException(\Throwable $ex, array $data, array $params) {
        if (array_key_exists('component', $data)) {
            $result = [
                'component' => 'error',
                'message' => $ex->getMessage(),
                'code' => $ex->getCode(),
            ];

            return $result;
        } else {
            throw $ex;
        }
    }
}
