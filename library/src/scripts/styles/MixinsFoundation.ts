/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { injectGlobal } from "@emotion/css";
import { IContentBoxes } from "@library/styles/cssUtilsTypes";
import { Mixins } from "@library/styles/Mixins";

/**
 * Mixins for the foundation
 */
export class MixinsFoundation {
    public static contentBoxes(pageBoxes: IContentBoxes, section?: string, contentSelector: string = ".Content") {
        function prefixedSelector(selector: string): string {
            return section ? `.Section-${section} ${selector}` : selector;
        }
        injectGlobal({
            [prefixedSelector(`${contentSelector} .pageBox`)]: Mixins.box(pageBoxes.depth1),
            [prefixedSelector(`${contentSelector} .pageBox .pageBox`)]: Mixins.box(pageBoxes.depth2),
            [prefixedSelector(`${contentSelector} .pageBox .pageBox .pageBox`)]: Mixins.box(pageBoxes.depth3),
        });
    }
}
