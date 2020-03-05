<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

use Garden\Web\Data;
use Garden\Web\Exception\HttpException;

/**
 * A redux error action to render a frontend error page.
 */
class ReduxErrorAction extends ReduxAction {

    private const ACTION_TYPE = "@@serverPage/ERROR";

    /**
     * @param \Throwable $throwable The exception to create the error for.
     */
    public function __construct(\Throwable $throwable) {
        $data = [
            'message' => $throwable->getMessage(),
            'status' => $throwable->getCode(),
        ];

        if ($throwable instanceof HttpException) {
            $data['description'] = $throwable->getDescription();
        }

        parent::__construct(self::ACTION_TYPE, new Data($data));
    }

    /**
     * Return an array of redux action to be sent.
     *
     * @return array
     */
    public function value(): array {
        return [
            "type" => $this->type,
            "payload" => $this->payload,
        ];
    }
}
