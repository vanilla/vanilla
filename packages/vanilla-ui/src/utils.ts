/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { CSSObject } from "@emotion/css";

export function srOnlyMixin(): CSSObject {
    return {
        position: "absolute !important" as any,
        display: "block !important",
        width: "1px !important",
        height: "1px !important",
        padding: "0px !important",
        margin: "-1px !important",
        overflow: "hidden !important",
        clip: "rect(0, 0, 0, 0) !important",
        border: "0px !important",
    };
}
