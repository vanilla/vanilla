/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { trimTrailingCommas } from "@dashboard/compatibilityStyles/trimTrailingCommas";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { Mixins } from "@library/styles/Mixins";

export const mixinClickInput = (selector: string, overwriteColors?: {}, overwriteSpecial?: {}) => {
    selector = trimTrailingCommas(selector);
    const selectors = selector.split(",");
    const linkColors = Mixins.clickable.itemState(overwriteColors, overwriteSpecial);
    selectors.map((s) => {
        if (linkColors.color !== undefined) {
            cssOut(selector, {
                color: linkColors.color,
            });
        }
        cssOut(trimTrailingCommas(s), linkColors);
    });
};
