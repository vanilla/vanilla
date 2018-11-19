/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import { IInjectableUserState } from "@library/users/UsersModel";
import { connect } from "react-redux";
import UsersModel from "@library/users/UsersModel";

export interface IProps extends IInjectableUserState {
    className?: string;
    photoSize?: UserPhotoSize;
}

/**
 * Generic wrap for items in DropDownMenu
 */
export class DropDownUserCard extends React.Component<IProps> {
    public render() {
        const currentUser = this.props.currentUser.data!;
        return (
            <li className={classNames("dropDown-userCard", this.props.className)}>
                <UserPhoto
                    className="userDropDown-userCardPhoto"
                    userInfo={currentUser}
                    size={this.props.photoSize || UserPhotoSize.LARGE}
                />
                <div className="userDropDown-userCardName">{currentUser.name}</div>
            </li>
        );
    }
}
const withRedux = connect(UsersModel.mapStateToProps);
export default withRedux(DropDownUserCard);
