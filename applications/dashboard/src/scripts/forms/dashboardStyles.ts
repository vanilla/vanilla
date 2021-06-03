/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { styleFactory } from "@library/styles/styleUtils";
import { useThemeCache } from "@library/styles/themeCache";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const dashboardClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const style = styleFactory("dashboard");

    const formList = style({
        padding: 0,
    });

    const extendRow = css({
        ...extendItemContainer(18),
    });

    const helpAsset = style("helpAsset", {
        fontSize: "inherit !important",
        marginBottom: globalVars.gutter.size,
    });

    const tokenInput = style("tokenInput", {
        fontSize: "inherit",
    });

    const selectOne = style("selectOne", {
        ...{
            [`&.SelectOne__value-container.inputText.inputText`]: {
                fontSize: "inherit",
            },
        },
    });

    const formListItem = css({
        minHeight: 49,
        display: "flex",
        justifyContent: "flex-end",
        alignItems: "center",
        borderBottom: singleBorder(),
        "& span:first-of-type": {
            marginRight: "auto",
        },
    });

    const formListItemTitle = css({
        fontSize: globalVars.fonts.size.medium,
        fontWeight: globalVars.fonts.weights.semiBold,
    });

    const formListItemStatus = css({
        display: "inline-block",
        fontSize: globalVars.fonts.size.small,
        fontWeight: globalVars.fonts.weights.normal,
        marginTop: globalVars.fonts.size.medium - globalVars.fonts.size.small,
        color: "#767676",
        marginRight: 30,
        "&:last-of-type": {
            marginRight: 53,
        },
    });

    const formListItemAction = css({
        "& svg": {
            maxWidth: 21,
        },
    });

    const extendBottomBorder = css({
        position: "relative",
        "&:before, &:after": {
            content: "''",
            display: "block",
            width: 18,
            height: "100%",
            borderBottom: singleBorder(),
            position: "absolute",
            bottom: -1,
        },
        "&:before": {
            left: -18,
        },
        "&:after": {
            right: -18,
        },
    });

    return {
        formList,
        helpAsset,
        tokenInput,
        selectOne,
        extendRow,
        formListItem,
        formListItemTitle,
        formListItemStatus,
        formListItemAction,
        extendBottomBorder,
    };
});
