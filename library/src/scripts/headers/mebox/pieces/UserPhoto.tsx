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
import { accessibleLabel, t } from "@library/utility/appUtils";
import { LoadingCircle } from "@library/loaders/LoadingRectangle";

export enum UserPhotoSize {
    SMALL = "small",
    MEDIUM = "medium",
    LARGE = "large",
    XLARGE = "xlarge",
}

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    className?: string;
    size?: UserPhotoSize;
    styleType?: UserIconTypes;
    userInfo?: Partial<IUserFragment>;
}

/**
 * Implements User Photo Component
 */
export function UserPhoto(props: IProps) {
    const { className, userInfo = {}, size, styleType, ...spreadProps } = props;
    const { name = t("Unknown"), photoUrl } = userInfo;
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
        <div {...spreadProps} className={classNames(className, sizeClass, classes.root, { isOpen: open })}>
            {!badImage ? (
                <img
                    {...commonProps}
                    height="200"
                    width="200"
                    src={photoUrl}
                    className={classNames(classes.photo)}
                    onError={(e) => {
                        setBadImage(true);
                    }}
                    loading="lazy"
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

export function UserPhotoSkeleton(props: { className?: string; size?: UserPhotoSize }) {
    const classes = userPhotoClasses();
    let sizeClass = classes.small;
    switch (props.size) {
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
    return (
        <div className={classNames(props.className, sizeClass, classes.root)}>
            <LoadingCircle height={50} className={classNames(classes.photo, classes.noPhoto)} />
        </div>
    );
}
