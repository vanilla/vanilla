import React from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";

/**
 * Display user role(s)
 */

interface IProps extends IUserRoles {
    maxRoleCount?: number;
    wrapper?: boolean;
    classNane?: string;
    roleClass?: string;
}

export function Roles(props: IProps) {
    const { roles, maxRoleCount = 1, wrapper = true } = props;

    const classesMeta = metasClasses();
    const classes = rolesClasses();

    const userRoles = roles.map((r, i) => {
        if (i < maxRoleCount) {
            return (
                <span key={i} className={classNames(classesMeta.meta, classes.role)}>
                    {r.name}
                </span>
            );
        }
    });

    return (
        <ConditionalWrap condition={wrapper} className={classNames(classesMeta.root, "isFlexed")}>
            {userRoles}
        </ConditionalWrap>
    );
}
