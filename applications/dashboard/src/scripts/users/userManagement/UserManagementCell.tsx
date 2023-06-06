import React from "react";
import { IUser } from "@library/@types/api/users";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { UserManagementTableColumnName } from "@dashboard/users/userManagement/UserManagementUtils";
import ProfileLink from "@library/navigation/ProfileLink";
import DateTime from "@library/content/DateTime";
import { t } from "@vanilla/i18n";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IProps {
    data: IUser;
    columnName: UserManagementTableColumnName;
    wrappedVersion?: boolean;
    updateQuery?: (newQueryParams: IGetUsersQueryParams) => void;
}

export default function UserManagementCell(props: IProps) {
    const { data: userData, columnName, wrappedVersion, updateQuery } = props;
    const classes = userManagementClasses();
    const { permissions } = useUserManagement();

    switch (columnName) {
        case "username":
            return (
                <div className={classes.userName}>
                    <ProfileLink userFragment={userData} isUserCard>
                        {/* intentionally not using <img/> or <UserPhoto/>  here, to avoid flashing these when changing the query*/}
                        <div
                            className={classes.userPhoto}
                            style={{
                                backgroundImage: `url(${userData.photoUrl})`,
                            }}
                        ></div>
                    </ProfileLink>

                    <div className={classes.userNameAndEmail}>
                        <ProfileLink userFragment={userData}>{userData.name}</ProfileLink>
                        <span>{userData.email}</span>
                    </div>
                </div>
            );
        case "roles":
            return (
                <div>
                    {wrappedVersion && <span className={classes.wrappedColumnLabel}>{t("Roles: ")}</span>}
                    {userData.roles.map((role, key) => (
                        <span key={key}>
                            {!wrappedVersion && (
                                <Button
                                    buttonType={ButtonTypes.DASHBOARD_LINK}
                                    onClick={() => {
                                        updateQuery && updateQuery({ roleID: role.roleID });
                                    }}
                                    className={classes.roleAsButton}
                                >
                                    <span>{role.name}</span>
                                </Button>
                            )}
                            {wrappedVersion && <span>{role.name}</span>}
                            {key < userData.roles.length - 1 && <span>,</span>}
                        </span>
                    ))}
                </div>
            );

        case "first visit":
            return (
                <div>
                    {wrappedVersion && <span className={classes.wrappedColumnLabel}>{t("First Visit: ")}</span>}
                    {userData.dateInserted ? <DateTime timestamp={userData.dateInserted || ""} /> : "-"}
                </div>
            );
        case "last visit":
            return (
                <div>
                    {wrappedVersion && <span className={classes.wrappedColumnLabel}>{t("Last Visit: ")}</span>}
                    {userData.dateLastActive ? <DateTime timestamp={userData.dateLastActive || ""} /> : "-"}
                </div>
            );
        case "last ip":
            return (
                <div>
                    {wrappedVersion && <span className={classes.wrappedColumnLabel}>{t("Last IP: ")}</span>}
                    {permissions.canViewPersonalInfo ? userData.lastIPAddress : ""}
                </div>
            );
    }
}
