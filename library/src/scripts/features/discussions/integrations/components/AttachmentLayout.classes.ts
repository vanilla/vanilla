/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/metas/Metas.variables";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { singleBorder } from "@library/styles/styleHelpersBorders";

const wrap = true;

const AttachmentLayoutClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({
        ...Mixins.border({
            color: globalVars.mainColors.primary,
            width: globalVars.border.width,
            radius: globalVars.border.radius,
            style: "solid",
        }),
        display: "flex",

        lineHeight: globalVars.lineHeights.base,

        ...Mixins.margin({
            bottom: globalVars.gutter.size,
        }),

        ...(wrap && {
            flexWrap: "wrap",
        }),
    });

    const logoSection = css({
        ...Mixins.background({
            color: globalVars.mainColors.primary,
        }),

        ...Mixins.font({
            color: globalVars.mainColors.primaryContrast,
        }),
        display: "flex",
        flexDirection: "column",
        alignItems: "center",

        ...(wrap && {
            flex: "1 0 auto",
        }),
    });

    const logoWrapper = css({
        ...Mixins.padding({
            horizontal: 30,
            vertical: 20,
        }),
        "& svg": {
            width: 60,
            height: 60,
        },
    });

    const textSection = css({
        ...(wrap && {
            flex: "1000 1 300px",
        }),

        ...Mixins.padding({
            top: globalVars.spacer.componentInner,
            left: globalVars.spacer.componentInner,
            right: globalVars.spacer.componentInner,
        }),
    });

    const header = css({
        borderBottom: singleBorder(),
        paddingBottom: globalVars.gutter.quarter,
        display: "flex",
        justifyContent: "space-between",
        flexWrap: "wrap",
    });

    const titleAndNoticeAndMetasWrapper = css({
        display: "flex",
        flexWrap: "wrap",
        flexGrow: 1,
    });

    const positionNoticeInCorner = css({
        justifyContent: "space-between",
    });

    const title = css({
        // Extra specificity for legacy page.
        "&&": {
            ...Mixins.margin({
                right: globalVars.gutter.half,
                bottom: 2,
            }),
            display: "inline-flex",
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("large"),
            }),
        },
    });

    const inlineMetas = css({
        display: "inline-flex",
        flexBasis: "content",
    });

    const metasRow = css({
        display: "flex",
        flexBasis: "100%",
    });

    const metasVars = metasVariables();

    const externalLinkWrapper = css({
        ...extendItemContainer(metasVars.spacing.horizontal! as number),
        marginRight: -metasVars.spacing.horizontal! as number,
        flexBasis: "fit-content",
    });

    const externalLink = css({
        display: "flex",
        height: "1lh",
        ...Mixins.clickable.itemState(),
    });

    const externalIcon = css({
        ...Mixins.verticallyAlignInContainer(24, globalVars.lineHeights.base),
    });

    const notice = css({
        textTransform: "uppercase",
        textAlign: "center",
    });

    const detailItem = css({
        ...Mixins.margin(metasVars.spacing),
    });

    const detailLink = css({
        display: "flex",
        ...Mixins.clickable.itemState(),
    });

    const details = css({
        ...Mixins.padding({
            top: globalVars.gutter.quarter * 3,
            bottom: globalVars.gutter.size,
        }),

        columnGap: globalVars.gutter.size,
        columnCount: 2,
        columnWidth: 220,

        [`> .${detailItem}`]: {
            display: "inline-block",
            width: "100%",
            ...Mixins.margin({
                all: "0",
                bottom: globalVars.gutter.half,
            }),
        },
    });

    const detailLabel = css({
        ...Mixins.font({
            ...metasVars.font,
        }),
    });

    const detailValue = css({});

    return {
        root,
        logoSection,
        logoWrapper,
        textSection,
        header,
        titleAndNoticeAndMetasWrapper,
        positionNoticeInCorner,
        title,
        inlineMetas,
        metasRow,
        notice,
        externalLinkWrapper,
        externalLink,
        externalIcon,
        details,
        detailItem,
        detailLabel,
        detailLink,
        detailValue,
    };
});

export default AttachmentLayoutClasses;
