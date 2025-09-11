/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export function postSettingsFormClasses() {
    const globalVars = globalVariables();
    return {
        modal: {
            maxHeight: css({
                maxHeight: "60dvh",
            }),
        },
        layout: css({
            display: "grid",
            gridTemplateColumns: "1fr 24px 1fr",
            alignItems: "center",
            justifyItems: "start",
            gap: 16,
            paddingBlock: 16,
            "&:not(:last-child)": {
                borderBottom: singleBorder(),
            },
        }),
        header: css({
            fontWeight: 600,
            // Fix me
            borderBottom: "none!important",
            paddingBlockEnd: 0,

            "& span": {
                display: "flex",
                alignItems: "center",
                gap: 4,
            },
        }),
        current: css({
            display: "flex",
            flexDirection: "column",
            alignItems: "start",
        }),
        label: css({
            display: "flex",
            alignItems: "center",
            // This is to align the tool tip trigger span
            "& > span": {
                display: "flex",
                alignItems: "center",
            },
        }),
        emptyValue: css({
            fontStyle: "italic",
        }),
        meta: css({
            display: "flex",
            gap: 4,
            marginTop: 4,
            fontSize: 12,
            alignItems: "center",
        }),
        iconToken: css({
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
        }),
        target: css({
            width: "100%",
        }),
        selector: css({
            display: "flex",
            alignItems: "center",
            gap: 8,
            "&:not(:last-child)": {
                marginBlockEnd: 8,
            },
            "& > div": {
                width: "100%",
            },
        }),
        newFieldInput: css({
            display: "flex",
            alignItems: "center",
            gap: 8,
        }),
        dayPicker: css({
            width: "100%",
        }),
        additionalOptionsLabel: css({
            position: "relative",
        }),
        validationMessage: css({
            display: "flex",
            alignItems: "center",
            lineHeight: 1,
            marginBlock: 8,
            fontSize: 12,
            "& svg": {
                zoom: 0.8,
                marginRight: 4,
            },
        }),
        requiredWarning: css({
            marginInlineStart: -16,
            width: "calc(100% + 36px)",
            position: "sticky",
            top: 0,
            zIndex: 50,
        }),
        labelRequired: css({
            color: ColorsUtils.colorOut(globalVars.elementaryColors.red),
            marginInlineStart: -4,
        }),
        errorMessages: css({
            marginBlockStart: 8,
        }),
        setNewFieldsLayout: css({
            paddingBlock: 16,
        }),
        fieldValidationHeader: css({
            marginBottom: 16,
            "& h3": {
                display: "flex",
                alignItems: "center",
                gap: 8,
                marginBottom: 8,
            },
            "& p": {
                margin: 0,
                opacity: 0.8,
            },
        }),
        summary: {
            layout: css({
                marginBlock: 16,
            }),
            postName: css({
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("large", "bold"),
                }),
            }),
            categories: css({
                display: "flex",
                flexWrap: "wrap",
                gap: 4,
                marginBlock: 8,
            }),
            redirect: css({
                fontStyle: "italic",
            }),
            mappingLayout: css({
                display: "grid",
                gridTemplateColumns: "1fr 24px 1fr",
                alignItems: "center",
                justifyItems: "start",
                gap: 16,
                paddingBlock: 4,
                "&:last-child": {
                    paddingBlockEnd: 16,
                },
            }),
            mappingHeader: css({
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                }),
                marginBlock: 16,
                "& span": {
                    display: "flex",
                    alignItems: "center",
                    gap: 4,
                },
            }),

            mappingCurrent: css({}),
            mappingTarget: css({}),
            mappingFieldName: css({
                position: "relative",
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                }),
                marginBlock: 4,
            }),
            mappingFieldValue: css({
                fontStyle: "italic",
            }),
            mappingFieldMeta: css({
                display: "flex",
                gap: 4,
                marginTop: 4,
                fontSize: 12,
                alignItems: "center",
            }),
            discardedField: css({
                "& svg": {
                    color: ColorsUtils.colorOut(globalVars.elementaryColors.red),
                },
            }),
            discardedFieldLabel: css({
                display: "flex",
                alignItems: "center",
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                }),
                marginBlock: 2,
            }),
            discardedFieldValue: css({
                fontStyle: "italic",
            }),
            discardedFieldWarning: css({
                display: "flex",
                alignItems: "center",
                lineHeight: 1,
                marginBlock: 8,
                fontSize: 12,
                color: ColorsUtils.colorOut(globalVars.elementaryColors.red),
            }),
            newFieldIndicator: css({
                display: "flex",
                alignItems: "center",
                gap: 4,
                ...Mixins.font({
                    ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                }),
                color: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                "& svg": {
                    color: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                },
            }),
            noChanges: css({
                paddingBlock: 16,
                "& span": {
                    display: "inline-flex",
                    alignItems: "center",
                    gap: 4,
                    ...Mixins.font({
                        ...globalVars.fontSizeAndWeightVars("large", "bold"),
                    }),
                },
            }),
        },
    };
}
