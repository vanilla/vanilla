/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const sectionEvenColumnsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const layoutVars = oneColumnVariables();
    return {
        root: css({
            position: "relative",
        }),
        container: css({
            // the container is a flexbox by default.
            // This is problematic as it disables margin collapsing.
            display: "block",
        }),
        columns: css({
            display: "flex",
            flexWrap: "wrap",
            gap: globalVars.spacer.pageComponent,
        }),
        column: css({
            flex: 1,
            minWidth: 240,
        }),
        widget: css({
            clear: "both", // Some old themes have weird floating elements.
            ...Mixins.margin({
                vertical: globalVars.spacer.pageComponentCompact,
            }),
            "&:first-child": {
                marginTop: 0,
            },
            "&:last-child": {
                marginBottom: 0,
            },
        }),
        breadcrumbs: css({}),
    };
});
