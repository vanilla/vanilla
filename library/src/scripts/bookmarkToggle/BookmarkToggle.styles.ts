/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { css, CSSObject } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { defaultTransition } from "@library/styles/styleHelpers";

export const bookmarkToggleClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const iconCheckedStyle: CSSObject = {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        fill: "currentColor",
    };

    const iconDefaultStyle: CSSObject = {
        color: "inherit",
        fill: "transparent",
    };

    const iconHoverStyle: CSSObject = {
        color: iconCheckedStyle.color,
    };

    const iconDisabledStyle: CSSObject = {
        ...iconCheckedStyle,
    };

    const icon = css({
        ...iconDefaultStyle,
        ...defaultTransition("color", "fill", "stroke"),
        [`label:hover &, input[type='checkbox']:active + &, input[type='checkbox']:focus + &`]: iconHoverStyle,
    });

    const iconChecked = css(iconCheckedStyle);
    const iconDisabled = css(iconDisabledStyle);

    return {
        icon,
        iconChecked,
        iconDisabled,
    };
});
