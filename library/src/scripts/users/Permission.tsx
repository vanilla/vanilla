/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { connect } from "react-redux";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import { LoadStatus } from "@library/@types/api";
import UsersActions, { IInjectableUsersActions } from "@library/users/UsersActions";

interface IProps extends IInjectableUserState, IInjectableUsersActions {
    permission: string;
    children: React.ReactNode;
    fallback?: React.ReactNode;
}

export class Permission extends React.Component<IProps> {
    public render(): React.ReactNode {
        return this.hasPermission() ? this.props.children : this.props.fallback || null;
    }

    public componentDidMount() {
        if (this.props.currentUser.status === LoadStatus.PENDING) {
            void this.props.usersActions.getMe();
        }
    }

    private hasPermission(): boolean {
        const { currentUser, permission } = this.props;
        return (
            currentUser.status === LoadStatus.SUCCESS &&
            (currentUser.data.isAdmin || currentUser.data.permissions.includes(permission))
        );
    }
}

const withRedux = connect(
    UsersModel.mapStateToProps,
    UsersActions.mapDispatch,
);

export default withRedux(Permission);
