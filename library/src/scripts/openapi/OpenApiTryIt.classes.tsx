/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export const openApiTryItClasses = {
    form: css({
        height: "100%",
        display: "flex",
        flexDirection: "column",
    }),
    split: css({
        display: "flex",
        flex: "1",
        minHeight: 0,
    }),
    emptyFormSection: css({
        paddingTop: 12,
        paddingBottom: 12,
    }),
    splitRequest: css({
        width: "55%",
        paddingLeft: 18,
        paddingRight: 18,
        height: "100%",
        maxHeight: "100%",
        overflowY: "auto",
        overflowX: "hidden",
        borderRight: singleBorder(),
    }),
    cell: css({
        maxHeight: "initial",
        padding: "0 8px",
    }),
    splitResponse: css({
        width: "45%",
        paddingLeft: 18,
        paddingRight: 18,
        minHeight: "100%",
        maxHeight: "100%",
        overflowY: "auto",
        overflowX: "hidden",
    }),
    tableHeader: css({
        "& th": {
            paddingLeft: 8,
        },
    }),
    jsonError: css({
        marginBottom: 12,
    }),
    header: css({
        position: "sticky",
        top: 0,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: 12,
        background: ColorsUtils.colorOut(globalVariables().mainColors.bg),
        borderBottom: singleBorder(),
        zIndex: 5,
    }),
    headerSep: css({
        borderRight: singleBorder(),
        width: 1,
        height: 20,
        display: "inline-block",
    }),
    spacer: css({
        flex: 1,
    }),
    headerMethod: css({
        textTransform: "uppercase",
        position: "relative",
    }),
    headerInput: css({
        position: "relative",
        ...Mixins.font({ family: globalVariables().fonts.families.monospace, size: 16 }),
        whiteSpace: "nowrap",
        fontSize: 14,
        display: "flex",
        gap: 8,
        alignItems: "center",
        background: ColorsUtils.colorOut(globalVariables().mixBgAndFg(0.08)),
        padding: "6px 12px",
        borderRadius: 6,
        width: "100%",
    }),
    baseUrl: css({
        display: "inline-block",
        background: ColorsUtils.colorOut(globalVariables().mixBgAndFg(0.16)),
        padding: "4px 8px",
        borderRadius: 100,
    }),
    headerButton: css({
        ...Mixins.font({ family: globalVariables().fonts.families.body }),
        gap: 4,
        minWidth: 0,
        display: "flex",
        alignItems: "center",
        minHeight: 28,
        height: 28,
    }),
    formWrap: css({
        paddingLeft: 18,
        paddingRight: 18,
    }),
};
