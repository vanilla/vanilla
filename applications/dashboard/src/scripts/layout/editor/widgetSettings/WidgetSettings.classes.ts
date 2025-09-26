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
import { bodyStyleMixin } from "@library/layout/bodyStyles";
import { ColorVar } from "@library/styles/CssVar";

export const widgetSettingsClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const mediaQueries = oneColumnVariables().mediaQueries();

    const container = css({
        height: "100%",
        "&&&": {
            maxHeight: viewHeight(80),
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
        ...bodyStyleMixin(),
        position: "sticky",
        overflow: "auto",
        ...Mixins.margin({
            vertical: globalVars.spacer.panelComponent,
        }),
        ...Mixins.border({
            radius: 6,
        }),
        "& > div": {
            width: "100%",
            height: "60%",
        },

        // Special override for the Article Reactions widget, see note in ArticleReactionsWidgetPreview
        "&.article-reactions": {
            border: "none",
            margin: 0,
        },

        "&.titleBar": {
            position: "relative",
            minHeight: 200,
        },
    });

    const previewContainer = css({
        ...Mixins.margin({ horizontal: globalVars.spacer.panelComponent }),
    });

    const previewContent = css({
        padding: 16,
        ".titleBar &": {
            padding: 0,
        },
        ...Mixins.margin({ vertical: 0 }),
        "& > div": {
            ...Mixins.margin({ vertical: 0 }),
        },

        // Special override for the Article Reactions widget, see note in ArticleReactionsWidgetPreview
        "& .articleReactionsPage": {
            display: "none",
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
    });

    const autocompleteContainer = css({
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
        previewContainer,
        previewColorSwatch: css({
            border: singleBorder(),
            borderRadius: 6,
            height: 24,
        }),
        previewColorLabel: css({
            display: "inline-flex",
            alignItems: "center",
            margin: 0,
            gap: 4,

            "& strong": {
                fontWeight: 600,
            },
        }),
        previewContent,
        settings,
        settingsHeader,
        modalForm,
        formGroupHeader,
        autocompleteContainer,
    };
});
