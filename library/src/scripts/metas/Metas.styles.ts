/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { CSSObject } from "@emotion/css";
import { iconVariables } from "@library/icons/iconStyles";
import { metasVariables } from "@library/metas/Metas.variables";
import { tagVariables } from "@library/metas/Tag.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { colorOut, defaultTransition, extendItemContainer, userSelect } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

import { important, percent } from "csx";

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
    const globalVars = globalVariables();

    const flexed: CSSObject = { display: "flex", flexWrap: "wrap", justifyContent: "flex-start", alignItems: "center" };

    const styles: CSSObject = {
        ...Mixins.font(vars.linkFont),
        "&:hover, &:focus, &:active, &.focus-visible": {
            ...Mixins.font(vars.linkFontState),
        },
        "&.isFlexed": flexed,
        "&.usernameAsMetaTitle": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium"),
                color: colorOut(globalVars.mainColors.fg),
            }),
        },
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

    const tagVars = tagVariables();

    const {
        standard: { height: iconHeight },
    } = iconVariables();

    const metaItemHeight = Math.round(
        (vars.font.size as number) * (vars.font.lineHeight! as number) + (tagVars.border.width! as number) * 2,
    );

    const metaIcon = style("metaIcon", {
        ...metaItemStyle(),
        maxHeight: metaItemHeight,
        ...{
            "& svg": {
                transform: `translateY(-${Math.abs(metaItemHeight - iconHeight)}px)`,
                verticalAlign: "text-top",
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
