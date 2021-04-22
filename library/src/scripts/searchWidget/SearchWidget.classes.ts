/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { searchWidgetVariables } from "@library/searchWidget/SearchWidget.variables";

export const searchWidgetClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = searchWidgetVariables();

    const root = css({
        width: "100%",
    });

    const container = css({
        ...Mixins.box(vars.options.box),
        "& > div > div[data-reach-tab-panels]": {
            marginTop: globalVars.gutter.size,
        },
    });

    const tabFooter = css({
        display: "flex",
        justifyContent: "flex-end",
        paddingTop: globalVars.gutter.size,
    });

    return {
        container,
        tabFooter,
        root,
    };
});
