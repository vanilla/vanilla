/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { getRequiredID, IRequiredComponentID } from "@library/utility/idUtils";
import classNames from "classnames";
import { IFieldError } from "@library/@types/api/core";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";

interface IProps extends IRequiredComponentID {
    className?: string;
    errors?: IFieldError[];
}

interface IState {
    id: string;
}

export default class ErrorMessages extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "errorMessages") as string,
        };
    }

    public render() {
        const { errors } = this.props;
        const classesInputBlock = inputBlockClasses();
        if (errors && errors.length > 0) {
            const componentClasses = classNames(classesInputBlock.errors, this.props.className);

            const errorList = (errors as any).map((error: any, index) => {
                return (
                    <span key={index} className={classesInputBlock.error}>
                        {error.message}
                    </span>
                );
            });

            return (
                <span id={this.state.id} className={componentClasses}>
                    {errorList}
                </span>
            );
        } else {
            return null;
        }
    }
}
