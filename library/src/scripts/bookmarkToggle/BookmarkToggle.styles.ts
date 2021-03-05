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
        stroke: ColorsUtils.colorOut(globalVars.mainColors.primary),
        fillOpacity: 1,
    };

    const iconDefaultStyle: CSSObject = {
        fill: iconCheckedStyle.stroke,
        fillOpacity: 0,
        stroke: ColorsUtils.colorOut(globalVars.mainColors.fg),
    };

    const iconHoverStyle: CSSObject = {
        stroke: iconCheckedStyle.stroke,
    };

    const iconDisabledStyle: CSSObject = {
        ...iconCheckedStyle,
        fillOpacity: 0.5,
    };

    const icon = css({
        ...iconDefaultStyle,
        ...defaultTransition("fill-opacity"),
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
