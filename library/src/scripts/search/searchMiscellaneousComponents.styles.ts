/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { metasVariables } from "@library/metas/Metas.variables";
import { useThemeCache } from "@library/styles/themeCache";
import { styleUnit } from "@library/styles/styleUnit";
import { Mixins } from "@library/styles/Mixins";
import { css } from "@emotion/css";

export const searchMiscellaneousComponentsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const metasVars = metasVariables();

    const root = css({
        display: "flex",
        alignItems: "baseline",
        justifyContent: "flex-end",
    });

    const sort = css({
        display: "flex",
        flexWrap: "wrap",
        ...Mixins.margin({
            all: 0,
        }),
    });

    const sortLabel = css({
        alignSelf: "center",
        marginRight: styleUnit(6),
        ...Mixins.font({
            color: metasVars.font.color,
            weight: globalVars.fonts.weights.normal,
        }),
    });

    const pages = css({
        ...Mixins.margin({
            left: globalVars.gutter.size,
        }),
    });

    return {
        root,
        sort,
        sortLabel,
        pages,
    };
});
