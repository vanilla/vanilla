/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { accountSettingsClasses } from "@library/accountSettings/AccountSettings.classes";

export enum AccountSettingType {
    USERNAME = "username",
    EMAIL = "email",
    PASSWORD = "password",
}

interface IProps {
    label: ReactNode;
    value?: ReactNode;
    afterLabel?: ReactNode;
    afterValue?: ReactNode;
}

/**
 * A generic component to layout label and value
 * pairs on the Account Setting Page
 */
export function AccountSettingsDetail(props: IProps) {
    const { label, value, afterValue, afterLabel } = props;
    const classes = accountSettingsClasses();

    return (
        <div className={classes.infoRow}>
            <p className={classes.infoLabel}>
                {label}
                {afterLabel}
            </p>
            <div className={classes.infoDetail}>
                {value}
                {afterValue}
            </div>
        </div>
    );
}
