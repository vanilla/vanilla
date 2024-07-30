import { css } from "@emotion/css";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { useThemeCache } from "@library/styles/themeCache";

export const escalationClasses = useThemeCache(() => {
    const list = css({
        display: "flex",
        flexDirection: "column",
        gap: 16,
    });
    const listItem = css({
        ...shadowHelper().embed(),
        borderRadius: 6,
    });
    const title = css({
        fontWeight: 600,
        fontSize: 16,
        gap: 6,
        display: "flex",
        alignItems: "center",
    });
    const titleContents = css({
        color: "inherit",
    });
    const actions = css({});
    const listItemContainer = css({
        padding: "8px 16px",
        "&:empty": {
            display: "none",
        },
    });
    const actionBar = css({
        display: "flex",
        alignItems: "center",
        gap: 12,
        borderTop: singleBorder(),
        marginTop: 6,
        paddingTop: 0,
        paddingBottom: 2,
    });
    const moreActions = css({
        transform: "translateX(4px)",
    });

    return {
        list,
        listItem,
        title,
        titleContents,
        actions,
        listItemContainer,
        actionBar,
        moreActions,
    };
});
