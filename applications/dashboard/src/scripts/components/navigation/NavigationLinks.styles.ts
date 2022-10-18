/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export default useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("navigationManager");

    const expandColapseButton = style("expandColapseButton", {
        display: "flex",
        alignItems: "center",
        height: 36,
        marginLeft: -8,
        marginRight: 16,
        ...{
            "& span": {
                marginLeft: 6,
            },
        },
    });

    const newLinkButton = style("newLinkButton", {
        display: "flex",
        alignItems: "center",
        height: 36,
        ...{
            "& span": {
                marginLeft: 6,
            },
        },
    });

    const spacer = style({
        flex: 1,
    });

    const toolbar = style("toolbar", {
        display: "flex",
        flexDirection: "row",
        ...Mixins.margin({
            horizontal: -16,
        }),
        ...Mixins.padding({
            horizontal: 16,
        }),
        borderBottom: singleBorder(),
    });

    const treeContainer = style("treeContainer", {
        position: "relative",
        ...Mixins.margin({
            horizontal: -8,
        }),
        ...Mixins.padding({
            vertical: 16,
        }),
    });

    return {
        expandColapseButton,
        newLinkButton,
        spacer,
        toolbar,
        treeContainer,
    };
});
