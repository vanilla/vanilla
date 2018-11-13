/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { noUserPhoto } from "@library/components/icons/common";
import classNames from "classnames";
import { IUserFragment } from "@library/@types/api";

export enum UserPhotoSize {
    SMALL = "isSmall",
}

interface IProps {
    className?: string;
    size: UserPhotoSize;
    open?: boolean; // Only useful when using as dropdown button with SVG.
    userInfo: IUserFragment;
}

interface IState {
    open?: boolean; // When used as dropdown icon
}

/**
 * Implements Messages Drop down for header
 */
export class UserPhoto extends React.Component<IProps, IState> {
    public constructor(props) {
        super(props);
        this.state = {
            open: false,
        };
    }

    public render() {
        const { className, size, userInfo } = this.props;
        const photoUrl = userInfo ? userInfo.photoUrl : null;
        const userID = userInfo ? userInfo.userID : null;
        const name = userInfo ? userInfo.name : null;

        return (
            <div className={classNames("userPhoto", className, { isOpen: this.state.open })}>
                {!!photoUrl && <img src={photoUrl} alt={name || ""} className={classNames("userPhoto-photo", size)} />}
                {!photoUrl && noUserPhoto("userPhoto-photo")}
            </div>
        );
    }
}
