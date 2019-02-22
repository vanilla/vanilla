/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { user } from "@library/components/icons/header";
import classNames from "classnames";
import { IUserFragment } from "@library/@types/api";
import { userPhotoClasses } from "@library/styles/userPhotoStyles";

export enum UserPhotoSize {
    SMALL = "small",
    MEDIUM = "medium",
    LARGE = "large",
}

interface IProps {
    className?: string;
    size?: UserPhotoSize;
    open?: boolean; // Only useful when using as dropdown button with SVG.
    userInfo: IUserFragment;
}

/**
 * Implements User Photo Component
 */
export class UserPhoto extends React.Component<IProps> {
    public render() {
        const { className, userInfo } = this.props;
        const photoUrl = userInfo ? userInfo.photoUrl : null;
        const name = userInfo ? userInfo.name : null;
        const open = !!this.props.open;
        const classes = userPhotoClasses();
        let sizeClass = classes.small;
        switch (this.props.size) {
            case UserPhotoSize.LARGE:
                sizeClass = classes.large;
                break;
            case UserPhotoSize.MEDIUM:
                sizeClass = classes.medium;
                break;
        }

        return (
            <div className={classNames("userPhoto", className, sizeClass, classes.root, { isOpen: open })}>
                {!!photoUrl && (
                    <img src={photoUrl} alt={name || ""} className={classNames("userPhoto-photo", classes.photo)} />
                )}
                {!photoUrl && user(open, classNames("userPhoto-photo", classes.photo))}
            </div>
        );
    }
}
