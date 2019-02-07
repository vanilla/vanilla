/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { mixBgAndFg } from "@library/styles/styleHelpers";

export function formElementsVariables() {
    const vars = globalVariables();

    const sizing = {
        height: 36,
        halfHeight: 18,
        maxWidth: 528,
    };

    const spacing = {
        margin: 12,
        horizontalPadding: 12,
        verticalPadding: 6,
    };

    const border = {
        width: 1,
        fullWidth: 2,
        color: vars.border.color,
        style: "solid",
        radius: vars.border.radius,
    };

    const giantInput = {
        height: 82,
        fontSize: 24,
    };

    const largeInput = {
        height: 48,
        fontSize: 16,
    };

    const miniInput = {
        width: 100,
    };

    const colors = {
        fg: mixBgAndFg(0.8),
        bg: vars.mainColors.bg,
    };

    const placeholder = {
        color: mixBgAndFg(0.5),
    };

    return { sizing, spacing, border, giantInput, largeInput, miniInput, colors, placeholder };
}
