/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LoadingRectangle } from "@library/loaders/LoadingRectangle";

export function OpenApiLoader() {
    return (
        <div>
            <LoadingRectangle height={30} width={"100%"} />
            <LoadingRectangle height={30} width={"100%"} />
        </div>
    );
}
