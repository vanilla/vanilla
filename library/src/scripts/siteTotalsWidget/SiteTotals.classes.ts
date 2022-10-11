/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";
import { ISiteTotalsOptions, siteTotalsVariables } from "@library/siteTotalsWidget/SiteTotals.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { DeepPartial } from "redux";

export const siteTotalsClasses = useThemeCache((shouldWrap: boolean, options?: DeepPartial<ISiteTotalsOptions>) => {
    const vars = siteTotalsVariables(options);
    const globalVars = globalVariables();
    const mediaQueries = vars.mediaQueries();

    const widget = css({
        margin: 0,
        padding: 0,
    });

    const root = css({
        display: "flex",
        justifyContent: vars.options.alignment,
        alignItems: "center",
        minWidth: styleUnit(globalVars.foundationalWidths.panelWidth),
        ...Mixins.box(vars.options.box),
        borderRadius: 0,
        flexWrap: "wrap",
    });

    const countContainer = css(
        {
            display: "flex",
            alignItems: "center",
            padding: "0.5rem 2rem",
        },
        mediaQueries.mobile({
            flexDirection: "column",
            padding: "1rem",
        }),
    );

    const icon = css({
        color: vars.options.iconColor,
    });

    const count = css({
        margin: "0.25rem",
        ...Mixins.font(vars.count.font),
    });

    const label = css({
        ...Mixins.font(vars.label.font),
    });

    return {
        widget,
        root,
        countContainer,
        icon,
        count,
        label,
    };
});
