import React from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";

interface IProps {
    leftCell: React.ReactNode;
    rightCell: React.ReactNode;
    isFirst: boolean;
    isLast: boolean;
    className?: string;
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGridRow(props: IProps) {
    const { leftCell, rightCell, isFirst, isLast } = props;
    const classes = translationGridClasses();
    return (
        <div
            className={classNames(classes.row, props.className, {
                [classes.isFirst]: isFirst,
                [classes.isLast]: isLast,
            })}
        >
            <div className={classNames(classes.leftCell)}>{leftCell}</div>
            <div className={classNames(classes.rightCell)}>{rightCell}</div>
        </div>
    );
}
