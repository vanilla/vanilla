/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";

export default function ProfileFieldsListClasses() {
    const extendTableRows = css({
        "& th, & td": {
            paddingLeft: 12,
            paddingRight: 12,
        },
        "& td:not(:last-child)": {
            textAlign: "left",
            "& span": {
                justifyContent: "left",
            },
        },
        "& td:first-child, & th:first-child": {
            paddingLeft: 18,
        },
        "& td:last-child": {
            paddingRight: 18,
        },
    });

    const highlightLabels = css({
        "& td:first-of-type": {
            ...Mixins.font({
                ...globalVariables().fontSizeAndWeightVars("medium", "semiBold"),
            }),
        },
    });

    const scrollTable = css({
        overflow: "auto",
        paddingBottom: 20,
    });

    const dashboardHeaderStyles = css({
        background: "#FBFCFF",
        "& tr, & tr th": {
            borderBottom: "none",
        },
        "& th:not(:first-of-type)": {
            borderLeft: "1px solid #D8D8D8",
        },
    });

    const root = css({
        "&:before": {
            content: "''",
            display: "block",
            borderTop: singleBorder(),
            ...extendItemContainer(18),
        },
    });

    const editIconSize = css({
        minWidth: 18,
        width: 18,
    });
    const deleteIconSize = css({
        minWidth: 28,
        width: 28,
    });

    const actionsLayout = css({
        width: 75,
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        paddingTop: 9,
        paddingBottom: 9,
    });

    const actionButtonsContainer = css({
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        gap: 18,
    });

    return {
        root,
        extendTableRows,
        highlightLabels,
        scrollTable,
        dashboardHeaderStyles,
        editIconSize,
        deleteIconSize,
        actionsLayout,
        actionButtonsContainer,
    };
}
