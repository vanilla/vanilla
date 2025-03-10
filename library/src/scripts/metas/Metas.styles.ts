/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { CSSObject } from "@emotion/css/types/create-instance";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { extendItemContainer } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";

export const metaContainerStyle = (overwrites?: any) => {
    const vars = metasVariables();
    return {
        ...Mixins.font(vars.font),
        display: "block",
        ...extendItemContainer(vars.spacing.horizontal! as number),
        overflow: "initial", // We can't hide overflow or stuff like user cards will not be shown.
        textAlign: "start",
        ...overwrites,
    };
};

export const metaItemStyle = useThemeCache(() => {
    const vars = metasVariables();
    return {
        ...Mixins.font(vars.font),
        display: "inline-block",
        ...Mixins.margin(vars.spacing),
        "& > a": {
            fontSize: "inherit",
        },
        "& &": {
            margin: 0,
        },
        ".isDeleted, &.isDeleted": {
            ...Mixins.font(vars.specialFonts.deleted),
        },
        maxHeight: vars.height,
    };
});

export const metaLinkItemStyle = useThemeCache(() => {
    const vars = metasVariables();

    const styles: CSSObject = {
        ...Mixins.font(vars.linkFont),
        "&:hover, &:focus, &:active, &.focus-visible": {
            ...Mixins.font(vars.linkFontState),
        },
    };

    return styles;
});

export const metasClasses = useThemeCache(() => {
    const vars = metasVariables();
    const globalVars = globalVariables();

    const root = css({ ...metaContainerStyle() });

    const metaLink = css(metaLinkItemStyle());

    const meta = css({
        ...metaItemStyle(),
        [`.${metaLink}`]: {
            ...metaLinkItemStyle(),
        },
    });

    const metaFlexed = css({
        display: "inline-flex",
        alignItems: "center",
        gap: "0.5ch",
    });

    // Get styles of meta, without the margins
    const metaStyle = css({
        display: "inline-block",
        fontSize: styleUnit(vars.font.size),
        color: ColorsUtils.colorOut(vars.font.color),
    });

    const draftStatus = css({
        flexGrow: 1,
        textAlign: "start",
    });

    const noUnderline = css({
        textDecoration: "none !important",
    });

    const inlineBlock = css({
        display: "inline-flex",
        borderTop: "none !important",
        ...{
            "& *:hover, & *:focus, & .isFocused": {
                color: `${globalVars.links.colors.default} !important`,
                backgroundColor: "transparent !important",
            },
        },
    });

    const itemSpacing = css({
        ...Mixins.margin(vars.spacing),
    });

    const alignVerticallyInMetaItem = useThemeCache((height: number) =>
        css(Mixins.verticallyAlignInContainer(height, vars.font.lineHeight as number)),
    );

    const iconButton = css({
        ...metaLinkItemStyle(),
        maxHeight: vars.height,
        padding: 0,
    });

    const profileMeta = css({
        maxHeight: "unset",
        "& a": {
            display: "flex",
            alignItems: "center",
            gap: 6,
            ...metaLinkItemStyle(),
        },
    });

    return {
        root,
        meta,
        metaFlexed,
        itemSpacing,
        metaLink,
        metaStyle,
        draftStatus,
        noUnderline,
        inlineBlock,
        alignVerticallyInMetaItem,
        iconButton,
        profileMeta,
    };
});
