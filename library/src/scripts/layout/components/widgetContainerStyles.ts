/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { unit } from "@library/styles/styleHelpers";
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";
import { percent } from "csx";
import { containerVariables } from "@library/layout/components/containerStyles";

export const widgetContainerClasses = useThemeCache(() => {
    const style = styleFactory("widgetContainerClasses");
    const vars = containerVariables();

    const root = style({
        position: "relative",
        maxWidth: percent(100),
        margin: "auto",
        width: unit(vars.sizing.narrowContentSize),
    });

    return {
        root,
    };
});
