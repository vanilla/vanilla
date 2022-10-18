/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { Mixins } from "@library/styles/Mixins";

export const TokenItemClasses = () => {
    const metaVars = metasVariables();
    const root = css({
        display: "inline-flex",
        backgroundColor: "#eeefef",
        borderRadius: 2,
        alignItems: "center",
        maxWidth: "85%",
        ...Mixins.padding({
            vertical: 4,
            horizontal: 8,
        }),
        ...Mixins.font(metaVars.font),
    });

    return {
        root,
    };
};
