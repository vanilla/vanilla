/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { IUser, IUserFragment } from "@library/@types/api/users";
import { userPhotoClasses } from "@library/headers/mebox/pieces/userPhotoStyles";
import classNames from "classnames";
import { UserIcon, UserIconTypes } from "@library/icons/titleBar";
import { accessibleLabel } from "@library/utility/appUtils";

export enum UserPhotoSize {
    SMALL = "small",
    MEDIUM = "medium",
    LARGE = "large",
    XLARGE = "xlarge",
}

interface IProps {
    className?: string;
    size?: UserPhotoSize;
    styleType?: UserIconTypes;
    userInfo: IUserFragment;
}

/**
 * Implements User Photo Component
 */
export function UserPhoto(props: IProps) {
    const { className, userInfo = {} as IUserFragment, size, styleType } = props;
    const { name, photoUrl } = userInfo;
    const classes = userPhotoClasses();
    const [badImage, setBadImage] = useState(!photoUrl);

    let sizeClass = classes.small;
    switch (size) {
        case UserPhotoSize.XLARGE:
            sizeClass = classes.xlarge;
            break;
        case UserPhotoSize.LARGE:
            sizeClass = classes.large;
            break;
        case UserPhotoSize.MEDIUM:
            sizeClass = classes.medium;
            break;
    }

    const commonProps = {
        title: name,
        alt: accessibleLabel(`User: "%s"`, [name]),
    };

    return (
        <div className={classNames(className, sizeClass, classes.root, { isOpen: open })}>
            {!badImage ? (
                <img
                    {...commonProps}
                    src={photoUrl}
                    className={classNames(classes.photo)}
                    onError={(e) => {
                        setBadImage(true);
                    }}
                />
            ) : (
                <UserIcon
                    {...commonProps}
                    styleType={styleType}
                    className={classNames(classes.photo, classes.noPhoto)}
                />
            )}
        </div>
    );
}
