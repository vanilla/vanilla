/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import CloseButton from "@library/navigation/CloseButton";
import { flyoutPosition } from "@rich-editor/flyouts/pieces/flyoutPosition";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { richEditorFlyoutClasses } from "@rich-editor/flyouts/pieces/flyoutClasses";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { ButtonTypes } from "@library/forms/buttonTypes";

interface IState {
    id: string;
    descriptionID?: string;
    titleID: string;
}

interface IProps extends IWithEditorProps {
    id: string;
    titleID: string;
    descriptionID?: string;
    title: string;
    accessibleDescription?: string;
    isVisible: boolean;
    body: JSX.Element;
    footer?: JSX.Element;
    additionalHeaderContent?: JSX.Element;
    alertMessage?: string;
    additionalClassRoot?: string;
    className?: string;
    titleRef?: React.Ref<any>;
    onCloseClick(event?: React.MouseEvent<any>);
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode: boolean;
    headerClass?: string;
    bodyClass?: string;
    footerClass?: string;
}

export class Flyout extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
        this.state = {
            id: props.id,
            descriptionID: props.descriptionID,
            titleID: props.titleID,
        };
    }

    public render() {
        const { additionalClassRoot } = this.props;
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const classesFlyout = richEditorFlyoutClasses();

        let classes = classNames("richEditor-menu", "richEditorFlyout", classesFlyout.root, {
            [additionalClassRoot as any]: !!additionalClassRoot,
            isHidden: !this.props.isVisible,
        });

        classes += this.props.className ? ` ${this.props.className}` : "";

        const headerClasses = classNames("richEditorFlyout-header", classesFlyout.header, this.props.headerClass, {
            [additionalClassRoot + "-header"]: !!additionalClassRoot,
        });

        const bodyClasses = classNames("richEditorFlyout-body", classesFlyout.body, this.props.bodyClass, {
            [additionalClassRoot + "-body"]: !!additionalClassRoot,
        });

        const footerClasses = classNames("richEditorFlyout-footer", classesFlyout.footer, this.props.footerClass, {
            [additionalClassRoot + "-footer"]: !!additionalClassRoot,
        });

        const alertMessage = this.props.alertMessage ? (
            <span aria-live="assertive" role="alert" className="sr-only">
                {this.props.alertMessage}
            </span>
        ) : null;

        const screenReaderDescription = this.props.accessibleDescription ? (
            <div id={this.state.descriptionID} className="sr-only">
                {this.props.accessibleDescription}
            </div>
        ) : null;

        return (
            <div
                id={this.state.id}
                aria-describedby={this.state.descriptionID}
                aria-labelledby={this.state.titleID}
                className={classes}
                role="dialog"
                aria-hidden={!this.props.isVisible}
                style={flyoutPosition(!!this.props.renderAbove, !!this.props.renderLeft, this.props.legacyMode)}
            >
                {alertMessage}
                <div className={headerClasses}>
                    <h2
                        id={this.props.titleID}
                        tabIndex={-1}
                        className={classNames("richEditorFlyout-title", classesFlyout.title)}
                        ref={this.props.titleRef}
                    >
                        {this.props.title}
                    </h2>
                    {screenReaderDescription}

                    <CloseButton
                        baseClass={ButtonTypes.ICON}
                        onClick={this.props.onCloseClick}
                        className={classNames("richEditor-close", classesRichEditor.close)}
                    />

                    {this.props.additionalHeaderContent && this.props.additionalHeaderContent}
                </div>

                <div className={bodyClasses}>{this.props.body && this.props.body}</div>

                <div className={footerClasses}>{this.props.footer && this.props.footer}</div>
            </div>
        );
    }
}

export default withEditor<IProps>(Flyout);
