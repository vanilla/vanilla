/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { colorOut, unit } from "@library/styles/styleHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/index";

export const ideaCSS = () => {
    const globalVars = globalVariables();

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

    cssOut(
        `
        .Groups .DataTable.DiscussionsTable.DiscussionsTable .ItemIdea td.DiscussionName .Wrap,
        .DataTable.DiscussionsTable.DiscussionsTable .ItemIdea td.DiscussionName .Wrap`,
        {
            paddingLeft: unit(50),
        },
    );
};
