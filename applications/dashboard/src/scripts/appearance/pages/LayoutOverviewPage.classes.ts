import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { useThemeCache } from "@library/styles/styleUtils";

export default useThemeCache(() => {
    const layoutOptionsDropdown = css({
        marginRight: 8,
    });

    const dropdownItemLabel = css({
        display: "block",
        flexGrow: 1,
    });

    const overviewContent = css({
        padding: 0,
        margin: 0,
        maxWidth: "initial",
    });

    const titleLabel = css({
        ...Mixins.font({
            color: ColorsUtils.colorOut(globalVariables().elementaryColors.white),
            size: 10,
            transform: "uppercase",
        }),
        ...Mixins.padding({ horizontal: 4, vertical: 2 }),
        backgroundColor: "#f5296d",
        borderRadius: 2,
        marginTop: 2,
    });

    return {
        layoutOptionsDropdown,
        dropdownItemLabel,
        overviewContent,
        titleLabel,
    };
});
