import React from "react";
import { IUser, IUserRoles } from "@library/@types/api/users";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { metasClasses } from "@library/styles/metasStyles";
import { rolesClasses } from "@library/content/rolesStyles";
import classNames from "classnames";
import InputTextBlock from "@library/forms/InputTextBlock";
import { translationGridClasses } from "@library/content/translationGrid/TranslationGridStyles";

interface IProps {
    multiline: boolean;
    text: string;
    className?: string;
}

/**
 * Translation UI
 * @param props
 * @constructor
 */

export function TranslationGridInput(props: IProps) {
    const classes = translationGridClasses();
    return (
        <InputTextBlock
            className={props.className}
            inputProps={{ defaultValue: props.text, multiline: props.multiline }}
        />
    );
}
