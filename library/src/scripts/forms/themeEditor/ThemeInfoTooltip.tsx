/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { WarningIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";

interface IProps extends Omit<React.ComponentProps<typeof ToolTip>, "children"> {}

export function ThemeInfoTooltip(props: IProps) {
    const classes = themeBuilderClasses();
    return (
        <span className={classes.tooltip}>
            <ToolTip {...props}>
                <ToolTipIcon>
                    <WarningIcon warningMessage={t("Info")} />
                </ToolTipIcon>
            </ToolTip>
        </span>
    );
}
