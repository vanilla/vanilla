/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { percent } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { globalVariables } from "@library/styles/globalStyleVars";
import { CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { ISpacing } from "@library/styles/cssUtilsTypes";

export const containerVariables = useThemeCache(() => {
    const vars = layoutVariables();
    const globalVars = globalVariables();
    const makeThemeVars = variableFactory("containerVariables");

    let spacing = makeThemeVars("spacing", {
        padding: globalVars.constants.fullGutter / 2,
        mobile: {
            padding: globalVars.widget.padding,
        },
    });

    const sizing = makeThemeVars("sizes", {
        full: vars.contentWidth,
        narrowContentSize: vars.contentSizes.narrow,
    });

    const colors = makeThemeVars("colors", {
        bg: globalVars.mainColors.bg,
    });

    return {
        sizing,
        colors,
        spacing,
    };
});

export const containerMainStyles = (): CSSObject => {
    const vars = containerVariables();
    return {
        display: "flex",
        flexDirection: "column",
        position: "relative",
        boxSizing: "border-box",
        width: percent(100),
        maxWidth: styleUnit(vars.sizing.full + vars.spacing.padding * 2),
        marginLeft: "auto",
        marginRight: "auto",
        ...Mixins.padding({
            horizontal: vars.spacing.padding,
        }),
        "&.isNarrow": {
            maxWidth: vars.sizing.narrowContentSize,
        },
    };
};

export function containerMainMediaQueries() {
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = containerVariables();
    return mediaQueries.oneColumnDown({
        ...Mixins.padding({
            horizontal: vars.spacing.mobile.padding,
        }),
    });
}

export const containerClasses = useThemeCache((options?: { desktopSpacing?: ISpacing; maxWidth?: number }) => {
    const style = styleFactory("container");
    const mediaQueries = layoutVariables().mediaQueries();
    const vars = containerVariables();
    const root = style(containerMainStyles(), containerMainMediaQueries());

    const fullGutter = style(
        "fullGutter",
        {
            ...containerMainStyles(),
            ...Mixins.padding({
                horizontal: vars.spacing.padding * 2,
            }),
            ...(options?.maxWidth ? { maxWidth: options.maxWidth } : {}),
        },
        options?.desktopSpacing && Mixins.padding(options.desktopSpacing),
        mediaQueries.oneColumnDown({
            ...Mixins.padding({
                horizontal: vars.spacing.mobile.padding * 2,
            }),
        }),
    );

    return { root, fullGutter };
});
