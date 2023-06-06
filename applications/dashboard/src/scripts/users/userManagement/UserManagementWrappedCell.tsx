import React from "react";
import { IUser } from "@library/@types/api/users";
import { UserManagementTableColumnName } from "@dashboard/users/userManagement/UserManagementUtils";
import UserManagementTableCell from "@dashboard/users/userManagement/UserManagementCell";
import { StackableTableColumnsConfig } from "@dashboard/tables/StackableTable/StackableTable";

interface IProps {
    configuration: StackableTableColumnsConfig;
    orderedColumns: UserManagementTableColumnName[];
    data: IUser;
}

export default function UserManagementWrappedCell(props: IProps) {
    const { data: userData, configuration, orderedColumns } = props;
    let content = <></>;
    orderedColumns.forEach((columnName, index) => {
        if (!configuration[columnName].hidden && configuration[columnName].wrapped) {
            content = (
                <>
                    {index !== 0 && content}
                    <UserManagementTableCell columnName={columnName} data={userData} wrappedVersion />
                </>
            );
        }
    });

    return content;
}
