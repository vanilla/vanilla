/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { StatusLightVariables } from "@library/statusLight/StatusLight.variables";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const StatusLightClasses = useThemeCache((active = true) => {
    const vars = StatusLightVariables();

    return {
        root: css({
            position: "relative",
            minWidth: vars.sizing.width,

            "&:after": {
                ...Mixins.absolute.middleRightOfParent(),
                content: `""`,
                height: vars.sizing.width,
                width: vars.sizing.width,
                background: active ? vars.colors.active.toString() : vars.colors.inactive.toString(),
                borderRadius: "50%",
            },
        }),
    };
});
