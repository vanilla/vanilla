/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings, unit, importantUnit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { percent } from "csx";

export const frameBodyClasses = useThemeCache(() => {
    const vars = frameVariables();
    const globalVars = globalVariables();
    const style = styleFactory("frameBody");
    const classesInputBlock = inputBlockClasses();

    const root = style({
        position: "relative",
        width: percent(100),
        ...paddings({
            horizontal: vars.spacing.padding,
        }),
        $nest: {
            "&.isSelfPadded": {
                ...paddings({
                    left: 0,
                    right: 0,
                }),
            },
            "&.hasVerticalPadding": {
                ...paddings({
                    vertical: vars.spacing.padding,
                }),
            },
            [`& > .${classesInputBlock.root}`]: {
                $nest: {
                    "&.isFirst": {
                        marginTop: unit(globalVars.gutter.half),
                    },
                    "&.isLast": {
                        marginBottom: unit(globalVars.gutter.half),
                    },
                },
            },
        },
    });

    const noContentMessage = style("noContentMessage", {
        ...paddings({
            top: vars.header.spacing * 2,
            right: vars.header.spacing,
            bottom: vars.header.spacing * 2,
            left: vars.header.spacing,
        }),
    });
    const contents = style("contents", {
        ...paddings({
            top: vars.spacing.padding,
            right: 0,
            bottom: vars.spacing.padding,
            left: 0,
        }),
        fontSize: importantUnit(globalVars.fonts.size.medium),
        minHeight: unit(50),
    });
    return {
        root,
        noContentMessage,
        contents,
    };
});
