import { css } from "@emotion/css";
import { useThemeCache } from "@library/styles/styleUtils";

export default useThemeCache(() => {
    const layoutOptionsDropdown = css({
        marginRight: 8,
    });

    return {
        layoutOptionsDropdown,
    };
});
