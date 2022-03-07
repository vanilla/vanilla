import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";

export default useThemeCache(() => {
    const layoutOptionsDropdown = css({
        marginRight: 8,
    });

    const overviewContent = css({
        padding: 0,
        margin: 0,
    });

    return {
        layoutOptionsDropdown,
        overviewContent,
    };
});
