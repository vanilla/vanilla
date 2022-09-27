/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpers";
import { viewHeight } from "csx";
import { oneColumnVariables } from "@library/layout/Section.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";

export const widgetSettingsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const container = css({
        height: "100%",
        "&&&": {
            width: 1125,
            maxHeight: viewHeight(80),
            ...mediaQueries.oneColumnDown({
                width: "100%",
            }),
        },
    });

    const section = css({
        "&&": {
            width: "100%",
            height: "100%",
            display: "flex",
            paddingTop: 0,
            paddingBottom: 0,
            ...mediaQueries.oneColumnDown({
                flexDirection: "column",
            }),
        },
    });

    const preview = css({
        flex: 1,
        display: "flex",
        flexDirection: "column",
        maxWidth: "70%",
        width: "100%",
        minWidth: 500,
        ...mediaQueries.oneColumnDown({
            maxWidth: "100%",
            minWidth: "initial",
            overflow: "initial",
        }),
    });

    const previewHeader = css({
        marginTop: 8,
        marginBottom: 16,

        ...Mixins.padding({
            top: globalVars.spacer.panelComponent,
            horizontal: globalVars.spacer.panelComponent,
        }),
    });

    const previewBody = css({
        position: "sticky",
        overflow: "auto",
        ...Mixins.margin({
            vertical: globalVars.spacer.panelComponent,
            horizontal: globalVars.spacer.panelComponent,
        }),
        ...Mixins.border({
            radius: 6,
        }),
        "& > div": {
            width: "100%",
            height: "60%",
        },
    });

    const previewContent = css({
        padding: 16,
        ...Mixins.margin({ vertical: 0 }),
        "& > div": {
            ...Mixins.margin({ vertical: 0 }),
        },
    });

    const settings = css({
        minWidth: 300,
        maxWidth: "35%",
        width: "100%",
        overflowY: "auto",
        borderLeft: singleBorder(),
        ...Mixins.padding({
            all: 16,
            top: 0,
        }),
        ...mediaQueries.oneColumnDown({
            maxWidth: "100%",
            borderLeft: "none",
            borderTop: singleBorder(),
            overflowY: "initial",
        }),
    });

    const settingsHeader = css({
        ...Mixins.padding({
            vertical: globalVars.spacer.headingBox,
        }),
        textAlign: "center",
        textTransform: "uppercase",
    });

    //overwrite some styles coming from admin-new, to obtain smaller input size
    const modalForm = css({
        minHeight: viewHeight(80),
        "& > section": {
            maxHeight: "none",
        },

        "& .form-group": {
            borderBottom: "none",
            paddingTop: 0,
            paddingBottom: 8,

            "& .label-wrap": {
                flex: "0 0 41%",
            },

            "& .input-wrap": {
                display: "flex",
                justifyContent: "flex-end",
                flex: "0 0 59%",
                maxWidth: "59%",

                "& textarea, & input": {
                    fontSize: 13,
                    borderColor: ColorsUtils.colorOut(globalVars.border.color),
                    "&:focus, &:hover, &:active, &.focus-visible": {
                        borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                    },
                },

                "& input": {
                    minHeight: 28,
                    lineHeight: "28px",
                    padding: "0 8px",
                },
            },

            //little bit smaller toggle than the default
            "& .toggle-wrap": {
                width: 60,
                height: 30,

                "& .toggle-well": {
                    width: 60,
                    height: 30,
                },

                "& .toggle-slider": {
                    width: 24,
                    height: 24,
                },

                "&.toggle-wrap-on .toggle-slider": {
                    left: 33,
                },
            },

            //upload
            "& .file-upload": {
                minHeight: 30,
                "& > input, & .file-upload-choose, & .file-upload-browse": {
                    lineHeight: "28px",
                    fontSize: 13,
                    maxHeight: 30,
                    borderColor: ColorsUtils.colorOut(globalVars.border.color),
                    "&:focus, &:hover, &:active, &.focus-visible": {
                        borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                    },
                },

                "& .file-upload-choose": {
                    padding: "0 8px",
                    maxWidth: "calc(100% - 63px)",
                    overflow: "hidden",
                    textOverflow: "ellipsis",
                    whiteSpace: "nowrap",
                    borderTopRightRadius: 0,
                    borderBottomRightRadius: 0,
                },

                "& .file-upload-browse": {
                    minWidth: 64,
                    background: ColorsUtils.colorOut(globalVars.mainColors.bg),
                    color: ColorsUtils.colorOut(globalVars.mainColors.fg),
                    "&:focus, &:hover, &:active, &.focus-visible": {
                        background: ColorsUtils.colorOut(globalVars.mainColors.primary),
                        borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
                        color: ColorsUtils.colorOut(globalVars.mainColors.primaryContrast),
                    },
                },
            },
        },

        "& .form-group:last-of-type": {
            paddingBottom: 24,
        },

        "& .formGroup-toggle": {
            "& .label-wrap": {
                flex: "0 0 66.66666666%",
            },

            "& .input-wrap": {
                flex: "0 0 33.33333333%",
            },
        },

        "& .formGroup-radio": {
            "& .input-wrap": {
                flexDirection: "column",
            },
        },
    });

    const autocompleteContainer = css({
        borderColor: ColorsUtils.colorOut(globalVars.border.color),

        "&:focus, &:hover, &:active, &.focus-visible": {
            borderColor: ColorsUtils.colorOut(globalVars.elementaryColors.primary),
        },

        "& > input::placeholder": {
            color: "#aaadb1",
        },
    });

    const formGroupHeader = css({
        display: "block",
        ...Mixins.font({
            size: 16,
            weight: 700,
        }),
        ...Mixins.margin({
            vertical: 16,
        }),
    });

    return {
        container,
        section,
        preview,
        previewHeader,
        previewBody,
        previewContent,
        settings,
        settingsHeader,
        modalForm,
        formGroupHeader,
        autocompleteContainer,
    };
});
