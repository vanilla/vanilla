import React, { ComponentType } from "react";
import { IUser } from "@library/@types/api/users";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import {
    UserManagementColumnNames,
    UserManagementTableColumnName,
} from "@dashboard/users/userManagement/UserManagementUtils";
import ProfileLink from "@library/navigation/ProfileLink";
import DateTime from "@library/content/DateTime";
import { t } from "@vanilla/i18n";
import { useUserManagement } from "@dashboard/users/userManagement/UserManagementContext";
import { IGetUsersQueryParams } from "@dashboard/users/userManagement/UserManagement.hooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { StackableTableColumnsConfig } from "@dashboard/tables/StackableTable/StackableTable";
import DashboardAddEditUser from "@dashboard/users/userManagement/dashboardAddEditUser/DashboardAddEditUser";
import LinkAsButton from "@library/routing/LinkAsButton";
import { DeleteIcon } from "@library/icons/common";
import UserManagementSpoof from "@dashboard/users/userManagement/UserManagementSpoof";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { cx } from "@emotion/css";
import { ProfileField, ProfileFieldDataType } from "@dashboard/userProfiles/types/UserProfiles.types";
import TruncatedText from "@library/content/TruncatedText";
import { CollapsableContent } from "@library/content/CollapsableContent";

interface IProps {
    data: IUser;
    columnName: UserManagementTableColumnName;
    wrappedVersion?: boolean;
    updateQuery?: (newQueryParams: IGetUsersQueryParams) => void;
}

export default function UserManagementTableCell(props: IProps) {
    const { data: userData, columnName, wrappedVersion, updateQuery } = props;
    const classes = userManagementClasses();
    const { permissions, RanksWrapperComponent, profileFields } = useUserManagement();

    switch (columnName) {
        case UserManagementColumnNames.USER_NAME:
            return <UserNameCell wrappedVersion={wrappedVersion} data={userData} />;
        case UserManagementColumnNames.ROLES:
            return <RolesCell wrappedVersion={wrappedVersion} data={userData} updateQuery={updateQuery} />;

        case UserManagementColumnNames.FIRST_VISIT:
            return (
                <RegularCell
                    label="First Visit"
                    permission={true}
                    wrappedVersion={wrappedVersion}
                    component={userData.dateInserted ? <DateTime timestamp={userData.dateInserted || ""} /> : <></>}
                />
            );

        case UserManagementColumnNames.LAST_VISIT:
            return (
                <RegularCell
                    label="Last Visit"
                    permission={true}
                    wrappedVersion={wrappedVersion}
                    component={userData.dateLastActive ? <DateTime timestamp={userData.dateLastActive || ""} /> : <></>}
                />
            );
        case UserManagementColumnNames.LAST_IP:
            return (
                <RegularCell
                    label="Last IP"
                    permission={permissions.canViewPersonalInfo}
                    wrappedVersion={wrappedVersion}
                    content={userData.lastIPAddress}
                />
            );
        case UserManagementColumnNames.REGISTER_IP:
            return (
                <RegularCell
                    label="Register IP"
                    permission={permissions.canViewPersonalInfo}
                    wrappedVersion={wrappedVersion}
                    content={userData.insertIPAddress}
                />
            );
        case UserManagementColumnNames.USER_ID:
            return (
                <RegularCell
                    label="User ID"
                    permission={permissions.canViewPersonalInfo}
                    wrappedVersion={wrappedVersion}
                    content={userData.userID}
                    alignRight
                />
            );
        case UserManagementColumnNames.RANK:
            return (
                <ConditionalWrap
                    condition={Boolean(RanksWrapperComponent)}
                    component={RanksWrapperComponent as ComponentType<any>}
                >
                    <RankCell {...props} />
                </ConditionalWrap>
            );
        case UserManagementColumnNames.POSTS:
            return (
                <RegularCell
                    label="Posts"
                    permission={permissions.canViewPersonalInfo}
                    wrappedVersion={wrappedVersion}
                    content={userData.countPosts}
                    alignRight
                />
            );
        case UserManagementColumnNames.POINTS:
            return (
                <RegularCell
                    label="Points"
                    permission={permissions.canViewPersonalInfo}
                    wrappedVersion={wrappedVersion}
                    content={userData.points}
                    alignRight
                />
            );
        //profile fields
        default:
            return (
                <ProfileFieldCell
                    userProfileFields={userData.profileFields}
                    profileFields={profileFields}
                    wrappedVersion={wrappedVersion}
                    columnName={columnName}
                />
            );
    }
}

interface IUserNameCellProps
    extends Omit<React.ComponentProps<typeof UserManagementTableCell>, "updateQuery" | "columnName"> {}

const UserNameCell = (props: IUserNameCellProps) => {
    const { data: userData, wrappedVersion } = props;
    const classes = userManagementClasses();
    return (
        <div className={classes.userName}>
            {!wrappedVersion && (
                <>
                    <ProfileLink userFragment={userData} isUserCard>
                        {/* intentionally not using <img/> or <UserPhoto/>  here, to avoid flashing these when changing the query*/}
                        <div
                            className={classes.userPhoto}
                            style={{
                                backgroundImage: `url(${userData.photoUrl})`,
                            }}
                        ></div>
                    </ProfileLink>

                    <div className={cx(classes.userNameAndEmail, classes.bottomSpace)}>
                        <ProfileLink userFragment={userData}>{userData.name}</ProfileLink>
                        <span>{userData.email}</span>
                    </div>
                </>
            )}
            {wrappedVersion && (
                <div className={classes.userNameAndEmail}>
                    <div>
                        <span className={classes.wrappedColumnLabel}>{`${t("Username")}: `}</span>
                        <span>{userData.name}</span>
                    </div>
                    <div>
                        <span className={classes.wrappedColumnLabel}>{`${t("Email")}: `}</span>
                        <span>{userData.email}</span>
                    </div>
                </div>
            )}
        </div>
    );
};

interface IRolesCellProps extends Omit<React.ComponentProps<typeof UserManagementTableCell>, "columnName"> {}

const RolesCell = (props: IRolesCellProps) => {
    const { data: userData, wrappedVersion, updateQuery } = props;
    const classes = userManagementClasses();
    return (
        <div className={classes.multipleValuesCellContent}>
            {wrappedVersion && <span className={classes.wrappedColumnLabel}>{`${t("Roles")}: `}</span>}
            {userData.roles.map((role, key) => (
                <div key={key}>
                    {!wrappedVersion && (
                        <Button
                            buttonType={ButtonTypes.DASHBOARD_LINK}
                            onClick={() => {
                                updateQuery && updateQuery({ roleID: role.roleID });
                            }}
                            className={cx(classes.roleAsButton, {
                                [classes.smallLineHeight]: userData.roles && userData.roles.length > 1,
                            })}
                        >
                            <span>{role.name}</span>
                        </Button>
                    )}
                    {wrappedVersion && <span>{role.name}</span>}
                    {key < userData.roles.length - 1 && <span>,</span>}
                </div>
            ))}
        </div>
    );
};

interface IRegularCellProps {
    permission: boolean;
    label: string;
    wrappedVersion?: boolean;
    content?: string | number;
    component?: React.ReactNode;
    alignRight?: boolean;
}

export function RegularCell(props: IRegularCellProps) {
    const { label, content, component, wrappedVersion, permission } = props;
    const classes = userManagementClasses();

    if (permission) {
        return (
            <div className={cx({ [classes.alignRight]: props.alignRight && !wrappedVersion })}>
                {wrappedVersion && <span className={classes.wrappedColumnLabel}>{`${t(label)}: `}</span>}
                {content ?? component ?? ""}
            </div>
        );
    }

    return <></>;
}

interface IRankCellProps extends Omit<React.ComponentProps<typeof UserManagementTableCell>, "updateQuery"> {
    allRanks?: Record<number, string>;
}

export function RankCell(props: IRankCellProps) {
    const { data: userData, wrappedVersion, allRanks } = props;
    const classes = userManagementClasses();

    if (allRanks && Object.keys(allRanks).length && userData.rankID) {
        return (
            <div>
                {wrappedVersion && <span className={classes.wrappedColumnLabel}>{`${t("Rank")}: `}</span>}
                {allRanks[userData.rankID]}
            </div>
        );
    }
    return <></>;
}

interface IProfileFieldCellProps {
    columnName: string;
    profileFields?: ProfileField[];
    userProfileFields: any;
    wrappedVersion?: boolean;
}

export function ProfileFieldCell(props: IProfileFieldCellProps) {
    const { profileFields, userProfileFields, columnName, wrappedVersion } = props;
    const classes = userManagementClasses();
    const profileFieldByColumn = profileFields?.filter((field) => field.label === columnName);

    if (
        profileFieldByColumn &&
        profileFieldByColumn.length &&
        userProfileFields &&
        Object.keys(userProfileFields).length &&
        userProfileFields[profileFieldByColumn[0].apiName]
    ) {
        const userProfileFieldValue = userProfileFields[profileFieldByColumn[0].apiName];
        const dataType = profileFieldByColumn[0].dataType;

        let content = userProfileFieldValue ? (
            wrappedVersion ? (
                <TruncatedText lines={1}>{userProfileFieldValue}</TruncatedText>
            ) : (
                <CollapsableContent maxHeight={60}>{userProfileFieldValue}</CollapsableContent>
            )
        ) : (
            <></>
        );

        switch (dataType) {
            // checkbox
            case ProfileFieldDataType.BOOLEAN:
                content =
                    typeof userProfileFieldValue === "boolean" ? (
                        <span>{userProfileFieldValue === true ? t("Yes") : t("No")}</span>
                    ) : (
                        <></>
                    );
                break;
            case ProfileFieldDataType.DATE:
                content = userProfileFieldValue ? <DateTime timestamp={userProfileFieldValue || ""} /> : <></>;
                break;
            case ProfileFieldDataType.STRING_MUL:
            case ProfileFieldDataType.NUMBER_MUL:
                content = userProfileFieldValue ? (
                    userProfileFieldValue.map((value, key) => (
                        <div key={key}>
                            <span>{value}</span>
                            {key < userProfileFieldValue.length - 1 && <span>,</span>}
                        </div>
                    ))
                ) : (
                    <></>
                );
                break;
        }

        return (
            <div
                className={cx(classes.multipleValuesCellContent, {
                    [classes.alignRight]: dataType === ProfileFieldDataType.NUMBER && !wrappedVersion,
                })}
            >
                {wrappedVersion && <span className={classes.wrappedColumnLabel}>{`${columnName}: `}</span>}
                {content}
            </div>
        );
    }

    return <></>;
}

interface IWrappedCellProps {
    configuration: StackableTableColumnsConfig;
    orderedColumns: UserManagementTableColumnName[];
    data: IUser;
}

export function WrappedCell(props: IWrappedCellProps) {
    const { data: userData, configuration, orderedColumns } = props;
    let content = <></>;
    orderedColumns.forEach((columnName, index) => {
        if (!configuration[columnName].isHidden && configuration[columnName].wrapped) {
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

export function ActionsCell(props: { data: IUser }) {
    const classes = userManagementClasses();
    const { permissions, currentUserID, RanksWrapperComponent, ...rest } = useUserManagement();
    const { canEditUsers, canDeleteUsers, canSpoofUsers } = permissions;
    const notSelfOrSystem = currentUserID !== props.data?.userID && !props.data.isSysAdmin;

    // we need to do a bit of adjustment here for userAddEdit form role dropdown
    const userData = {
        ...props.data,
        roles: Object.fromEntries(props.data.roles.map((roleObject) => [Number(roleObject.roleID), roleObject.name])),
    };

    return (
        <div className={cx(classes.actionButtons, classes.alignRight)} data-testid="action-buttons-container">
            {canEditUsers && (
                <ConditionalWrap
                    condition={Boolean(RanksWrapperComponent)}
                    component={RanksWrapperComponent as ComponentType<any>}
                >
                    <DashboardAddEditUser {...rest} userData={userData} newUserManagement />
                </ConditionalWrap>
            )}
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
