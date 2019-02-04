/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { color, percent, px } from "csx";

export const globalVariables = (globalColorsOverwrite = {}, bodyOverwrite = {}, borderOverwrite = {}) => {
    const colorPrimary = color("#0291db");

    const utility = {
        "percentage.third": percent(100 / 3),
        "percentage.nineSixteenths": percent((9 / 16) * 100),
        "svg.encoding": "data:image/svg+xml,",
    };

    const elementaryColors = {
        black: color("#000"),
        white: color("#fff"),
        transparent: color("transparent"),
    };

    const mainColors = {
        fg: color("#555a62"),
        bg: color("#fff"),
        primary: colorPrimary,
        secondary: colorPrimary.darken(10),
        ...globalColorsOverwrite,
    };

    const errorFg = color("#ff3933");
    const warning = color("#ffce00");
    const deleted = color("#D0021B");
    const feedbackColors = {
        warning,
        error: {
            fg: errorFg,
            bg: errorFg.mix(mainColors.bg, 10),
        },
        confirm: color("#60bd68"),
        unresolved: warning.mix(mainColors.fg, 10),
        deleted,
    };

    const body = {
        bg: mainColors.bg,
        ...bodyOverwrite,
    };

    const border = {
        color: mainColors.fg.mix(mainColors.bg, 24),
        width: px(1),
        style: "solid",
        radius: px(6),
        ...borderOverwrite,
    };

    return { utility, elementaryColors, mainColors, feedbackColors, body, border };
};
