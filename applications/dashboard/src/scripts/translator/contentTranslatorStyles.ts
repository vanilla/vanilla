/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { percent, px } from "csx";
import { styleUnit } from "@library/styles/styleUnit";
import { modalVariables } from "@library/modal/modalStyles";
import { titleBarVariables } from "@library/headers/TitleBar.variables";
import { Mixins } from "@library/styles/Mixins";

export const contentTranslatorClasses = useThemeCache(() => {
    const style = styleFactory("contentTranslator");
    const layoutVars = oneColumnVariables();
    const titleBarVars = titleBarVariables();
    const globalVars = globalVariables();

    const content = style("content", {
        paddingTop: styleUnit(modalVariables().fullScreenTitleSpacing.gap),
        position: "relative",
        maxWidth: styleUnit(800),
        width: percent(100),
        ...Mixins.margin({ horizontal: "auto" }),
    });

    const header = style("header", {
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
        height: px(titleBarVars.sizing.height),
        ...layoutVars.mediaQueries().oneColumnDown({
            height: px(titleBarVars.sizing.mobile.height),
        }),
    });

    const title = style("title", {
        display: "flex",
        ...Mixins.padding({
            left: styleUnit(globalVars.gutter.half),
            right: styleUnit(globalVars.gutter.half),
            top: globalVars.gutter.size - 6,
        }),
    });

    const translateIcon = style("translateIcon", {
        marginLeft: styleUnit(12),
        marginRight: "auto",
    });

    return { content, header, translateIcon, title };
});
