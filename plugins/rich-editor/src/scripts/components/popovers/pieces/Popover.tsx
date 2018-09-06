/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { t } from "@dashboard/application";

interface IState {
    id: string;
    descriptionID?: string;
    titleID: string;
}

interface IProps {
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
}

export default class Popover extends React.Component<IProps, IState> {
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
                    <button type="button" onClick={this.props.onCloseClick} className="Close richEditor-close">
                        <span className="Close-x" aria-hidden="true">
                            {t("Close")}
                        </span>
                        <span className="sr-only">{t("Close")}</span>
                    </button>

                    {this.props.additionalHeaderContent && this.props.additionalHeaderContent}
                </div>

                <div className={bodyClasses}>{this.props.body && this.props.body}</div>

                <div className={footerClasses}>{this.props.footer && this.props.footer}</div>
            </div>
        );
    }
}
