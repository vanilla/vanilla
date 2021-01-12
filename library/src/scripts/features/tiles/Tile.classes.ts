/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { absolutePosition, defaultTransition, userSelect } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { shadowHelper, shadowOrBorderBasedOnLightness } from "@library/styles/shadowHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";
import { TileAlignment } from "@library/features/tiles/TileAlignment";
import { tileVariables } from "./Tile.variables";

export const tileClasses = useThemeCache(() => {
    const vars = tileVariables();
    const globalVars = globalVariables();
    const style = styleFactory("tile");
    const shadow = shadowHelper();

    const root = style({
        display: "flex",
        flexDirection: "column",
        alignItems: "stretch",
        width: percent(100),
        flexGrow: 1,
        margin: "auto",
        ...userSelect(),
    });

    const link = useThemeCache((columns?: number) => {
        let minHeight;

        switch (columns) {
            case 2:
                minHeight = vars.link.twoColumnsMinHeight;
                break;
            case 3:
                minHeight = vars.link.threeColumnsMinHeight;
                break;
            case 4:
                minHeight = vars.link.fourColumnsMinHeight;
                break;
            default:
                minHeight = 0;
        }

        return style("link", {
            ...defaultTransition("box-shadow", "border"),
            ...Mixins.padding(vars.link.padding),
            display: "block",
            position: "relative",
            cursor: "pointer",
            flexGrow: 1,
            color: ColorsUtils.colorOut(globalVars.mainColors.fg),
            backgroundColor: ColorsUtils.colorOut(vars.link.bg),
            background: ColorsUtils.colorOut(vars.link.bgImage),
            borderRadius: styleUnit(vars.link.borderRadius),
            minHeight: styleUnit(minHeight ?? 0),
            ...shadowOrBorderBasedOnLightness(
                globalVars.body.backgroundImage.color,
                Mixins.border({
                    radius: vars.link.borderRadius, // We need to redeclare border radius here so it doesn't take default
                }),
                shadow.embed(),
            ),
            textDecoration: "none",
            boxSizing: "border-box",
            ...defaultTransition("background", "backgroundColor", "box-shadow"),
            ...{
                "&:hover": {
                    backgroundColor: ColorsUtils.colorOut(vars.link.bgHover),
                    background: ColorsUtils.colorOut(vars.link.bgImageHover),
                    textDecoration: "none",
                    ...shadowOrBorderBasedOnLightness(
                        globalVars.body.backgroundImage.color,
                        Mixins.border({
                            color: ColorsUtils.offsetLightness(globalVars.border.color, -0.05),
                            radius: vars.link.borderRadius, // We need to redeclare border radius here so it doesn't take default
                        }),
                        shadow.embedHover(),
                    ),
                },
            },
        });
    });

    const main = style("main", {
        position: "relative",
    });

    const { height, width } = vars.frame;
    const frame = style("iconFrame", {
        display: "flex",
        alignItems: "center",
        justifyContent: vars.options.alignment,
        position: "relative",
        height: styleUnit(height),
        width: styleUnit(width),
        marginTop: "auto",
        marginRight: "auto",
        marginLeft: vars.options.alignment === TileAlignment.CENTER ? "auto" : undefined,
        marginBottom: styleUnit(vars.frame.marginBottom),
    });

    const icon = style("icon", {
        display: "block",
        position: "absolute",
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
        margin: vars.options.alignment === TileAlignment.CENTER ? "auto" : undefined,
        height: "auto",
        maxWidth: percent(100),
        maxHeight: percent(100),
    });

    const title = style("title", {
        ...Mixins.font(vars.title.font),
        textAlign: vars.options.alignment,
        marginBottom: styleUnit(vars.title.marginBottom),
    });

    const description = style("description", {
        position: "relative",
        marginTop: styleUnit(vars.description.marginTop),
        fontSize: styleUnit(vars.description.fontSize),
        lineHeight: vars.description.lineHeight,
        textAlign: vars.options.alignment,
    });

    const fallBackIcon = style(
        "fallbackIcon",
        {
            width: styleUnit(vars.fallBackIcon.width),
            height: styleUnit(vars.fallBackIcon.height),
            color: vars.fallBackIcon.fg.toString(),
        },
        vars.options.alignment === TileAlignment.CENTER ? absolutePosition.middleOfParent() : {},
    );

    return {
        root,
        link,
        frame,
        icon,
        main,
        title,
        description,
        fallBackIcon,
    };
});
