/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { translateX } from "csx";
import { css } from "@emotion/css";
import FloatingToolbarVariables from "@library/editor/toolbars/FloatingToolbar.variables";

type NubPosition = "above" | "below";

export const inlineToolbarClasses = useThemeCache(() => {
    const above = css({
        transform: `translateY(-12px)`,
    });

    const below = css({
        transform: `translateY(12px)`,
    });
    return { above, below };
});

export const nubClasses = useThemeCache((nubPosition?: NubPosition) => {
    nubPosition = nubPosition ?? "above";

    const { overlay } = globalVariables();

    const { menu, colors, nub } = FloatingToolbarVariables();

    const offsetForNub = menu.offset / 2;

    const root = css({
        position: "relative",
        display: "block",
        width: styleUnit(nub.width),
        height: styleUnit(nub.width),
        borderTop: singleBorder({
            width: menu.borderWidth,
        }),
        borderRight: singleBorder({
            width: menu.borderWidth,
        }),
        boxShadow: overlay.dropShadow,
        background: ColorsUtils.colorOut(colors.bg),
        ...(nubPosition === "above"
            ? {
                  transform: `translateY(-50%) rotate(135deg)`,
                  marginBottom: styleUnit(offsetForNub),
              }
            : {
                  transform: `translateY(50%) rotate(-45deg)`,
                  marginTop: styleUnit(offsetForNub),
                  boxShadow: "none",
              }),
    });

    const position = css({
        position: "absolute",

        display: "flex",
        alignItems: "flex-start",
        justifyContent: "center",
        overflow: "hidden",
        width: styleUnit(nub.width * 2),
        height: styleUnit(nub.width * 2),
        ...userSelect(),
        pointerEvents: "none",
        ...(nubPosition === "above"
            ? {
                  bottom: 0,
                  zIndex: 10,
              }
            : {
                  bottom: "100%",
              }),
    });

    return { root, position };
});
