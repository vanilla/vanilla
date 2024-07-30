/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";

export const reportReasonListClasses = () => {
    const headerActions = css({
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        gap: 18,
    });

    const dashboardHeaderStyles = css({
        background: "#FBFCFF",
        "& tr, & tr th": {
            borderBottom: "1px solid #D8D8D8",
        },
        "& th:not(:first-of-type)": {
            borderLeft: "1px solid #D8D8D8",
        },
    });

    const extendTableRows = css({
        "& th, & td": {
            paddingLeft: 12,
            paddingRight: 12,
        },
        "& td:not(:last-child)": {
            textAlign: "start",
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

    const emptyState = css({
        display: "flex",
        flexDirection: "column",
        justifyContent: "center",
        alignItems: "center",
        padding: 48,
        transform: "scale(1.3)",
        opacity: 0.8,
    });

    const nameDescriptionCellRoot = css({});
    const nameDescriptionCellName = css({
        display: "block",
        ...Mixins.font({
            ...globalVariables().fontSizeAndWeightVars("medium", "semiBold"),
        }),
    });
    const nameDescriptionCellDescription = css({
        display: "block",
        ...Mixins.font({
            ...globalVariables().fontSizeAndWeightVars("small", "normal"),
        }),
    });

    const cellOverride = css({
        "& > span": {
            maxHeight: "revert",
        },
    });

    const roleCellRoot = css({
        display: "flex",
        flexWrap: "wrap",
        gap: 4,
    });

    const allRoles = css({
        ...Mixins.font({
            ...globalVariables().fontSizeAndWeightVars("small", "normal"),
        }),
        fontStyle: "italic",
        textWrap: "pretty",
    });

    const errorContainer = css({
        ...Mixins.padding({
            all: 16,
        }),
        textAlign: "center",
    });

    return {
        headerActions,
        dashboardHeaderStyles,
        extendTableRows,
        highlightLabels,
        emptyState,
        nameDescriptionCellRoot,
        nameDescriptionCellName,
        nameDescriptionCellDescription,
        roleCellRoot,
        cellOverride,
        allRoles,
        errorContainer,
    };
};

export const rowActionsClasses = () => {
    const actions = css({
        display: "flex",
        flexDirection: "row",
        justifyContent: "flex-end",
        gap: 4,
    });

    const editIconSize = css({
        minWidth: "revert",
        aspectRatio: "1/1",
        width: 18,
    });
    const deleteIconSize = css({
        minWidth: "revert",
        aspectRatio: "1/1",
        width: 28,
    });

    return {
        actions,
        editIconSize,
        deleteIconSize,
    };
};
