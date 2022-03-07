/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { widgetLayoutClasses } from "@library/layout/WidgetLayout.styles";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const sectionOneColumnClasses = useThemeCache(() => {
    const widgetClasses = widgetLayoutClasses();
    const widgetClass = css({
        ...widgetClasses.widgetMixin,
        "&:first-child": {
            marginTop: 0,
        },
        "&:last-child": {
            marginBottom: 0,
        },
    });

    return { widgetClass };
});
