/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/styleUtils";
import { suggestedAnswersVariables } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { ColorHelper } from "csx";

export const suggestedAnswersClasses = useThemeCache(() => {
    const globalVars = globalVariables();
    const vars = suggestedAnswersVariables();

    const root = css({
        ...Mixins.box(vars.box),
        ...Mixins.font(vars.font),
        ...Mixins.padding({ vertical: globalVars.gutter.size }),
        ...(vars.box.borderType === BorderType.SEPARATOR && {
            "&:after": { display: "none" },
        }),
    });

    const header = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "flex-start",
        justifyContent: "space-between",
    });

    const headerContent = css({
        flex: 1,
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        columnGap: globalVars.gutter.size,
        rowGap: globalVars.gutter.half,
        flexWrap: "wrap",
        fontSize: globalVars.fonts.size.medium,
    });

    const user = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-start",
        gap: globalVars.gutter.half,
    });

    const userIcon = css({
        fill: "currentcolor",
    });

    const userName = css({
        fontWeight: globalVars.fonts.weights.bold,
    });

    const rootFg = vars.font.color as ColorHelper;
    const rootBg = vars.box.background.color as ColorHelper;
    const helperText = css({
        color: ColorsUtils.colorOut(rootFg.mix(rootBg, 0.75)),
        fontStyle: "italic",
    });

    const loader = css({
        ...Mixins.margin({ vertical: globalVars.gutter.size }),
    });

    const regenerateBox = css({
        background: ColorsUtils.colorOut(
            globalVars.mainColors.primary.mix(vars.box.background.color as ColorHelper, 0.1),
        ),
        color: ColorsUtils.colorOut(vars.font.color),
        borderStyle: "solid",
        borderColor: ColorsUtils.colorOut(globalVars.mainColors.primary),
        borderWidth: 1,
        ...Mixins.padding({ all: globalVars.gutter.size }),
        ...Mixins.margin({ vertical: globalVars.gutter.half }),
        display: "flex",
        flexDirection: "row",
        alignItems: "flex-start",
        gap: globalVars.gutter.half,
        "& p": {
            margin: 0,
            padding: 0,
        },
    });

    const content = css({
        ...Mixins.font(vars.font),
    });

    const contentBox = css({
        overflow: "hidden",
        transition: "ease all 0.5s",
        [`& .${helperText}`]: {
            ...Mixins.margin({ vertical: globalVars.gutter.size }),
        },
    });

    const intro = css({
        ...Mixins.margin({ vertical: globalVars.gutter.size }),
        "& strong": {
            marginInlineEnd: "1ch",
        },
    });

    const list = css({});

    const item = css({
        ...Mixins.box(vars.item.box),
        ...Mixins.font(vars.item.font),
        display: "flex",
        flexDirection: "row",
        alignItems: "flex-start",
        justifyContent: "space-between",
    });

    const itemFg = vars.item.font.color as ColorHelper;
    const itemBg = vars.item.box.background.color as ColorHelper;
    const mutedItemFg = itemFg.mix(itemBg, 0.75);

    const itemContent = css({
        flex: 1,
        "& p": {
            lineHeight: 1.25,
        },
    });

    const itemIcon = css({
        display: "inline-flex",
        alignItems: "center",
        justifyContent: "center",
        background: ColorsUtils.colorOut(itemFg.mix(itemBg, 0.1)),
        aspectRatio: 1,
        borderRadius: "50%",
        ...Mixins.padding({ all: 2 }),
        marginInlineEnd: "0.5ch",
        transform: "translateY(2px)",
    });

    const itemTitle = css({
        ...Mixins.font(vars.item.title),
        "&:after": {
            content: `":"`,
        },
    });

    const itemLink = css({
        display: "inline-flex",
        alignItems: "center",
        color: ColorsUtils.colorOut(mutedItemFg),
        fontSize: "0.875em",
        "&:hover": {
            color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        },
    });

    const itemLinkIcon = css({
        height: "1.75em",
        marginInlineStart: "-0.25ch",
    });

    const dismissAnswer = css({
        marginInlineStart: "1ch",
        "& svg": {
            width: 10,
        },
    });

    const answerButton = css({
        display: "flex",
        alignItems: "center",
        fontSize: globalVars.fonts.size.medium,
        marginTop: globalVars.gutter.half,
        gap: "0.5ch",
    });

    const toggleVisibility = css({
        fontSize: "inherit",
    });

    return {
        root,
        header,
        headerContent,
        user,
        userIcon,
        userName,
        helperText,
        contentBox,
        loader,
        regenerateBox,
        content,
        intro,
        list,
        item,
        itemContent,
        itemIcon,
        itemTitle,
        itemLink,
        itemLinkIcon,
        dismissAnswer,
        answerButton,
        toggleVisibility,
    };
});
