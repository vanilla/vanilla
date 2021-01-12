/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { absolutePosition, negativeUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { quote, translate } from "csx";
import { forumVariables } from "@library/forms/forumStyleVars";
import { userCardDiscussionPlacement } from "@dashboard/compatibilityStyles/userCards";
import { Mixins } from "@library/styles/Mixins";

export const discussionCSS = () => {
    const globalVars = globalVariables();
    const formVars = forumVariables();
    const userPhotoVars = formVars.userPhoto;

    cssOut(
        `
        .userContent,
        .UserContent,
        .MessageList.Discussion
        `,
        {
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
            fontSize: styleUnit(globalVars.fonts.size.medium),
        },
    );

    cssOut(
        `
        .userContent.userContent h1,
        .userContent.userContent h2,
        .userContent.userContent h3,
        .userContent.userContent h4,
        .userContent.userContent h5,
        .userContent.userContent h6
    `,
        {
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        },
    );

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

    cssOut(
        `
        .DiscussionHeader .AuthorWrap,
        .MessageList .ItemComment .AuthorWrap,
        .MessageList .ItemDiscussion .AuthorWrap,
        `,
        {
            position: "relative",
        },
    );

    cssOut(
        ".Meta.Meta-Discussion",

        {
            display: globalVars.meta.display,
        },
    );

    cssOut(
        `
        .MessageList .ItemDiscussion .Item-Header.DiscussionHeader .PhotoWrap,
        .MessageList .ItemComment .Item-Header .PhotoWrap,
        .MessageList .ItemDiscussion .Item-Header .PhotoWrap,
        .MessageList .Item-Header .userCardWrapper-photo,
        `,
        {
            position: "absolute",
            top: 2,
            left: 2,
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
            ...absolutePosition.topLeft("50%", "50%"),
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

    userCardDiscussionPlacement();

    cssOut(
        `
        .Container .MessageList .ItemComment .Item-Header,
        .Container .MessageList .ItemDiscussion .Item-Header
    `,
        Mixins.padding({
            all: globalVars.gutter.half,
            top: globalVars.gutter.size,
        }),
    );

    cssOut(
        `
        body.Discussion .ItemDiscussion .DiscussionHeader .userCardWrapper-photo,
        body.Discussion .MessageList .CommentHeader .userCardWrapper-photo,
        `,
        {
            top: 0,
            left: 0,
        },
    );
};
