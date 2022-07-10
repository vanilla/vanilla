/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory, variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { em } from "csx";
import { singleLineEllipsis, userSelect, ensureColorHelper } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { LinkDecorationType } from "@library/styles/cssUtilsTypes";

export const breadcrumbsVariables = useThemeCache(() => {
    const globalVars = globalVariables();

    /**
     * @varGroup breadcrumbs
     * @description Variables to style breadcrumbs
     */
    const makeThemeVars = variableFactory("breadcrumbs");

    /**
     * @varGroup breadcrumbs.sizing
     * @description Set the size of the breadcrumbs
     */
    const sizing = makeThemeVars("sizing", {
        /**
         * @var breadcrumbs.sizing.minHeight
         * @type string | number
         */
        minHeight: 16,
    });

    const link = makeThemeVars("link", {
        /**
         * @varGroup breadcrumbs.link.font
         * @description Font variables for links (scumb labels)
         * @expand font
         */
        font: Variables.font({
            ...globalVars.fontSizeAndWeightVars("small"),
            color: ensureColorHelper(globalVars.links.colors.default),
            lineHeight: globalVars.lineHeights.condensed,
            transform: "uppercase",
            textDecoration: globalVars.links.linkDecorationType === LinkDecorationType.ALWAYS ? "underline" : "none",
        }),
    });
    const separator = makeThemeVars("separator", {
        /**
         * @var breadcrumbs.separator.spacing
         * @description Controls the spacing between the breadcrumb labels
         * @type number | string
         */
        spacing: em(0.2),
        /**
         * @varGroup breadcrumbs.separator.font
         * @description Font variables for the separator (crumb) element
         * @expand font
         */
        font: Variables.font({}),
    });

    return { sizing, link, separator };
});

export const breadcrumbsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = breadcrumbsVariables();
    const style = styleFactory("breadcrumbs");
    const linkColors = Mixins.clickable.itemState();
    const link = style("link", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        ...Mixins.font(vars.link.font),
        ...linkColors,
    });

    const root = style({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-start",
        flexWrap: "wrap",
    });

    const crumb = style("crumb", {
        ...singleLineEllipsis(),
        display: "inline-flex",
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
            lineHeight: globalVars.lineHeights.condensed,
        }),
        overflow: "hidden",
    });

    const list = style("list", {
        display: "flex",
        alignItems: "center",
        flexDirection: "row",
        justifyContent: "flex-start",
        flexWrap: "wrap",
        minHeight: styleUnit(vars.sizing.minHeight),
    });

    const separator = style("separator", {
        ...userSelect(),
        marginTop: 0,
        marginBottom: 0,
        marginLeft: vars.separator.spacing,
        marginRight: vars.separator.spacing,
        ...Mixins.font(vars.separator.font),
    });

    const separatorIcon = style("separatorIcon", {
        display: "inline-flex",
        justifyContent: "center",
        alignItems: "center",
        fontFamily: "arial, sans-serif !important",
        width: `1ex`,
        opacity: 0.5,
        position: "relative",
        color: "inherit",
    });

    const breadcrumb = style("breadcrumb", {
        display: "inline-flex",
        lineHeight: 1,
        minHeight: styleUnit(vars.sizing.minHeight),
    });

    const current = style("current", {
        color: "inherit",
        opacity: 1,
    });

    return {
        root,
        list,
        separator,
        separatorIcon,
        breadcrumb,
        link,
        current,
        crumb,
    };
});
