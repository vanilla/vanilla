/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";

export const embedDropdownClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("embedDropdown");
    const container = style("container", {
        listStyle: "none !important", // Overrides default ul/li style
        margin: "0 !important", // Overrides default ul/li style
        position: "absolute",
        top: "100%",
        left: -1,
        right: -1,
        background: "white",
        ...Mixins.border(),
        borderTopLeftRadius: 0,
        borderTopRightRadius: 0,
    });

    const option = style("option", {
        display: "flex",
        ...Mixins.padding({ horizontal: 8, vertical: 4 }),
        alignItems: "center",
        margin: "4px !important", // Overrides default ul/li style
        height: 32,
        borderRadius: globalVars.border.radius,
        ...{
            "&:hover": {
                color: ColorsUtils.colorOut(globalVars.mainColors.primary),
            },
            "&:before": {
                display: "none",
            },
        },
    });

    const optionLabel = style("optionLabel", {
        flex: 1,
        textAlign: "left",
        marginLeft: 12,
    });

    const check = style("check", {
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        marginBottom: -1,
    });

    return { container, option, optionLabel, check };
});
