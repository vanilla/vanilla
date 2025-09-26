/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forum Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/themeCache";
import { Mixins } from "@library/styles/Mixins";
import { numberedPagerVariables } from "@library/features/numberedPager/NumberedPager.variables";
import { CSSObject } from "@emotion/serialize";

export const numberedPagerClasses = useThemeCache(() => {
    const vars = numberedPagerVariables();

    const mobileMixin = (styles: CSSObject): CSSObject => {
        return {
            "@container(max-width: 600px)": {
                ...styles,
            },
        };
    };

    const root = css({
        ...Mixins.background(vars.background),
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "space-between",
        ...Mixins.font(vars.font),
        lineHeight: 1,
        ...Mixins.padding({ vertical: 16 }),
        containerType: "normal",

        "& > div": {
            // the first and last divs should flex to ensure proper alignment of the controls
            "&:first-child, &:last-child": {
                flex: 1,
            },
        },

        ...mobileMixin({
            "& .noMobile": {
                display: "none",
            },
        }),
    });

    const resultCount = css({
        display: "flex",
        flexDirection: "row",
        alignItems: "center",
        justifyContent: "flex-end",
        width: "max-content",
        whiteSpace: "nowrap",
    });

    const pageNumber = css({
        ...Mixins.padding({
            horizontal: 4,
        }),
    });

    const iconButton = css({
        ...Mixins.button(vars.buttons.iconButton),
        width: vars.buttons.iconButton.sizing?.minWidth,
        height: vars.buttons.iconButton.sizing?.minHeight,
        "& svg": {
            fill: "currentcolor",
        },
    });

    const nextPageWrapper = css({
        display: "flex",
        justifyContent: "center",
        ...mobileMixin({
            justifyContent: "flex-start",
        }),
        "@container(max-width: 340px)": {
            display: "none",
        },
    });

    const nextPageButton = css({
        ...Mixins.button(vars.buttons.nextPage),
    });

    const jumperInput = css({
        width: "fit-content !important",
        "& > span": {
            ...Mixins.margin({ horizontal: 8, vertical: 0 }),
            "& > input": {
                ...Mixins.padding({ all: 4 }),
                ...Mixins.font(vars.font),
                textAlign: "center",
                minWidth: 1,
                width: "5ch",
                minHeight: vars.buttons.jumperGo.sizing?.minHeight,
                height: vars.buttons.jumperGo.sizing?.minHeight,
            },
        },
    });

    const jumperButton = css({
        ...Mixins.button(vars.buttons.jumperGo),
        ...Mixins.margin({ left: 8 }),
    });

    const jumperForm = css({ display: "contents" });

    return {
        root,
        resultCount,
        pageNumber,
        iconButton,
        nextPageWrapper,
        nextPageButton,
        jumperInput,
        jumperButton,
        jumperForm,
    };
});
