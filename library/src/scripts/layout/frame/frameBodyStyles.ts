/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { importantUnit } from "@library/styles/styleHelpers";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { styleUnit } from "@library/styles/styleUnit";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { percent } from "csx";
import { Mixins } from "@library/styles/Mixins";

export const frameBodyClasses = useThemeCache(() => {
    const vars = frameVariables();
    const globalVars = globalVariables();
    const style = styleFactory("frameBody");
    const classesInputBlock = inputBlockClasses();

    const root = style({
        position: "relative",
        width: percent(100),
        ...Mixins.padding({
            horizontal: vars.spacing.padding,
        }),
        ...{
            "&.isSelfPadded": {
                ...Mixins.padding({
                    left: 0,
                    right: 0,
                }),
            },
            "&.hasVerticalPadding": {
                ...Mixins.padding({
                    vertical: vars.spacing.padding,
                }),
            },
            [`& > .${classesInputBlock.root}`]: {
                ...{
                    "&.isFirst": {
                        marginTop: styleUnit(globalVars.gutter.half),
                    },
                    "&.isLast": {
                        marginBottom: styleUnit(globalVars.gutter.half),
                    },
                },
            },
        },
    });

    const framePaddings = style("framePaddings", {
        ...Mixins.padding({
            left: vars.spacing.padding,
            right: vars.spacing.padding,
        }),
    });

    const noContentMessage = style("noContentMessage", {
        ...Mixins.padding({
            top: vars.header.spacing * 2,
            right: vars.header.spacing,
            bottom: vars.header.spacing * 2,
            left: vars.header.spacing,
        }),
    });
    const contents = style("contents", {
        ...Mixins.padding({
            top: vars.spacing.padding,
            right: 0,
            bottom: vars.spacing.padding,
            left: 0,
        }),
        fontSize: importantUnit(globalVars.fonts.size.medium),
        minHeight: styleUnit(50),
        color: ColorsUtils.colorOut(globalVars.mainColors.fg),
    });
    return {
        root,
        framePaddings,
        noContentMessage,
        contents,
    };
});
