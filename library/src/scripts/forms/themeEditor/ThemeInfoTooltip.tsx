/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { WarningIcon, InformationIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import classNames from "classnames";

interface IProps extends Omit<React.ComponentProps<typeof ToolTip>, "children"> {
    small?: boolean;
}

export function ThemeInfoTooltip(props: IProps) {
    const classes = themeBuilderClasses();
    return (
        <span className={classNames(classes.tooltip, { [classes.small]: props.small })}>
            <ToolTip {...props}>
                <ToolTipIcon>
                    <InformationIcon informationMessage={t("Info")} />
                </ToolTipIcon>
            </ToolTip>
        </span>
    );
}
