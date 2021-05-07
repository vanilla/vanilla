/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@vanilla/i18n";
import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IAuthenticator } from "@oauth2/AuthenticatorTypes";
import Button from "@library/forms/Button";
import { DeleteIcon, EditIcon } from "@library/icons/common";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";

interface IProps {
    authenticator: IAuthenticator;
    disableToggle?: boolean;
    onChangeActive(newValue: boolean): void;
    onEditClick(): void;
    onDeleteClick(): void;
}

export function AuthenticatorTableRow(props: IProps) {
    const { authenticator, disableToggle, onChangeActive, onEditClick, onDeleteClick } = props;
    const { clientID, name, default: isDefault, active: isActive } = authenticator;
    return (
        <tr>
            <td>
                <DashboardMediaItem title={name} info={isDefault ? t("Default") : ""} />
            </td>
            <td>{clientID}</td>
            <td>
                <DashboardTableOptions>
                    <Button className="btn-icon" onClick={onEditClick} buttonType={ButtonTypes.ICON_COMPACT}>
                        <EditIcon />
                    </Button>
                    <Button className="btn-icon" onClick={onDeleteClick} buttonType={ButtonTypes.ICON_COMPACT}>
                        <DeleteIcon />
                    </Button>
                    <DashboardToggle disabled={disableToggle} onChange={onChangeActive} checked={isActive} />
                </DashboardTableOptions>
            </td>
        </tr>
    );
}
