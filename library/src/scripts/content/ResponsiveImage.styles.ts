/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";
import { percent } from "csx";

export const responsiveImageClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const ratioContainer = useThemeCache((ratio: { vertical: number; horizontal: number }) => {
        return css({
            position: "relative",
            width: "auto",
            paddingTop: percent((ratio.vertical / ratio.horizontal) * 100),
        });
    });
    const image = css({
        ...Mixins.absolute.fullSizeOfParent(),
        objectFit: "cover",
        borderRadius: 1,
    });

    return { image, ratioContainer };
});
