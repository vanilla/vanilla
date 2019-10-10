import React from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";

interface IProps {
    text: string;
    className?: string;
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGridText(props: IProps) {
    const classes = translationGridClasses();
    const { text } = props;
    return <div className={classNames(classes.text, props.className)}>{text}</div>;
}
