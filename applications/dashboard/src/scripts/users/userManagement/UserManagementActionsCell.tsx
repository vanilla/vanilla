import React from "react";
import { IUser } from "@library/@types/api/users";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import LinkAsButton from "@library/routing/LinkAsButton";
import { DeleteIcon } from "@library/icons/common";
import UserManagementSpoof from "@dashboard/users/userManagement/UserManagementSpoof";

export default function UserManagementActionsCell(props: { data: IUser }) {
    const classes = userManagementClasses();
    const { permissions, currentUserID, ...rest } = useUserManagement();
    const { canEditUsers, canDeleteUsers, canSpoofUsers } = permissions;
    const notSelfOrSystem = currentUserID !== props.data?.userID && !props.data.isSysAdmin;

    // we need to do a bit of adjustment here for userAddEdit form role dropdown
    const userData = {
        ...props.data,
        roles: Object.fromEntries(props.data.roles.map((roleObject) => [Number(roleObject.roleID), roleObject.name])),
    };

    return (
        <div className={classes.actionButtons} data-testid="action-buttons-container">
            {canEditUsers && <DashboardAddEditUser {...rest} userData={userData} newUserManagement />}
            {canDeleteUsers && notSelfOrSystem && (
                <LinkAsButton
                    to={`/user/delete/${userData.userID}`}
                    buttonType={ButtonTypes.ICON}
                    className={classes.deleteIcon}
                >
                    <DeleteIcon />
                </LinkAsButton>
            )}
            {canSpoofUsers && (
                <UserManagementSpoof userID={userData.userID} isSysAdmin={userData.isSysAdmin} name={userData.name} />
            )}
        </div>
    );
}
