/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import classNames from "classnames";
import { t } from "@core/application";
import { withEditor, IEditorContextProps } from "../ContextProvider";

interface IGenericPopoverProps extends IEditorContextProps {
    title: string;
    accessibleDescription: string;
    isVisible: boolean;
    body: JSX.Element;
    footer?: JSX.Element;
    additionalHeaderContent?: JSX.Element;
    popoverTitleID: string;
    popoverDescriptionID: string;
    alertMessage?: string;
    id: string;
    additionalClassRoot?: string;
    className?: string;
    closeMenuHandler(event?: React.MouseEvent<any>);
}

export interface IPopoverProps extends IEditorContextProps {
    isVisible: boolean;
    closeMenuHandler: React.MouseEventHandler<any>;
    blurHandler?: React.FocusEventHandler<any>;
    popoverTitleID: string;
    popoverDescriptionID: string;
    id: string;
}

export class Popover extends React.PureComponent<IGenericPopoverProps> {
    public render() {
        const { additionalClassRoot } = this.props;

        let classes = classNames("richEditor-menu", "FlyoutMenu", "insertPopover", {
            [additionalClassRoot as any]: !!additionalClassRoot,
            isHidden: !this.props.isVisible,
        });

        classes += this.props.className ? ` ${this.props.className}` : "";

        const headerClasses = classNames("insertPopover-header", {
            [additionalClassRoot + "-header"]: !!additionalClassRoot,
        });

        const bodyClasses = classNames("insertPopover-body", {
            [additionalClassRoot + "-body"]: !!additionalClassRoot,
        });

        const footerClasses = classNames("insertPopover-footer", {
            [additionalClassRoot + "-footer"]: !!additionalClassRoot,
        });

        const alertMessage = this.props.alertMessage ? (
            <span aria-live="assertive" role="alert" className="sr-only">
                {this.props.alertMessage}
            </span>
        ) : null;

        return (
            <div
                className={classes}
                role="dialog"
                aria-describedby={this.props.popoverDescriptionID}
                aria-hidden={!this.props.isVisible}
                aria-labelledby={this.props.popoverTitleID}
                id={this.props.id}
            >
                {alertMessage}
                <div className={headerClasses}>
                    <h2 id={this.props.popoverTitleID} tabIndex={-1} className="H popover-title">
                        {this.props.title}
                    </h2>
                    <div id={this.props.popoverDescriptionID} className="sr-only">
                        {this.props.accessibleDescription}
                    </div>
                    <button type="button" onClick={this.props.closeMenuHandler} className="Close richEditor-close">
                        <span className="Close-x" aria-hidden="true">
                            {t("×")}
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

export default withEditor<IGenericPopoverProps>(Popover);
