/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { visibility } from "@library/styles/styleHelpers";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import React from "react";
import addonPlaceHolder from "../../../design/images/addon-placeholder.png"; // This might need a refactor

interface ICommonProps {
    iconUrl?: string;
    title: string;
    description?: string;
    isEnabled: boolean;
    onChange(newState: boolean): void;
    className?: string;
    disabled?: boolean;
    disabledNote?: string;
}

export type IProps = ICommonProps &
    (
        | {
              action(event: any): any;
              actionLabel: string;
              actionIcon: React.ReactNode | string;
          }
        | {
              action?: false;
              actionLabel?: never;
              actionIcon?: never;
          }
    );

export const DashboardMediaAddonListItem = (props: IProps) => {
    const {
        iconUrl,
        title,
        description,
        isEnabled,
        onChange,
        className,
        action,
        actionIcon,
        actionLabel,
        disabled,
        disabledNote,
    } = props;
    const classes = dashboardClasses();

    return (
        <li className={cx(classes.mediaAddonListItem, classes.extendBottomBorder, className)}>
            <div className="mediaAddonListItem_icon">
                <img src={iconUrl ? iconUrl : addonPlaceHolder} />
            </div>
            <div className="mediaAddonListItem_details">
                <h3>{title}</h3>
                {/* This HTML is returned from the API, the API is responsible for HTML sanitization */}
                {description && <span dangerouslySetInnerHTML={{ __html: description }} />}
            </div>
            {action && (
                <div className="mediaAddonListItem_config">
                    <Button onClick={action} ariaLabel={t("Configure")} buttonType={ButtonTypes.ICON_COMPACT}>
                        {actionIcon}
                        <span className={visibility().visuallyHidden}>{actionLabel}</span>
                    </Button>
                </div>
            )}
            <div className="mediaAddonListItem_control">
                {disabled && disabledNote ? (
                    <ToolTip label={disabledNote}>
                        <span>
                            <DashboardToggle checked={isEnabled} onChange={onChange} disabled={disabled} />
                        </span>
                    </ToolTip>
                ) : (
                    <DashboardToggle checked={isEnabled} onChange={onChange} disabled={disabled} />
                )}
            </div>
        </li>
    );
};
