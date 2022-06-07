/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";
import { calc } from "csx";

export const widgetSettingsAccordionClasses = useThemeCache(() => {
    const root = css({
        "&:last-child > .item": {
            borderBottom: "solid 1px #c1cbd7",
        },
    });
    const panel = css({
        ...Mixins.padding({
            top: 8,
            horizontal: 16,
        }),
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
        borderTop: "solid 1px #c1cbd7",
    });
    const item = css({
        ...Mixins.margin({
            horizontal: -16,
        }),
        width: calc(`100% + 32px`),
        "& .form-group": {
            borderBottom: "none",
        },
    });
    return { root, header, panel, item };
});
