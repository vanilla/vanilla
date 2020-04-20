/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { InformationIcon, DocumentationIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";
import { themeBuilderClasses } from "@library/forms/themeEditor/ThemeBuilder.styles";
import classNames from "classnames";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps extends Omit<React.ComponentProps<typeof ToolTip>, "children"> {
    small?: boolean;
    href?: string;
}

export function ThemeInfoTooltip(props: IProps) {
    const classes = themeBuilderClasses();

    let icon = <InformationIcon informationMessage={t("Info")} />;
    if (props.href) {
        icon = (
            <SmartLink className={classes.documentationIconLink} to={props.href}>
                <DocumentationIcon />
            </SmartLink>
        );
    }

    return (
        <span className={classNames(classes.blockInfo, { [classes.small]: props.small })}>
            <ToolTip {...props}>
                <ToolTipIcon>{icon}</ToolTipIcon>
            </ToolTip>
        </span>
    );
}
