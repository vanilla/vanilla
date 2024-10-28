/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useThemeCache } from "@library/styles/themeCache";

const ThreadItemClasses = useThemeCache((headerHasUserPhoto = false) => {
    const globalVars = globalVariables();

    const threadItemContainer = css({
        position: "relative",
        container: "threadItemContainer / inline-size",
    });

    const userContent = css({
        ...Mixins.padding({
            top: headerHasUserPhoto ? 12 : 2,
        }),
    });

    const resultWrapper = css({ display: "flex", gap: 12 });

    const attachmentsContentWrapper = css({
        ...Mixins.margin({ top: globalVars.gutter.size }),
        "&:empty": {
            display: "none",
        },
    });

    const footerWrapper = css({
        display: "grid",
        width: "100%",
        gridTemplateColumns: "auto 1fr 50px",
        gridTemplateRows: "1fr",
        gap: 8,
        "@container threadItemContainer (width < 516px)": {
            gridTemplateColumns: "1fr 50px",
            gridTemplateRows: "repeat(2, auto)",
        },

        marginBlockStart: globalVars.gutter.size,
        marginInlineEnd: 10,
    });

    const replyButton = css({
        alignSelf: "end",
        justifySelf: "end",
        "@container threadItemContainer (width < 516px)": {
            gridColumn: "2 /3",
            gridRow: "2 / 3",
        },
    });

    return {
        threadItemContainer,
        userContent,
        resultWrapper,
        attachmentsContentWrapper,
        replyButton,
        footerWrapper,
    };
});

export default ThreadItemClasses;
