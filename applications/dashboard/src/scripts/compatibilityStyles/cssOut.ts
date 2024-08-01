/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { flattenNests } from "@library/styles/styleShim";
import { CSSObject, injectGlobal } from "@emotion/css";

/**
 * @deprecated Use injectGlobal instead.
 */
export function cssOut(selector: string, ...objects: CSSObject[]): void {
    injectGlobal({
        [selector]: objects.map(flattenNests),
    });
}
