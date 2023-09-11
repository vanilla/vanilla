import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";

export const sortedTagCloudClasses = () => {
    const tokenGroup = css({
        display: "flex",
        flexWrap: "wrap",
        gap: 4,
        marginBottom: 10,
    });
    const token = css({
        ...Mixins.padding({ right: 4 }),
        maxWidth: "100%",
        "& > span": {
            flex: 1,
        },
    });
    const closeButton = css({
        ...Mixins.padding({ vertical: 0, left: 8, right: 4 }),
        width: 20,
    });
    const closeIcon = css({
        height: 8,
        width: 8,
        alignSelf: "center",
    });
    return {
        tokenGroup,
        token,
        closeButton,
        closeIcon,
    };
};
