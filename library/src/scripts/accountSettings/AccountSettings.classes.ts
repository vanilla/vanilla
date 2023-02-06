/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/themeCache";

export const accountSettingsClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const root = css({});

    const subtitle = css({
        color: ColorsUtils.colorOut(globalVars.mainColors.fgHeading),
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("subTitle"),
        }),
        ...Mixins.margin({ vertical: 16 }),
    });

    const infoRow = css({
        ...Mixins.margin({ vertical: 16 }),
    });

    const infoLabel = css({
        display: "flex",
        alignItems: "center",
        fontWeight: globalVars.fonts.weights.bold,
    });

    const infoDetail = css({
        display: "flex",
        alignItems: "center",
        "& .password": {
            letterSpacing: "-0.25em",
            ...Mixins.margin({ left: "-0.25em" }),
        },
    });

    const infoEdit = css({
        width: 16,
        minWidth: 16,
        height: 16,
        ...Mixins.margin({ horizontal: 10 }),
        "@media(max-width: 806px)": {
            minWidth: 16,
        },
    });

    const emailVerify = css({
        display: "flex",
        alignItems: "center",
        fontWeight: globalVars.fonts.weights.normal,
        fontSize: globalVars.fonts.size.small,
        ...Mixins.margin({ left: 10 }),
    });

    const verified = css({
        color: ColorsUtils.colorOut(globalVars.messageColors.confirm),
        width: globalVars.fonts.size.large,
        height: globalVars.fonts.size.large,
    });

    const unverified = css({
        color: ColorsUtils.colorOut(globalVars.messageColors.warning.state),
        width: globalVars.fonts.size.medium,
        height: globalVars.fonts.size.medium,
    });

    const loadingRectAdjustments = css({
        ...Mixins.margin({
            top: globalVars.spacer.headingItem / 2,
        }),
        height: "18px!important",
    });

    const topLevelErrors = css({
        marginBottom: 16,
    });

    const fitWidth = css({
        maxWidth: "fit-content",
    });

    const instructions = css({
        ...Mixins.margin({
            bottom: globalVariables().spacer.componentInner,
        }),
    });

    const passwordMatchAdjustments = css({
        ...Mixins.margin({
            top: 8,
        }),
    });

    return {
        root,
        subtitle,
        infoRow,
        infoLabel,
        infoDetail,
        infoEdit,
        emailVerify,
        verified,
        unverified,
        loadingRectAdjustments,
        topLevelErrors,
        fitWidth,
        instructions,
        passwordMatchAdjustments,
    };
});
