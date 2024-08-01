/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { metasVariables } from "@library/metas/Metas.variables";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { GlobalPreset, globalVariables } from "@library/styles/globalStyleVars";

export const TokenItemClasses = () => {
    const metaVars = metasVariables();
    const root = css({
        display: "inline-flex",
        backgroundColor: "#eeefef",
        borderRadius: 2,
        alignItems: "center",
        maxWidth: "85%",
    });

    const textContent = css({
        ...Mixins.padding({
            vertical: 4,
            horizontal: 8,
        }),
        ...Mixins.font(metaVars.font),
        ...(globalVariables().options.preset === GlobalPreset.DARK && {
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.darkText),
        }),
    });

    const textContentCompact = css({
        ...Mixins.padding({
            vertical: 1,
            horizontal: 6,
        }),
        color: "inherit",
        fontWeight: "inherit",
    });

    const button = css({
        marginLeft: -4,
    });

    const icon = css({
        height: 7,
        width: 7,
        position: "relative",
        transform: "translateY(1px)",
    });

    return {
        root,
        textContent,
        textContentCompact,
        button,
        icon,
    };
};
