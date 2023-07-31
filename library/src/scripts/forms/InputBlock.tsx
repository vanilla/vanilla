/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import ErrorMessages from "@library/forms/ErrorMessages";
import { getRequiredID, IOptionalComponentID } from "@library/utility/idUtils";
import classNames from "classnames";
import Paragraph from "@library/layout/Paragraph";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { cx } from "@emotion/css";

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
    label?: ReactNode;
    legend?: ReactNode;
    children: React.ReactNode | CallbackChildren;
    className?: string;
    wrapClassName?: string;
    labelClassName?: string;
    noteAfterInput?: string | ReactNode;
    labelNote?: ReactNode;
    labelID?: string;
    descriptionID?: string;
    errors?: IError[];
    baseClass?: InputTextBlockBaseClass;
    legacyMode?: boolean;
    noMargin?: boolean;
    grid?: boolean;
    tight?: boolean;
    icon?: ReactNode;
    noErrorPadding?: boolean;
    extendErrorMessage?: boolean;
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
        const { label, legend } = this.props;
        const OuterTag = legend ? "fieldset" : label ? "label" : "div";
        const LegendOrSpanTag = legend ? "legend" : "span";

        const hasLegendOrLabel = !!this.props.legend || !!this.props.label;

        const classesInputBlock = inputBlockClasses();
        const componentClasses = classNames(
            this.props.baseClass === InputTextBlockBaseClass.STANDARD ? classesInputBlock.root : "",
            this.props.className,
        );
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
            <OuterTag className={componentClasses}>
                {hasLegendOrLabel && (
                    <span id={this.labelID} className={classesInputBlock.labelAndDescription}>
                        <LegendOrSpanTag className={classNames(classesInputBlock.labelText, this.props.labelClassName)}>
                            {this.props.legend ?? this.props.label!}
                            {this.props.icon}
                        </LegendOrSpanTag>
                        <Paragraph className={classesInputBlock.labelNote}>{this.props.labelNote}</Paragraph>
                    </span>
                )}

                <span
                    className={classNames(
                        classesInputBlock.inputWrap,
                        [classesInputBlock.fieldsetGroup],
                        this.props.wrapClassName,
                        { [classesInputBlock.grid]: this.props.grid },
                        { [classesInputBlock.tight]: this.props.tight },
                        { noMargin: this.props.noMargin },
                    )}
                >
                    {children}
                </span>
                <Paragraph className={classesInputBlock.noteAfterInput}>{this.props.noteAfterInput}</Paragraph>
                <ErrorMessages
                    id={this.errorID}
                    errors={this.props.errors}
                    className={cx({ [classesInputBlock.extendErrorPadding]: this.props.extendErrorMessage })}
                    padded={!this.props.noErrorPadding}
                />
            </OuterTag>
        );
    }

    private get labelID(): string {
        return this.state.id + "-label";
    }

    private get errorID(): string {
        return this.state.id + "-errors";
    }
}
