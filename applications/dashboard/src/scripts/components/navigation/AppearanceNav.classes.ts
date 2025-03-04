import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";

export default useThemeCache(() => {
    const root = css({
        flex: 1,
        width: "100%",
    });

    return {
        root,
    };
});
