/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { longWordEllipsis, singleBorder } from "@library/styles/styleHelpers";
import { styleUnit } from "@library/styles/styleUnit";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { calc, percent } from "csx";
import { richEditorVariables } from "@rich-editor/editor/richEditorVariables";
import { important } from "csx";
import { CSSObject } from "@emotion/css";

export const richEditorFlyoutClasses = useThemeCache(() => {
    const vars = richEditorVariables();
    const style = styleFactory("richEditorFlyout");
    const shadows = shadowHelper();
    const globalVars = globalVariables();

    const root = style(!ColorsUtils.isLightColor(globalVars.mainColors.bg) ? {} : shadows.dropDown(), {
        position: "absolute",
        left: styleUnit(0),
        width: styleUnit(vars.richEditorWidth + vars.emojiBody.padding.horizontal * 8),
        zIndex: 6,
        overflow: "hidden",
        backgroundColor: ColorsUtils.colorOut(vars.colors.bg),
        ...Mixins.border(globalVars.borderType.dropDowns),
        ...{
            "&& .ReactVirtualized__Grid": {
                width: important(styleUnit(vars.richEditorWidth + vars.emojiBody.padding.horizontal * 6) as string),
                display: "flex",
                justifyContent: "center",
            },
        },
    });

    const header = style("header", {
        position: "relative",
        borderBottom: singleBorder(),
        ...Mixins.padding(vars.emojiHeader.padding),
    });

    const title = style("title", {
        display: "flex",
        alignItems: "center",
        ...longWordEllipsis(),
        margin: 0,
        maxWidth: calc(`100% - ${styleUnit(vars.menuButton.size)}`),
        minHeight: vars.menuButton.size - vars.emojiBody.padding.horizontal,
        fontSize: percent(100),
        lineHeight: "inherit",
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
        ...{
            "&:focus": {
                outline: 0,
            },
        },
    });

    const body = style("body", {
        ...Mixins.padding(vars.emojiBody.padding),
        width: styleUnit(vars.richEditorWidth + vars.emojiBody.padding.horizontal * 8),
    });

    const footer = style("footer", {
        borderTop: singleBorder(),
    });

    return { root, header, body, footer, title };
});
