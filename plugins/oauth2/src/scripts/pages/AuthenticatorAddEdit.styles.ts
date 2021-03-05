/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import { styleFactory, useThemeCache } from "@library/styles/styleUtils";

export const authenticatorAddEditClasses = useThemeCache(() => {
    const style = styleFactory("authenticatorAddEdit");

    const bodyWrap = style("bodyWrap", {
        overflowX: "hidden",
    });

    return {
        bodyWrap,
    };
});
