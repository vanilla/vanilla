/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";

export const escalationActionPanelClasses = () => {
    // Fighting with compat styles here
    const contentPanelOverrides = css({
        position: "absolute",
        minWidth: 200,
        padding: 0,
        "& ul, & ul ul": {
            all: "revert",
            margin: 0,
            padding: 0,
            listStyle: "none",
            display: "revert",

            "& li, & li li": {
                listStyle: "revert!important",
                position: "revert",
                margin: 0,
            },
        },
    });

    const layout = css({
        display: "flex",
        gap: 8,
    });

    const statusButton = css({
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        gap: 4,
    });

    return {
        layout,
        contentPanelOverrides,
        statusButton,
    };
};

export const getStatusClasses = (status?: string) => {
    switch (status) {
        case "open":
        case "in-progress": {
            return css({
                color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            });
        }
        case "on-hold": {
            return css({
                "&, &:not([disabled]):hover, &:not([disabled]):focus, &.focus-visible, &:not([disabled]):active": {
                    color: ColorsUtils.colorOut(globalVariables().elementaryColors.almostBlack),
                    borderColor: "#ef9722",
                    backgroundColor: "#ef972266",
                },
            });
        }
        case "in-zendesk":
        case "done": {
            return css({
                "&, &:not([disabled]):hover, &:not([disabled]):focus, &.focus-visible, &:not([disabled]):active": {
                    color: ColorsUtils.colorOut(globalVariables().elementaryColors.white),
                    borderColor: "#008b43",
                    backgroundColor: "#008b43",
                },
            });
        }
        default: {
            return css({
                color: ColorsUtils.colorOut(globalVariables().elementaryColors.primary),
            });
        }
    }
};
