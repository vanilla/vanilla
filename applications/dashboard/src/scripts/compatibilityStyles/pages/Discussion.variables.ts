/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { metasVariables } from "@library/metas/Metas.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

/**
 * @varGroup discussion
 * @description Variables affecting a discussion page.
 * This is generally a single discussion and the comments responding to it.
 */
export const discussionVariables = useThemeCache(() => {
    const makeVars = variableFactory("discussion");
    const globalVars = globalVariables();
    const metaVars = metasVariables();

    const author = makeVars("author", {
        name: {
            font: Variables.font({
                color: globalVars.mainColors.fg,
                size: metaVars.linkFont.size,
                weight: globalVars.fonts.weights.bold,
                textDecoration: "none",
            }),
            fontState: Variables.font({
                color: globalVars.mainColors.primary,
            }),
        },
    });

    /**
     * @varGroup discussion.contentBoxes
     * @description Content boxes for the discussion page (or comment list).
     * @expand contentBoxes
     */
    const contentBoxes = makeVars("contentBoxes", Variables.contentBoxes(globalVariables().contentBoxes));

    const panelBoxes = makeVars("panelBoxes", Variables.contentBoxes(globalVariables().panelBoxes));

    return { contentBoxes, panelBoxes, author };
});
