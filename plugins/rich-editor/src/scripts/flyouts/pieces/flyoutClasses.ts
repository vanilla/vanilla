/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import {
    borders,
    colorOut,
    isLightColor,
    longWordEllipsis,
    paddings,
    singleBorder,
    unit,
} from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { calc, percent } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { important } from "csx";
import { NestedCSSProperties } from "typestyle/lib/types";

export const richEditorFlyoutClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const style = styleFactory("richEditorFlyout");
    const shadows = shadowHelper();
    const globalVars = globalVariables();

    const root = style(!isLightColor(globalVars.mainColors.bg) ? {} : shadows.dropDown(), {
        position: "absolute",
        left: unit(0),
        width: unit(vars.richEditorWidth + vars.emojiBody.padding.horizontal * 2),
        zIndex: 6,
        overflow: "hidden",
        backgroundColor: colorOut(vars.colors.bg),
        ...borders(),

        $nest: {
            "&& .ReactVirtualized__Grid": {
                width: important(unit(vars.richEditorWidth) as string),
            },
        },
    } as NestedCSSProperties);

    const header = style("header", {
        position: "relative",
        borderBottom: singleBorder(),
        ...paddings(vars.emojiHeader.padding),
    });

    const title = style("title", {
        display: "flex",
        alignItems: "center",
        ...longWordEllipsis(),
        margin: 0,
        maxWidth: calc(`100% - ${unit(vars.menuButton.size)}`),
        minHeight: vars.menuButton.size - vars.emojiBody.padding.horizontal,
        fontSize: percent(100),
        lineHeight: "inherit",
        color: colorOut(globalVars.mainColors.fg),
        $nest: {
            "&:focus": {
                outline: 0,
            },
        },
    });

    const body = style("body", {
        ...paddings(vars.emojiBody.padding),
        width: unit(vars.richEditorWidth + vars.emojiBody.padding.horizontal * 2),
    });

    const footer = style("footer", {
        borderTop: singleBorder(),
    });

    return { root, header, body, footer, title };
});
