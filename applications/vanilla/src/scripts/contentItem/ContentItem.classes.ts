/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";
import { getMeta } from "@library/utility/appUtils";

const ContentItemClasses = useThemeCache((headerHasUserPhoto = false) => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();
    const userPhotoVars = userPhotoVariables();

    const threadItemContainer = css({
        position: "relative",
        container: "threadItemContainer / inline-size",
    });

    const userContent = css({
        ...Mixins.padding({
            top: headerHasUserPhoto ? 12 : 2,
        }),
    });

    const resultWrapper = css({ display: "flex", gap: 12 });

    const attachmentsContentWrapper = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        "&:empty": {
            display: "none",
        },
    });

    const footerWrapper = css({
        display: "flex",
        width: "100%",
        alignItems: "center",
        gap: 16,
        "@container threadItemContainer (width < 516px)": {
            display: "grid",
            gridTemplateColumns: "1fr 50px",
            gridTemplateRows: "repeat(2, auto)",
        },

        marginBlockStart: 12,
        marginInlineEnd: 10,
    });

    const replyButton = css({
        alignSelf: "end",
        justifySelf: "end",
        "@container threadItemContainer (width < 516px)": {
            gridColumn: "2 /3",
            gridRow: "2 / 3",
        },
    });

    const authorBadgesMeta = css({
        "& > div": {
            width: 22,
            height: 22,
            marginTop: -3,
            marginRight: 2,
            "& img": {
                width: 22,
                height: 22,
            },
        },
    });

    const ignoredUserPostHeader = (postContentExpanded?: boolean) =>
        css({
            display: "flex",
            alignItems: "center",
            gap: 2,
            ...(postContentExpanded && { marginBottom: 8 }),

            "& span": {
                fontStyle: "italic",
            },
        });

    const aboveMainContent = css({
        paddingTop: 12,
    });

    const postWarningModal = css({
        "& a": {
            color: ColorsUtils.colorOut(globalVariables().links.colors.default),
        },
    });

    const postWarningTopSpace = (gap: number = 4) =>
        css({
            paddingTop: gap,
        });

    const postWarningBottomSpace = (gap: number = 4) =>
        css({
            paddingBottom: gap,
        });

    const postWarningBold = css({
        fontWeight: 600,
    });

    const postWarningFlex = css({
        display: "flex",
        alignItems: "center",
    });

    const signatureMaxHeight = getMeta("signatures.imageMaxHeight", 0);

    const signature = css({
        marginTop: 16,
        position: "relative",
        "&&:before": {
            content: "''",
            display: "block",
            marginBottom: 16,
            width: 100,
            background: ColorsUtils.colorOut(globalVars.border.color),
            height: 3,
            borderTop: singleBorder(),
        },
        ...(signatureMaxHeight > 0
            ? {
                  "& .embedImage-img.embedImage-img.embedImage-img.embedImage-img": {
                      maxHeight: signatureMaxHeight,
                      width: "auto",
                  },
              }
            : undefined),
    });

    const actionsRoot = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        gap: 16,

        "@container threadItemContainer (width < 500px)": {
            columnGap: 16,
            rowGap: 8,
        },
    });

    const actionItemsContainer = css({
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        alignItems: "center",
        gap: globalVars.gutter.size,

        "@container threadItemContainer (width < 516px)": {
            gridColumn: "1/2",
            gridRow: "2/3",
            justifySelf: "start",
            alignSelf: "end",
        },
    });

    const reactionItemsContainer = css({
        "@container threadItemContainer (width < 516px)": {
            gridColumn: "1/3",
            gridRow: "1/2",
        },
    });

    const actionItem = css({
        display: "inline-flex",
        flexDirection: "row",
        alignItems: "center",
        "&:empty": {
            display: "none",
        },
    });

    const quoteButton = css({
        alignSelf: "center",
    });

    const actionButton = css({
        ...Mixins.font(metasVars.font),
        display: "inline-flex",
        alignItems: "center",
        gap: 2,
        minHeight: 24,
        "@media (max-width : 516px)": {
            fontSize: globalVars.fontSizeAndWeightVars("medium").size,
            fontWeight: globalVars.fontSizeAndWeightVars("medium").weight,
        },
    });

    const copyLinkButton = css({
        marginLeft: 0,
    });

    const photoSize = userPhotoVars.sizing.medium;

    const headerRoot = css({
        display: "flex",
        flexWrap: "nowrap",
        alignItems: "center",
        justifyContent: "space-between",
        width: "100%",
        minHeight: photoSize,
    });

    const headerMain = css({
        display: "flex",
        flexDirection: "column",
        flexWrap: "nowrap",
        alignItems: "flex-start",
        justifyContent: "space-between",
        width: "100%",
        paddingLeft: 8,
        minHeight: photoSize,
    });

    const userName = css({
        "&&&": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
    });

    const rankLabel = css({
        ...Mixins.padding({
            horizontal: 10,
        }),
        ...Mixins.font({
            color: globalVars.mainColors.primary,
            size: 10,
            lineHeight: 15 / 10,
            transform: "uppercase",
        }),
        ...Mixins.border({
            color: globalVars.mainColors.primary,
            radius: 3,
        }),

        // Without these it won't align in a meta-row (and will actually push things below down by a few pixels).
        display: "inline",
        verticalAlign: "middle",

        // Looks slightly offset without this.
        position: "relative",
        top: "-1px",
    });

    const headerMeta = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "start",
        justifyContent: "start",
        gap: 4,

        "& > div": {
            margin: "inherit",
        },

        "& svg": {
            color: "currentColor",
        },

        "@container threadItemContainer (width < 500px)": {
            gap: 12,
        },
    });

    return {
        threadItemContainer,
        userContent,
        resultWrapper,
        attachmentsContentWrapper,
        replyButton,
        footerWrapper,
        signature,
        authorBadgesMeta,
        aboveMainContent,
        postWarningModal,
        postWarningTopSpace,
        postWarningBottomSpace,
        postWarningBold,
        postWarningFlex,
        ignoredUserPostHeader,
        actionsRoot,
        reactionItemsContainer,
        actionItemsContainer,
        actionItem,
        quoteButton,
        actionButton,
        copyLinkButton,
        headerRoot,
        rankLabel,
        userName,
        headerMeta,
        headerMain,
    };
});

export default ContentItemClasses;
