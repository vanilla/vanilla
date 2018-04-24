/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import classNames from "classnames";
import uniqueId from "lodash/uniqueId";
import { t } from "@core/application";

interface IProps {
    title: string;
    accessibleDescription: string;
    isVisible: boolean;
    body: JSX.Element;
    footer?: JSX.Element;
    additionalHeaderContent?: JSX.Element;
    alertMessage?: string;
    additionalClassRoot?: string;
    className?: string;
    titleRef?: React.Ref<any>;
    titleId?: string;
    descriptionId?: string;
    onCloseClick(event?: React.MouseEvent<any>);
}

export default class Popover extends React.Component<IProps> {
    private id = uniqueId("insertPopover-");

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

        const descriptionId = this.props.descriptionId || this.id + "-description";
        const titleId = this.props.titleId || this.id + "-title";

        return (
            <div
                className={classes}
                role="dialog"
                aria-describedby={descriptionId}
                aria-hidden={!this.props.isVisible}
                aria-labelledby={titleId}
                id={this.id}
            >
                {alertMessage}
                <div className={headerClasses}>
                    <h2 id={titleId} tabIndex={-1} className="H popover-title" ref={this.props.titleRef}>
                        {this.props.title}
                    </h2>
                    <div id={descriptionId} className="sr-only">
                        {this.props.accessibleDescription}
                    </div>
                    <button type="button" onClick={this.props.onCloseClick} className="Close richEditor-close">
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
