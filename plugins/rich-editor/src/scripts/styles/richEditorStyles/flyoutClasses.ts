/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { richEditorVariables } from "@rich-editor/styles/richEditorStyles/richEditorVariables";
import { borders, longWordEllipsis, paddings, toStringColor, unit } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { calc, percent } from "csx";
import styleFactory from "@library/styles/styleFactory";
import memoize from "lodash/memoize";

export const richEditorFlyoutClasses = memoize(() => {
    const vars = richEditorVariables();
    const style = styleFactory("richEditorFlyout");
    const shadows = shadowHelper();
    const globalVars = globalVariables();

    const root = style({
        ...shadows.dropDown(),
        position: "absolute",
        left: 0,
        width: unit(vars.flyout.padding.left + vars.flyout.padding.right + 7 * vars.menuButton.size),
        zIndex: 6,
        overflow: "hidden",
        backgroundColor: toStringColor(vars.colors.bg),
        ...borders(),
    });

    const header = style("header", {
        position: "relative",
        ...paddings({
            top: unit(vars.flyout.padding.top / 2),
            right: unit(vars.flyout.padding.right),
            bottom: unit(vars.flyout.padding.bottom / 2),
            left: unit(vars.flyout.padding.left),
        }),
    });

    const title = style("title", {
        ...longWordEllipsis(),
        margin: 0,
        maxWidth: calc(`100% - ${unit(vars.menuButton.size)}`),
        minHeight: vars.menuButton.size - vars.flyout.padding.top,
        fontSize: percent(100),
        lineHeight: "inherit",
        color: toStringColor(globalVars.mainColors.fg),
        $nest: {
            "&:focus": {
                outline: 0,
            },
        },
    });

    const body = style("body", {
        paddingLeft: unit(vars.flyout.padding.left),
        paddingRight: unit(vars.flyout.padding.right),
    });

    const footer = style("footer", {
        ...paddings({
            top: unit(vars.flyout.padding.top),
            right: unit(vars.flyout.padding.right),
            bottom: unit(vars.flyout.padding.bottom),
            left: unit(vars.flyout.padding.left),
        }),
        $nest: {
            "&.insertEmoji-footer": {
                padding: 0,
            },
        },
    });

    return { root, header, body, footer, title };
});
