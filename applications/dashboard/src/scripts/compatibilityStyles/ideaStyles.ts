/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { forumVariables } from "@library/forms/forumStyleVars";

export const ideaCSS = () => {
    const globalVars = globalVariables();
    const forumVars = forumVariables();

    cssOut(
        `
        .idea-counter-module .idea-counter-box
        `,
        {
            backgroundColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.1)),
        },
    );

    cssOut(
        `
        .idea-counter-module .arrow::before,
        .idea-counter-module .arrow::after
    `,
        {
            borderColor: ColorsUtils.colorOut(globalVars.mixBgAndFg(0.75)),
        },
    );

    cssOut(
        `
        .idea-counter-module .uservote .arrow::before,
        .idea-counter-module .uservote .arrow::after
    `,
        {
            borderColor: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(0.2)),
        },
    );

    cssOut(`.idea-counter-module .score`, {
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
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
            width: styleUnit(forumVars.countBox.width),
            height: styleUnit(forumVars.countBox.height),
        },
    );
};
