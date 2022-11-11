/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { IThemeVariables } from "@library/theming/themeReducer";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Property } from "csstype";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { css } from "@emotion/css";

export const dataListVariables = useThemeCache((forcedVars?: IThemeVariables) => {
    const makeThemeVars = variableFactory("dataList", forcedVars);
    const globalVars = globalVariables();

    const spacing = makeThemeVars("spacing", {
        padding: {
            vertical: 6,
        },
    });

    const key = makeThemeVars("key", {
        textAlignment: "left" as Property.TextAlign,
        padding: {
            vertical: spacing.padding.vertical,
            right: globalVars.spacer.size,
        },
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("small", "bold"),
            lineHeight: globalVars.lineHeights.condensed,
        }),
    });

    const value = makeThemeVars("value", {
        textAlignment: "left" as "left" | "center",
        padding: {
            vertical: spacing.padding.vertical,
        },
    });

    return {
        spacing,
        key,
        value,
    };
});

export const dataListClasses = useThemeCache((layoutMediaQueries?: { xs: any }) => {
    const vars = dataListVariables();
    const globalVars = globalVariables();
    const mediaQueries = layoutMediaQueries ?? oneColumnVariables().mediaQueries();

    const root = css({});

    const table = css({
        "&&": {
            ...mediaQueries.xs({
                display: "inline-table",
            }),
        },
    });

    const title = css({
        // Fighting with the _profile styles
        "&&&&": {
            ...Mixins.margin({
                bottom: globalVars.spacer.headingBox,
            }),
        },
    });

    const key = css({
        textAlign: vars.key.textAlignment,
        verticalAlign: "top",
        fontWeight: globalVars.fonts.weights.bold,
        ...Mixins.padding(vars.key.padding),
    });

    const value = css({
        textAlign: vars.key.textAlignment,
        verticalAlign: "top",
        ...Mixins.padding(vars.value.padding),
    });

    const tokenGap = css({
        display: "flex",
        gap: globalVars.fonts.size.small,
    });

    const checkBoxAlignment = css({
        /**
         * This is fighting with old classNames and since this instance
         * is unique its the one that gets the !important
         */
        padding: "2px 0 0!important",
    });

    return {
        root,
        title,
        table,
        key,
        value,
        tokenGap,
        checkBoxAlignment,
    };
});
