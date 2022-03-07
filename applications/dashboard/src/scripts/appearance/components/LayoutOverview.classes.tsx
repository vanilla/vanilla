/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { NoMinHeight } from "@library/layout/Section.story";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const layoutOverviewClasses = useThemeCache(() => {
    const fauxWidget = css({
        position: "relative",
        display: "flex",
        alignItems: "center",
        ...Mixins.box(
            Variables.box({
                borderType: BorderType.SHADOW,
            }),
        ),
        minHeight: styleUnit(80),
        "& p": {
            fontSize: "bold",
            fontFamily: globalVariables().fonts.families.monospace,
        },
    });

    return { fauxWidget };
});
