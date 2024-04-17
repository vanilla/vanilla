/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { MixinsFoundation } from "@library/styles/MixinsFoundation";
import { discussionVariables } from "@dashboard/compatibilityStyles/pages/Discussion.variables";
import { cssOut } from "@dashboard/compatibilityStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { quote, translate } from "csx";
import { negativeUnit } from "@library/styles/styleHelpers";
import { userCardDiscussionPlacement } from "@dashboard/compatibilityStyles/userCards";
import { Mixins } from "@library/styles/Mixins";
import { injectGlobal } from "@emotion/css";

export const discussionCompatCSS = () => {
    const vars = discussionVariables();
    const globalVars = globalVariables();

    MixinsFoundation.contentBoxes(vars.contentBoxes, "Discussion");
    MixinsFoundation.contentBoxes(vars.panelBoxes, "Discussion", ".Panel");

    injectGlobal({
        ".Item-Header .Author .Username": {
            ...Mixins.font(vars.author.name.font),
            "&:hover, &:focus, &:active": {
                ...Mixins.font(vars.author.name.fontState),
            },
            marginRight: globalVars.gutter.half,
        },
        ".BeforeCommentHeading:empty": {
            display: "none",
        },
    });

    // Polls

    cssOut(
        `
        .Item .Poll .PollOptions .PollColor,
        .Item .Poll .PollOptions .PollColor.PollColor1,
        .Item .Poll .PollOptions .PollColor.PollColor2,
        .Item .Poll .PollOptions .PollColor.PollColor3,
        .Item .Poll .PollOptions .PollColor.PollColor4,
        .Item .Poll .PollOptions .PollColor.PollColor5,
        .Item .Poll .PollOptions .PollColor.PollColor6,
        .Item .Poll .PollOptions .PollColor.PollColor7,
        .Item .Poll .PollOptions .PollColor.PollColor8,
        .Item .Poll .PollOptions .PollColor.PollColor9,
        .Item .Poll .PollOptions .PollColor.PollColor10,
    `,
        {
            color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
            backgroundColor: ColorsUtils.colorOut(globalVars.mixPrimaryAndBg(globalVars.constants.stateColorEmphasis), {
                makeImportant: true,
            }),
            opacity: 1,
        },
    );

    cssOut(`.Container .DataTable span.MItem`, {
        display: "inline-block",
    });

    cssOut(`.Container .DataTable span.MItem a`, {
        display: "inline",
    });

    cssOut(`.MessageList .ItemDiscussion .InlineTags`, {
        margin: 0,
        padding: 0,
    });

    cssOut(
        `
        .Options a.Bookmark,
        .Options a.Bookmarking,
        .Options a.Bookmarked
        `,
        {
            opacity: 1,
            width: styleUnit(24),
            height: styleUnit(24),
            display: "block",
            position: "relative",
        },
    );

    cssOut(
        `
        .Content a.Bookmark,
        .Content a.Bookmarking,
        .Content a.Bookmarked`,
        {
            cursor: "pointer",
        },
    );

    cssOut(
        `
        .Content a.Bookmark .svgBookmark,
        .Content a.Bookmarking .svgBookmark,
        .Content a.Bookmarked .svgBookmark`,
        {
            ...Mixins.absolute.topLeft("50%", "50%"),
            content: quote(``),
            display: "block",
            width: styleUnit(12),
            height: styleUnit(16),
            fontSize: styleUnit(12),
            transform: translate(`-50%`, `-50%`),
        },
    );

    cssOut(
        `
        body.Discussions .DataList .Options,
        body.Discussions .MessageList .Options
    `,
        {
            marginTop: negativeUnit(2),
        },
    );

    cssOut("body.Discussion .MItem-Resolved", {
        width: 20,
        height: 14,
        padding: 0,
        marginBottom: 0,
        verticalAlign: "middle",
        display: "inline-flex",
    });

    userCardDiscussionPlacement();

    cssOut(".Discussion", {
        width: "100%",
    });

    cssOut(`.MessageList .Item-Header.Item-Header`, {
        ...Mixins.padding({
            all: 0,
        }),
        display: "flex",

        "& .Item-HeaderContent": {
            flex: 1,
        },

        "& .PhotoWrap": {
            marginRight: 12,
        },

        "& .AuthorWrap, & .DiscussionMeta": {
            paddingLeft: 0,
            display: "block",
        },
    });
};
