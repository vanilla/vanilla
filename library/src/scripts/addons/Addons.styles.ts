/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";
import { css, CSSObject } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpers";
import { shadowHelper } from "@library/styles/shadowHelpers";

export const addonClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const spacer = globalVars.spacer.componentInner;

    const root = css({
        display: "flex",
        alignItems: "center",
    });

    const column = css({
        ...Mixins.margin({
            horizontal: spacer,
        }),
    });

    const previewAndTextContainer = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
    });

    const previewContainer = css({
        maxWidth: 232,
        minWidth: 140,
        width: "40%",
        overflow: "hidden",
        ...shadowHelper().embed(),
        ...Mixins.margin({
            right: spacer,
        }),
    });

    const previewContainerMobile = css({
        width: "100%",
        overflow: "hidden",
        ...shadowHelper().embed(),
        ...Mixins.margin({
            bottom: spacer,
        }),
    });

    const previewImage = css({
        maxWidth: "100%",
        width: "100%",
    });

    const textContainer = css({
        flex: 2,
        flexGrow: 1,
    });

    const title = css({
        "&&": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "bold"),
                lineHeight: 20 / 14,
            }),
            ...Mixins.margin({
                all: 0,
                bottom: 10,
            }),
        },
    });

    const description = css({
        "&&": {
            ...Mixins.font({
                ...globalVars.fontSizeAndWeightVars("medium", "normal"),
                lineHeight: 20 / 14,
            }),
            ...Mixins.margin({
                all: 0,
                bottom: spacer,
            }),
        },
    });

    const notes = css({
        "&&": {
            ...Mixins.font({
                size: 13,
                lineHeight: 18 / 13,
            }),
            ...Mixins.margin({
                all: 0,
            }),
        },
    });

    const optionsContainer = css({});

    return {
        root,
        column,
        previewAndTextContainer,
        previewContainer,
        previewContainerMobile,
        previewImage,
        textContainer,
        title,
        description,
        notes,
        optionsContainer,
    };
});
