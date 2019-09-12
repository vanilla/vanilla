/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { cssRule } from "typestyle";
import { important } from "csx";

export const loadedCSS = useThemeCache(() => {
    // Avoid FOUC in Forum side
    cssRule("body", {
        visibility: important("visible"),
    });
});
