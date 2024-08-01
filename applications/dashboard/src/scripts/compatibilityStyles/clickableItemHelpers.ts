/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { Mixins } from "@library/styles/Mixins";
import { injectGlobal } from "@emotion/css";

export const mixinClickInput = (selector: string, overwriteColors?: {}, overwriteSpecial?: {}) => {
    const linkColors = Mixins.clickable.itemState(overwriteColors, overwriteSpecial);

    injectGlobal({
        [selector]: { ...linkColors },
    });
};
