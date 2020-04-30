/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IOptionalComponentID, useUniqueID } from "@library/utility/idUtils";
import classNames from "classnames";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { capitalizeFirstLetter } from "@vanilla/utils";

interface IError {
    message: string;
    [key: string]: any;
}

interface IProps extends IOptionalComponentID {
    className?: string;
    errors?: IError[];
    padded?: boolean;
}

export default function ErrorMessages(props: IProps) {
    const ownID = useUniqueID("errorMessages");
    const id = props.id ?? ownID;
    const { errors, padded } = props;
    const classesInputBlock = inputBlockClasses();
    if (errors && errors.length > 0) {
        const componentClasses = classNames(
            classesInputBlock.errors,
            props.className,
            padded && classesInputBlock.errorsPadding,
        );

        const errorList = errors.map((error, index) => {
            return (
                <span key={index} className={classesInputBlock.error}>
                    {capitalizeFirstLetter(error.message)}
                </span>
            );
        });

        return (
            <span id={id} className={componentClasses}>
                {errorList}
            </span>
        );
    } else {
        return null;
    }
}
