<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Filter for the open api.
 */
class ReactionsFilterOpenApi
{
    /**
     * Filter the open api to add reactions parameters.
     *
     * @param array $openApi
     */
    public function __invoke(array &$openApi): void
    {
        // Add "reactionsReceived" to the userExpand options.
        $userExpandEnum = \Vanilla\Utility\ArrayUtils::getByPath(
            "components.parameters.UserExpand.schema.items.enum",
            $openApi,
            []
        );

        $userExpandEnum[] = "reactionsReceived";
        \Vanilla\Utility\ArrayUtils::setByPath(
            "components.parameters.UserExpand.schema.items.enum",
            $openApi,
            $userExpandEnum
        );
    }
}
