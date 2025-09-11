/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const messageSourcesStyles = useThemeCache(() => {
    const trayContainer = css({
        display: "flex",
        flexDirection: "column",

        marginTop: "-2em",
    });

    const trayButton = css({
        alignSelf: "flex-end",
        background: "#d8d8d8",
        borderRadius: "5px",
        padding: "2px 5px",

        display: "inline-flex",
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.black),
    });

    const trayContentsContainer = css({
        border: singleBorder(),
        margin: "0.5em",
        borderRadius: "5px",
        padding: "1em",

        maxHeight: "350px",

        background: "#fbfcff",
        overflowY: "auto",
    });

    const messageSourcesList = css({
        margin: "2em",

        counterReset: "sources-counter",
    });

    const messageSourcesListItem = css({
        listStyleType: "none",
        marginBottom: "1em",
        display: "flex",

        "&::before": {
            content: "counter(sources-counter)",
            counterIncrement: "sources-counter",
            marginRight: "0.5em",
            marginLeft: "-1em",

            backgroundColor: "rgb(238,238,239)",
            borderRadius: "50%",

            display: "inline-flex",
            alignItems: "center",
            justifyContent: "center",

            padding: "1em",
            height: "1em",
            width: "1em",

            fontSize: globalVariables().fontSizeAndWeightVars("small").size,
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.black),
        },

        "&.largeNumber": {
            "&::before": {
                width: "24px",
                height: "24px",
                padding: "0",
            },
        },
    });

    const recordTypeTag = css({
        display: "inline-flex",
        alignItems: "center",
        gap: "0.5em",
    });

    const titleSourcesNumber = css({
        fontSize: globalVariables().fontSizeAndWeightVars("small").size,
        fontWeight: "100",
        backgroundColor: "rgb(238,238,239)",
        borderRadius: "5px",
        padding: "3px",
    });

    return {
        trayContainer,
        trayButton,
        trayContentsContainer,
        messageSourcesList,
        messageSourcesListItem,
        recordTypeTag,
        titleSourcesNumber,
    };
});
