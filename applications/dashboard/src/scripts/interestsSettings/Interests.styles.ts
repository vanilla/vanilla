/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/styleUtils";

export const interestsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const defaultInterestCheckbox = css({
        paddingRight: 16,
    });

    const table = css({
        marginLeft: -18,
        marginRight: -18,
    });

    const tableActions = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-end",
        gap: 2,
    });

    const tablePaging = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
    });

    const pager = css({
        flex: 1,
    });

    const defaultInterestIcon = css({
        "&&": {
            width: 16,
            height: 16,
            ...Mixins.padding({ top: 2 }),
            "& svg": {
                color: ColorsUtils.colorOut(globalVars.mainColors.fg),
            },
        },
    });

    const defaultInterestIconHeader = css({
        "&&": {
            margin: "auto",
        },
    });

    const interestsHeader = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        gap: 2,
        padding: 0,
    });

    const interestName = css({
        "&&": {
            margin: 0,
            fontWeight: globalVars.fonts.weights.semiBold,
        },
    });

    const interestApiName = css({
        "&&": {
            margin: 0,
            fontSize: globalVars.fonts.size.small,
        },
    });

    const cellFlexBox = css({
        flex: 1,
        display: "flex",
        flexDirection: "row",
        flexWrap: "wrap",
        gap: 4,
    });

    const cellWrapped = css({
        margin: 0,
        marginBlockStart: 6,
        "&&": {
            marginBottom: 0,
        },
    });

    const cellWrappedTitle = css({
        fontWeight: globalVars.fonts.weights.semiBold,
        paddingInlineEnd: "1ch",
        "&:after": {
            content: '":"',
        },
    });

    const cellWrappedProfileField = css({
        "& em": {
            paddingInlineStart: "0.5ch",
        },
    });

    const interestProfileField = css({
        flexWrap: "nowrap",
        gap: 8,
        width: "100%",
        ...Mixins.margin({ vertical: 8 }),
    });

    const interestProfileFieldValues = css({
        flex: 1,
    });

    const interestCategory = css({
        "&&": {
            margin: 0,
            fontSize: globalVars.fonts.size.small,
        },
    });

    const filterAside = css({
        paddingInlineEnd: 14,
    });

    const filterField = css({
        marginBlockStart: 12,
    });

    const filterButtons = css({
        display: "flex",
        justifyContent: "flex-end",
        alignContent: "center",
        ...Mixins.padding({ vertical: 14 }),
    });

    const defaultInterestTooltip = css({
        display: "inline-flex",
        alignItems: "center",
    });

    return {
        defaultInterestCheckbox,
        table,
        tableActions,
        tablePaging,
        pager,
        defaultInterestIcon,
        defaultInterestIconHeader,
        interestName,
        interestApiName,
        cellFlexBox,
        cellWrapped,
        cellWrappedTitle,
        cellWrappedProfileField,
        interestProfileField,
        interestProfileFieldValues,
        interestCategory,
        filterAside,
        filterField,
        filterButtons,
        interestsHeader,
        defaultInterestTooltip,
    };
});
