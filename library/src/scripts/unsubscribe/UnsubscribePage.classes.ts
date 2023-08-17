/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";

/**
 * Classes and styling for the unsubscribe page.
 */
export const unsubscribePageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = forumLayoutVariables().mediaQueries();

    const header = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
        flexWrap: "wrap",
    });

    const title = css({
        flex: 1,
        ...mediaQueries.oneColumnDown({
            ...Mixins.margin({ top: globalVars.gutter.size * 2 }),
        }),
    });

    const content = css({
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        "& a": {
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            fontWeight: globalVars.fonts.weights.semiBold,
        },
        ...mediaQueries.tabletDown({
            "& > p": {
                ...Mixins.margin({ vertical: globalVars.gutter.half }),
            },
        }),
    });

    const info = css({
        ...Mixins.margin({ top: 24 }),
        fontWeight: globalVars.fonts.weights.bold,
    });

    const digestInfo = css({
        ...Mixins.margin({ top: 24 }),
        fontWeight: globalVars.fonts.weights.bold,
        fontSize: globalVars.fonts.size.large,
    });

    const undoButton = css({
        ...Mixins.margin({ left: "1ch" }),
    });

    const actions = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const multipleOptions = css({
        flex: 1,
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
    });

    const checkboxGroup = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        "& label": {
            ...Mixins.padding({ vertical: 2 }),
        },
        ...mediaQueries.tabletDown({
            ...Mixins.margin({ vertical: globalVars.gutter.size }),
            "& label": {
                ...Mixins.padding({ vertical: globalVars.gutter.half }),
            },
        }),
    });

    const saveButton = css({
        minWidth: 230,
        ...Mixins.margin({ top: 16, bottom: 24 }),
        ...mediaQueries.mobileDown({
            alignSelf: "stretch",
        }),
    });

    const userInfo = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
    });

    const username = css({
        flex: 1,
        display: "flex",
        flexDirection: "column",
        alignItems: "flex-start",
        justifyContent: "center",
        ...Mixins.padding({ horizontal: 14 }),
    });

    const usernameLink = css({
        ...Mixins.clickable.itemState(),
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const manageButton = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ...mediaQueries.oneColumnDown({
            ...Mixins.margin({ vertical: globalVars.gutter.size * 2 }),
        }),
        ...mediaQueries.mobileDown({
            alignSelf: "stretch",
        }),
    });

    return {
        header,
        title,
        content,
        info,
        digestInfo,
        undoButton,
        actions,
        multipleOptions,
        checkboxGroup,
        saveButton,
        userInfo,
        username,
        usernameLink,
        manageButton,
    };
});
