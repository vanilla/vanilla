<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2022-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

use Garden\Web\Data;

/**
 * Class ReduxAction.
 */
class RawReduxAction extends ReduxAction
{
    /** @var array */
    private $action;

    /**
     * Constructor.
     *
     * @param array $action
     */
    public function __construct(array $action)
    {
        parent::__construct("layout", new Data($action));
        $this->action = $action;
    }

    /**
     * Get the array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->action;
    }
}
