/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React, { ReactNode } from "react";
import classNames from "classnames";
import { uniqueIDFromPrefix, getRequiredID, IRequiredComponentID } from "@dashboard/componentIDs";

interface IProps extends IRequiredComponentID {
    className?: string;
    errors: string | string[];
}

interface IState {
    id: string;
}

export default class ErrorMessages extends React.Component<IProps, IState> {
    public static defaultProps = {
        errors: [],
    };

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "errorMessages") as string,
        };
    }

    public render() {
        let errors = this.props.errors;
        if (typeof this.props.errors === "string") {
            errors = [this.props.errors];
        }

        if (errors.length > 0) {
            const componentClasses = classNames("inputBlock-errors", this.props.className);

            const errorList = (errors as any).map((error: any, index) => {
                return (
                    <span key={index} className="inputBlock-error">
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
