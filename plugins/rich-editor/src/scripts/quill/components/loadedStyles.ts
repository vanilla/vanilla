/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useThemeCache } from "@library/styles/styleUtils";
import { important } from "csx";
import { cssOut } from "@dashboard/compatibilityStyles";

export const loadedCSS = useThemeCache(() => {
    // Avoid FOUC in Forum side
    cssOut("body", {
        visibility: important("visible"),
    });
});
