/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/styleUtils";
import { calc } from "csx";

export const widgetSettingsAccordionClasses = useThemeCache(() => {
    const root = css({
        "& > .item": {
            borderBottom: singleBorder(),
        },

        "& + .modernFormGroup": {
            marginTop: 12,
        },

        ".modernFormGroup + &": {
            marginTop: 12,
        },
    });
    const panel = css({
        ...Mixins.padding({
            top: 8,
            horizontal: 16,
        }),
        "& > .modernFormGroup:last-child": {
            paddingBottom: 24,
        },
    });
    const header = css({
        textAlign: "center",
        width: "100%",
        ...Mixins.font({
            transform: "uppercase",
            color: "#757e8c",
            size: 12,
            weight: 600,
        }),
        borderTop: singleBorder(),
    });
    const item = css({
        ...Mixins.margin({
            horizontal: -16,
        }),
        width: calc(`100% + 32px`),
        "& .modernFormGroup": {
            borderBottom: "none",
        },
    });
    return { root, header, panel, item };
});
