/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

export const emailSettingsClasses = useThemeCache(() => {
    const root = css({
        "& span[id^='errorMessages']": {
            scrollMarginTop: 200,
        },
    });
    const section = css({
        scrollMarginTop: 96,
    });

    // a bit messy, but it's the only way to get the right spacing/borders etc
    const contentSection = css({
        "li.meta-group-header ": {
            borderBottom: "none",
            paddingBottom: 0,
        },
        "li.meta-group-header + li": {
            "&.formGroup-checkBox": {
                borderBottom: "none",
                paddingBottom: 0,
                paddingTop: 8,
            },
        },
        "li.formGroup-checkBox": {
            borderBottom: "none",
            paddingTop: 0,
        },
        "li.formGroup-checkBox + li": {
            "&.formGroup-checkBox": {
                borderBottom: "none",
                paddingBottom: 0,
            },
        },
        "li.formGroup-checkBox + li.formGroup-textBox": {
            marginTop: 16,
            borderTop: "1px solid #d8d8d8",
            borderBottom: "none",
            "& + li.formGroup-checkBox": {
                borderBottom: "1px solid #d8d8d8",
                "& .input-wrap": {
                    flex: "0 0 100%",
                    "& label": {
                        paddingTop: 0,
                    },
                },
            },
        },
    });

    const metaGroupHeader = css({
        fontWeight: 600,
        paddingLeft: 18,
    });

    const quickLinks = css({
        display: "flex",
        flexDirection: "column",
        color: ColorsUtils.colorOut(globalVariables().links.colors.default),
    });

    const hidden = css({
        display: "none",
    });

    const uppercase = css({
        textTransform: "uppercase",
    });

    return { root, section, contentSection, metaGroupHeader, quickLinks, hidden, uppercase };
});
