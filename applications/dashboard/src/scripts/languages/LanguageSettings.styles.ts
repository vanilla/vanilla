/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";

export const languageSettingsStyles = () => {
    const globalVars = globalVariables();

    const textBox = css({
        "& label": {
            fontWeight: globalVars.fonts.weights.normal,
            marginBottom: 8,
        },
    });

    const loaderLayout = css({
        minHeight: 49,
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        borderBottom: singleBorder(),
        "& div:nth-of-type(1)": {
            marginRight: "auto",
        },
        "& div:nth-of-type(2)": {
            marginRight: 30,
        },
    });

    return {
        textBox,
        loaderLayout,
    };
};
