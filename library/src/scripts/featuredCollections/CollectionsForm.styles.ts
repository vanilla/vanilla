/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { inputMixin } from "@library/forms/inputStyles";
import { LoadStatus } from "@library/@types/api/core";

export const collectionsFormClasses = useThemeCache(() => {
    const globalVars = globalVariables();

    const formBody = css({
        minHeight: 300,
    });

    const newCollection = css({
        ...Mixins.margin({ top: 16 }),
        display: "flex",
        alignItems: "center",
    });

    const deleteNewCollectionButton = css({
        width: 20,
        minWidth: 20,
        height: 20,
        ...Mixins.margin({ left: 8 }),
        borderRadius: 10,
    });

    const newCollectionsInputWrapper = css({});

    const newCollectionStatus = css({
        ...Mixins.font({
            ...globalVars.fontSizeAndWeightVars("small"),
        }),
        padding: inputMixin().padding,
        paddingTop: inputMixin().paddingTop,
        paddingBottom: inputMixin().paddingBottom,
        paddingLeft: inputMixin().paddingLeft,
        paddingRight: inputMixin().paddingRight,
        textTransform: "capitalize",
        [`&.${LoadStatus.SUCCESS}`]: {
            color: ColorsUtils.colorOut(globalVars.messageColors.confirm),
        },
        [`&.${LoadStatus.ERROR}`]: {
            color: ColorsUtils.colorOut(globalVars.messageColors.error.fg),
        },
    });

    const addNewWrapper = css({
        display: "flex",
        justifyContent: "flex-start",
        ...Mixins.padding({ vertical: 8 }),
    });

    const addNewButton = css({
        fontWeight: globalVars.fonts.weights.normal,
        display: "flex",
        alignItems: "center",
        lineHeight: 1,
        "& svg": {
            ...Mixins.margin({ right: 4 }),
            width: "1.25em",
        },
    });

    return {
        formBody,
        newCollection,
        deleteNewCollectionButton,
        newCollectionsInputWrapper,
        newCollectionStatus,
        addNewWrapper,
        addNewButton,
    };
});
