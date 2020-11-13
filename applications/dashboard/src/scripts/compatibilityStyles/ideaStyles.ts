/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";
import { forumVariables } from "@library/forms/forumStyleVars";

export const ideaCSS = () => {
    const globalVars = globalVariables();
    const forumVars = forumVariables();

    cssOut(
        `
        .idea-counter-module .idea-counter-box
        `,
        {
            backgroundColor: colorOut(globalVars.mixBgAndFg(0.1)),
        },
    );

    cssOut(
        `
        .idea-counter-module .arrow::before,
        .idea-counter-module .arrow::after
    `,
        {
            borderColor: colorOut(globalVars.mixBgAndFg(0.75)),
        },
    );

    cssOut(
        `
        .idea-counter-module .uservote .arrow::before,
        .idea-counter-module .uservote .arrow::after
    `,
        {
            borderColor: colorOut(globalVars.mixPrimaryAndBg(0.2)),
        },
    );

    cssOut(`.idea-counter-module .score`, {
        color: colorOut(globalVars.mainColors.fg),
    });

    cssOut(`.idea-counter-module`, {
        float: "none",
        margin: 0,
    });

    cssOut(
        `
        .DataList .ItemIdea.ItemIdea.ItemIdea .idea-counter-module .idea-counter-box,
        .DataList .ItemIdea.ItemIdea.ItemIdea .PhotoWrap.IndexPhoto,
        .MessageList .ItemIdea.ItemIdea.ItemIdea .idea-counter-module .idea-counter-box,
        .MessageList .ItemIdea.ItemIdea.ItemIdea .PhotoWrap.IndexPhoto
    `,
        {
            width: unit(forumVars.countBox.width),
            height: unit(forumVars.countBox.height),
        },
    );
};
