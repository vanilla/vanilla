/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import CloseButton from "@library/components/CloseButton";
import { IWithEditorProps, withEditor } from "@rich-editor/components/context";
import { flyoutPosition } from "./flyoutPosition";

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
}

export class Popover extends React.Component<IProps, IState> {
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

        let classes = classNames("richEditor-menu", "richEditorFlyout", {
            [additionalClassRoot as any]: !!additionalClassRoot,
            isHidden: !this.props.isVisible,
        });

        classes += this.props.className ? ` ${this.props.className}` : "";

        const headerClasses = classNames("richEditorFlyout-header", {
            [additionalClassRoot + "-header"]: !!additionalClassRoot,
        });

        const bodyClasses = classNames("richEditorFlyout-body", {
            [additionalClassRoot + "-body"]: !!additionalClassRoot,
        });

        const footerClasses = classNames("richEditorFlyout-footer", {
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
                        className="richEditorFlyout-title"
                        ref={this.props.titleRef}
                    >
                        {this.props.title}
                    </h2>
                    {screenReaderDescription}

                    <CloseButton
                        onClick={this.props.onCloseClick}
                        className="richEditor-close"
                        legacyMode={this.props.legacyMode}
                    />

                    {this.props.additionalHeaderContent && this.props.additionalHeaderContent}
                </div>

                <div className={bodyClasses}>{this.props.body && this.props.body}</div>

                <div className={footerClasses}>{this.props.footer && this.props.footer}</div>
            </div>
        );
    }
}

export default withEditor<IProps>(Popover);
