<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Resolvers;

use Garden\Hydrate\DataHydrator;

/**
 * Exception handler for the layout service.
 *
 * Propagates errors up to the nearest React node and replaces them with a React error component.
 */
class ReactLayoutExceptionHandler implements \Garden\Hydrate\ExceptionHandlerInterface {

    /**
     * @inheritdoc
     */
    public function handleException(\Throwable $ex, array $data, array $params) {
        $hydrateKey = $data[DataHydrator::KEY_HYDRATE] ?? '';
        $isReactNode = stringBeginsWith($hydrateKey, 'react') || stringBeginsWith($hydrateKey, 'section');
        if (!$isReactNode) {
            // Propagate the exception up until we hit a React node.
            throw $ex;
        }

        $errorBody = $ex instanceof \JsonSerializable ? $ex : [
            'message' => $ex->getMessage(),
            'code' => $ex->getCode(),
        ];

        return [
            '$reactComponent' => "LayoutError",
            '$reactProps' => [
                'layoutDefinition' => $data,
                'error' => $errorBody,
            ]
        ];
    }
}
