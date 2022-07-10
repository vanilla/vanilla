/**
 * Compatibility styles, using the color variables.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { inputVariables } from "@library/forms/inputStyles";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { cssOut } from "@dashboard/compatibilityStyles/cssOut";
import { variableFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { siteNavVariables } from "@library/navigation/siteNavStyles";
import { panelListVariables } from "@library/layout/panelListStyles";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";

export const forumFontsVariables = useThemeCache(() => {
    const makeThemeVars = variableFactory("forumFonts");
    const globalVars = globalVariables();
    const fonts = makeThemeVars("fonts", {
        sizes: {
            title: globalVars.fonts.size.large,
            base: globalVars.fonts.size.medium,

            // large: 16,
            // medium: 14,
            // small: 12,
            // largeTitle: 32,
            // title: 22,
            // subTitle: 18,
        },
    });

    const navVars = siteNavVariables();
    const panelLink = makeThemeVars("panelLink", {
        padding: {
            all: navVars.node.padding + navVars.node.borderWidth,
        },
        spacer: {
            default: panelListVariables().offset.default,
        },
    });

    const panelTitle = makeThemeVars("panelTitle", {
        margins: Variables.spacing({
            bottom: navVars.spacer.default,
        }),
    });

    return { fonts, panelLink, panelTitle };
});

export const fontCSS = () => {
    const globalVars = globalVariables();
    const vars = forumFontsVariables();

    cssOut(
        `
        .Content .Title,
        .Content .Title a
    `,
        {
            ...forumTitleMixin(),
        },
    );

    // Categories, top level
    cssOut(
        `
        body.Section-EditProfile .Box .PanelCategories li.Heading,
        .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Heading,
        .BoxCategories.BoxCategories .PanelCategories li.Heading
    `,
        {
            ...Mixins.font({
                size: vars.fonts.sizes.title,
                weight: globalVars.fonts.weights.semiBold,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
    );

    cssOut(
        `
        body.Section-EditProfile .Box .PanelCategories li.Depth2 a,
        .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Depth2 a,
        .BoxCategories.BoxCategories .PanelCategories li.Depth2 a,
    `,
        {
            ...Mixins.font({
                size: vars.fonts.sizes.base,
                weight: globalVars.fonts.weights.semiBold,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
    );

    cssOut(
        `
        .Panel .Box a:not(.Button):not(.Tag),
        .Panel .BoxFilter a:not(.Button):not(.Tag),
        body.Section-EditProfile .Box .PanelCategories li.Depth3 a,
        body.Section-EditProfile .Box .PanelCategories li.Depth4 a,
        body.Section-EditProfile .Box .PanelCategories li.Depth5 a,
        .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Depth3 a,
        .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Depth4 a,
        .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Depth5 a,
        .BoxCategories.BoxCategories .PanelCategories li.Depth3 a,
        .BoxCategories.BoxCategories .PanelCategories li.Depth4 a,
        .BoxCategories.BoxCategories .PanelCategories li.Depth5 a
    `,
        {
            ...Mixins.font({
                size: vars.fonts.sizes.base,
                weight: globalVars.fonts.weights.normal,
                lineHeight: globalVars.lineHeights.condensed,
            }),
        },
    );

    for (let i = 1; i <= 12; i++) {
        const offset = styleUnit((i - 1) * vars.panelLink.spacer.default);
        // Links
        cssOut(
            `
            body.Section-EditProfile .Box .PanelCategories li.Depth${i} a.ItemLink,
            .BoxFilter:not(.BoxBestOfFilter) .PanelCategories li.Depth${i} a.ItemLink,
            .BoxCategories.BoxCategories .PanelCategories li.Depth${i} a.ItemLink,
            .Panel.Panel-main .Box .Heading[aria-level='${i}'],
        `,
            {
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars(styleUnit(i === 1 ? "medium" : "small")),
                    color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                }),
                paddingLeft: offset,
            },
        );

        // Headings
        cssOut(
            `
            .Panel.Panel-main .Box .Heading.Heading[aria-level='${i}'],
        `,
            {
                ...Mixins.font({
                    ...(i === 1
                        ? globalVars.fontSizeAndWeightVars("large")
                        : globalVars.fontSizeAndWeightVars("small")),
                }),
                paddingLeft: offset,
            },
        );
    }
};

export const forumTitleMixin = () => {
    const vars = forumFontsVariables();
    return {
        ...Mixins.font({
            size: vars.fonts.sizes.title,
        }),
        textDecoration: "none",
    };
};
