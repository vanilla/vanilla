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

    const description = css({
        marginBottom: 8,
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

    const addonLoaderLayout = css({
        minHeight: 102,
        display: "flex",
        alignItems: "center",
        borderBottom: singleBorder(),
        justifyContent: "flex-end",
        "& div:nth-of-type(1)": {
            marginRight: 14,
        },
        "& div:nth-of-type(2)": {
            width: "70%",
            marginRight: "auto",
            "& > div": {
                marginBottom: 8,
            },
            "& div:nth-of-type(1)": {
                marginBottom: 12,
            },
        },
        "& div:nth-of-type(3)": {
            marginRight: 30,
        },
    });

    const subHeader = css({
        minHeight: 70, // to match "form-group" height
        display: "flex",
        alignItems: "center",
        fontSize: globalVars.fonts.size.small,
        position: "relative",
        "&:after": {
            content: "''",
            display: "block",
            width: "calc(100% + (18px * 2))",
            height: 1,
            borderBottom: "1px dotted #e7e8e9", // To match "form-group" border style
            position: "absolute",
            left: -18,
            bottom: 1,
        },
    });

    const warning = css({
        fontSize: globalVars.fonts.size.small,
        color: globalVars.messageColors.error.fg.toString(),
    });

    // Using !important here to override the 'auto' overflow style
    // to allow suggestions to be rendered outside of the modal frame
    // This should be fixed by https://github.com/vanilla/vanilla-cloud/issues/3046
    const modalSuggestionOverride = css(`
        overflow: visible!important
    `);

    return {
        description,
        loaderLayout,
        addonLoaderLayout,
        subHeader,
        modalSuggestionOverride,
        warning,
    };
};
