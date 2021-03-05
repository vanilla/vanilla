/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/metas/Metas.variables";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { allLinkStates, defaultTransition, extendItemContainer, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { calc, important, percent } from "csx";
import { tagVariables } from "@library/metas/Tag.variables";
import { CSSObject } from "@emotion/css";

export const metaContainerStyle = (overwrites?: any) => {
    const vars = metasVariables();
    return {
        ...Mixins.font(vars.font),
        display: "block",
        ...extendItemContainer(vars.spacing.horizontal! as number),
        overflow: "initial", // We can't hide overflow or stuff like user cards will not be shown.
        textAlign: "left",
        ...overwrites,
    };
};

export const metaItemStyle = useThemeCache(() => {
    const vars = metasVariables();
    const tagVars = tagVariables();

    return {
        ...Mixins.font(vars.font),
        display: "inline-block",
        ...Mixins.margin(vars.spacing),
        "& > a": metaLinkItemStyle(),
        "& &": {
            margin: 0,
        },
        ".isDeleted, &.isDeleted": {
            ...Mixins.font(vars.specialFonts.deleted),
        },
        ...Mixins.padding({
            vertical: tagVars.border.width,
        }),
    };
});

export const metaLinkItemStyle = useThemeCache(() => {
    const vars = metasVariables();

    const flexed: CSSObject = { display: "flex", flexWrap: "wrap", justifyContent: "flex-start", alignItems: "center" };

    const styles: CSSObject = {
        ...Mixins.font(vars.linkFont),
        "&:hover, &:focus, &:active, &.focus-visible": {
            ...Mixins.font(vars.linkFontState),
        },
        "&.isFlexed": flexed,
    };

    return styles;
});

export const metaLabelStyle = useThemeCache(() => {
    const tagVars = tagVariables();

    const style: CSSObject = {
        display: "inline-block",
        maxWidth: percent(100),
        whiteSpace: "normal",
        textOverflow: "ellipsis",
        ...userSelect(),
        ...Mixins.padding(tagVars.padding),
        ...Mixins.border(tagVars.border),
        ...Mixins.font(tagVars.font),
        ...defaultTransition("border"),
        ...Mixins.margin(tagVars.margin),
        ...Mixins.background(tagVars.background),
    };

    return style;
});

export const metasClasses = useThemeCache(() => {
    const vars = metasVariables();
    const globalVars = globalVariables();
    const style = styleFactory("metas");

    const root = style(metaContainerStyle());
    const meta = style("meta", metaItemStyle());
    const metaLink = style("metaLink", { ...metaItemStyle(), fontWeight: globalVars.fonts.weights.semiBold });

    const metaIcon = style("metaIcon", {
        ...metaItemStyle(),
        maxHeight: 14,
        ...{
            "& svg": {
                display: "inline-block",
                marginBottom: -6,
            },
        },
    });

    const metaLabel = style("metaLabel", metaLabelStyle());

    // Get styles of meta, without the margins
    const metaStyle = style("metaStyles", {
        display: "inline-block",
        fontSize: styleUnit(vars.font.size),
        color: ColorsUtils.colorOut(vars.font.color),
    });

    const draftStatus = style("draftStatus", {
        flexGrow: 1,
        textAlign: "left",
    });

    const noUnderline = style("noUnderline", {
        textDecoration: important("none"),
    });

    const inlineBlock = style("inlineBlock", {
        display: "inline-flex",
        borderTop: "none !important",
        ...{
            "& *:hover, & *:focus, & .isFocused": {
                color: `${globalVars.links.colors.default} !important`,
                backgroundColor: "transparent !important",
            },
        },
    });

    return {
        root,
        meta,
        metaLabel,
        metaLink,
        metaIcon,
        metaStyle,
        draftStatus,
        noUnderline,
        inlineBlock,
    };
});
