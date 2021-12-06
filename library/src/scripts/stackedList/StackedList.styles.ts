import { css, cx } from "@emotion/css";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { Mixins } from "@library/styles/Mixins";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";

export const stackedListClasses = useThemeCache((vars: ReturnType<typeof stackedListVariables>) => {
    const { sizing, plus } = vars;

    const item = css({
        position: "relative",
        zIndex: 1,
        width: styleUnit(sizing.width - sizing.offset),

        transitionDelay: "0.05s",
        ":hover, :focus, :focus-within, :active": {
            transform: "scale(1.16)",
            zIndex: 2,
        },
    });

    const lastItem = cx(
        item,
        css({
            width: "auto",
        }),
    );

    const root = css({
        display: "flex",
        flexWrap: "wrap",
        alignItems: "center",
        paddingRight: styleUnit(sizing.offset), // prevents photos from overflowing
    });

    const plusLink = css({
        marginLeft: styleUnit(plus.margin),
        ...Mixins.font(plus.font),
        lineHeight: styleUnit(sizing.width),
    });

    return {
        root,
        item,
        lastItem,
        plusLink,
    };
});
