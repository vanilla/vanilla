/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import { NoMinHeight } from "@library/layout/Section.story";
import { globalVariables } from "@library/styles/globalStyleVars";
import { Mixins } from "@library/styles/Mixins";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { styleUnit } from "@library/styles/styleUnit";
import { useThemeCache } from "@library/styles/themeCache";
import { Variables } from "@library/styles/Variables";

export const layoutOverviewClasses = useThemeCache(() => {
    const fauxWidget = css({
        position: "relative",
        display: "flex",
        flexDirection: "column",
        ...Mixins.box(
            Variables.box({
                borderType: BorderType.SHADOW,
            }),
        ),
        minHeight: styleUnit(80),
    });

    const fauxWidgetFullWidth = css({
        position: "relative",
        display: "flex",
        flexDirection: "column",
        minHeight: styleUnit(200),
        background: "#f5f5f5",
    });

    const fauxWidgetContent = css({
        display: "flex",
        width: "100%",
        height: "100%",
        flex: 1,
        alignItems: "center",
        justifyContent: "space-around",
        userSelect: "none",
        "& p": {
            fontWeight: "bold",
            width: "100%",
            textAlign: "center",
            fontFamily: globalVariables().fonts.families.monospace,
        },
    });

    return { fauxWidget, fauxWidgetFullWidth, fauxWidgetContent };
});
