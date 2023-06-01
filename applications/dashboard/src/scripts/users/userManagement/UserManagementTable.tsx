/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { IUser } from "@library/@types/api/users";
import DateTime from "@library/content/DateTime";
import { IRole } from "@dashboard/roles/roleTypes";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useUserManagement } from "./UserManagementContext";
import { DeleteIcon } from "@library/icons/common";
import { DashboardTable } from "@dashboard/tables/DashboardTable";

interface IProps {
    data: {
        users: IUser[];
        countUsers: string;
        currentPage: string;
    };
}

export default function UserManagementTable(props: IProps) {
    const { data } = props;
    const classes = userManagementClasses();
    const { permissions, ...rest } = useUserManagement();
    const { canEditUsers, canDeleteUsers, canViewPersonalInfo } = permissions;

    const header = (
        <div style={{ fontWeight: "bold" }} className={classes.userRow}>
            <span>USERNAME</span>
            <span>ROLES</span>
            <span>FIRST VISIT</span>
            <span>LAST VISIT</span>
            <span>LAST IP</span>
            <span>ACTIONS</span>
        </div>
    );

    return (
        <>
            {header}
            {data.users.map((userData: IUser, key) => {
                const userDataForEdit: Omit<Partial<IUser>, "roles"> & {
                    roles: { [k: IRole["roleID"]]: IRole["name"] };
                } = {
                    ...userData,
                    roles: Object.fromEntries(
                        userData.roles.map((roleObject) => [Number(roleObject.roleID), roleObject.name]),
                    ),
                };

                //to be replaced with  <UserTableRow />
                return (
                    <div key={key} className={classes.userRow}>
                        <span>
                            {userData.name}
                            <br />
                            <span>{userData.email}</span>
                        </span>
                        <span>
                            {userData.roles.map((role, key) => (
                                <React.Fragment key={key}>
                                    <span>{role.name}</span>
                                    <br />
                                </React.Fragment>
                            ))}
                        </span>
                        <span>
                            <DateTime timestamp={userData.dateInserted || ""} />
                        </span>
                        <span>
                            {userData.dateLastActive ? <DateTime timestamp={userData.dateLastActive || ""} /> : "-"}
                        </span>
                        <span>{canViewPersonalInfo ? userData.lastIPAddress : ""}</span>
                        <span className={classes.actionButtons} data-testid="action-buttons-container">
                            {canEditUsers && (
                                <DashboardAddEditUser {...rest} userData={userDataForEdit} newUserManagement />
                            )}
                            {canDeleteUsers && (
                                <LinkAsButton
                                    to={`/user/delete/${userData.userID}`}
                                    buttonType={ButtonTypes.ICON}
                                    className={classes.deleteIcon}
                                >
                                    <DeleteIcon />
                                </LinkAsButton>
                            )}
                        </span>
                    </div>
                );
            })}
        </>
    );
}
