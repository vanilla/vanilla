/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { Variables } from "@library/styles/Variables";
import { useThemeCache } from "@library/styles/themeCache";

export const vanillaEditorClasses = useThemeCache(() => {
    const root = ({ horizontalPadding = true }: { horizontalPadding?: boolean }) =>
        css({
            ...Mixins.padding(
                Variables.spacing({
                    vertical: 14,
                    ...(horizontalPadding && {
                        horizontal: 14,
                    }),
                }),
            ),

            "&.focus-visible, &:focus, &:focus-visible": {
                outline: "none",
            },
            flex: 1,
        });

    const elementToolbarPosition = css({
        position: "absolute",
        zIndex: 10,
        transition: "transform linear 50ms",
        willChange: "transform",
        top: 0,
        left: 0,
    });

    function elementToolbarContents(position: "above" | "below") {
        return css({
            position: "absolute",
            zIndex: 1,
            ...(position === "above" ? { bottom: "100%" } : { top: "100%" }),
            left: 0,
        });
    }

    return { root, elementToolbarPosition, elementToolbarContents };
});
