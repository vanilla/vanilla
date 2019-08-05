/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import ErrorMessages from "@library/forms/ErrorMessages";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import classNames from "classnames";
import Paragraph from "@library/layout/Paragraph";
import { IFieldError } from "@library/@types/api/core";

export enum InputTextBlockBaseClass {
    STANDARD = "inputBlock",
    CUSTOM = "",
}

interface ICallbackProps {
    labelID: string;
    hasErrors: boolean;
    errorID: string;
}
type CallbackChildren = (props: ICallbackProps) => React.ReactNode;

export interface IInputBlockProps extends IOptionalComponentID {
    label: ReactNode;
    children: React.ReactNode | CallbackChildren;
    className?: string;
    labelClassName?: string;
    noteAfterInput?: string;
    labelNote?: string;
    labelID?: string;
    descriptionID?: string;
    errors?: IFieldError[];
    baseClass?: InputTextBlockBaseClass;
    legacyMode?: boolean;
}

interface IState {
    id: string;
}

export default class InputBlock extends React.Component<IInputBlockProps, IState> {
    public static defaultProps = {
        errors: [],
        baseClass: InputTextBlockBaseClass.STANDARD,
    };

    public constructor(props: IInputBlockProps) {
        super(props);
        this.state = {
            id: getRequiredID(props, "inputText") as string,
        };
    }

    public render() {
        const componentClasses = classNames(this.props.baseClass, this.props.className);
        const hasErrors = !!this.props.errors && this.props.errors.length > 0;

        let children;
        if (typeof this.props.children === "function") {
            // Type is checked, but typechecker not accepting it.
            // eslint-disable-next-line @typescript-eslint/ban-types
            children = (this.props.children as Function)({ hasErrors, errorID: this.errorID, labelID: this.labelID });
        } else {
            children = this.props.children;
        }

        return (
            <label className={componentClasses}>
                <span id={this.labelID} className="inputBlock-labelAndDescription">
                    <span className={classNames("inputBlock-labelText", this.props.labelClassName)}>
                        {this.props.label}
                    </span>
                    <Paragraph className="inputBlock-labelNote">{this.props.labelNote}</Paragraph>
                </span>

                <span className="inputBlock-inputWrap">{children}</span>
                <Paragraph className="inputBlock-labelNote">{this.props.noteAfterInput}</Paragraph>
                <ErrorMessages id={this.errorID} errors={this.props.errors} />
            </label>
        );
    }

    private get labelID(): string {
        return this.state.id + "-label";
    }

    private get errorID(): string {
        return this.state.id + "-errors";
    }
}
