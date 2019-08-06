/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { paddings, unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { frameVariables } from "@library/layout/frame/frameStyles";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

export const frameBodyClasses = useThemeCache(() => {
    const vars = frameVariables();
    const globalVars = globalVariables();
    const style = styleFactory("frameBody");
    const classesInputBlock = inputBlockClasses();

    const root = style({
        position: "relative",
        ...paddings({
            left: vars.spacing.padding,
            right: vars.spacing.padding,
        }),
        $nest: {
            "&.isSelfPadded": {
                ...paddings({
                    left: 0,
                    right: 0,
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
        minHeight: unit(50),
    });
    return {
        root,
        noContentMessage,
        contents,
    };
});
