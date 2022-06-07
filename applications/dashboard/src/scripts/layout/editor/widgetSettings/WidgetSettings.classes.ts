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
        ...Mixins.padding({
            vertical: globalVars.spacer.panelComponent,
            horizontal: globalVars.spacer.panelComponent,
        }),
        flex: 1,
        display: "flex",
        flexDirection: "column",
        maxWidth: "70%",
        width: "100%",
        overflow: "auto",
        minWidth: 500,
        ...mediaQueries.oneColumnDown({
            maxWidth: "100%",
            minWidth: "initial",
            overflow: "initial",
        }),
    });

    const previewHeader = css({
        marginTop: 8,
        marginBottom: 24,
    });

    const previewBody = css({
        flex: 1,
        "& > div": {
            width: "100%",
            height: "60%",
        },
    });

    const settings = css({
        minWidth: 300,
        maxWidth: "35%",
        width: "100%",
        overflowY: "auto",
        borderLeft: singleBorder(),
        padding: 16,
        ...mediaQueries.oneColumnDown({
            maxWidth: "100%",
            borderLeft: "none",
            borderTop: singleBorder(),
            overflowY: "initial",
        }),
    });

    const settingsHeader = css({
        ...Mixins.padding({
            top: globalVars.spacer.headingBox,
        }),
        textAlign: "center",
        textTransform: "uppercase",
    });

    const modalForm = css({
        minHeight: viewHeight(80),
        "& > section": {
            maxHeight: "none",
        },
    });

    return { container, section, preview, previewHeader, previewBody, settings, settingsHeader, modalForm };
});
