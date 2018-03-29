/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import classNames from 'classnames';
import { t } from "@core/application";
import { withEditor, editorContextTypes } from "../ContextProvider";

export class Popover extends React.PureComponent {

    static propTypes = {
        ...editorContextTypes,
        title: PropTypes.string.isRequired,
        accessibleDescription: PropTypes.string.isRequired,
        isVisible: PropTypes.bool.isRequired,
        body: PropTypes.element.isRequired,
        closeMenu: PropTypes.func.isRequired,
        id: PropTypes.string,
        footer: PropTypes.element,
        additionalHeaderContent: PropTypes.element,
        popoverTitleID: PropTypes.string.isRequired,
        popoverDescriptionID: PropTypes.string.isRequired,
    };

    render() {
        const { additionalClassRoot } = this.props;

        let classes = classNames(
            'richEditor-menu',
            'FlyoutMenu',
            'insertPopover',
            {
                [additionalClassRoot]: additionalClassRoot,
                isHidden: !this.props.isVisible,
            }
        );

        classes += this.props.className ? ` ${this.props.className}` : "";

        const headerClasses = classNames(
            "insertPopover-header",
            {
                [additionalClassRoot + "-header"]: additionalClassRoot,
            }
        );

        const bodyClasses = classNames(
            "insertPopover-body",
            {
                [additionalClassRoot + "-body"]: additionalClassRoot,
            }
        );

        const footerClasses = classNames(
            "insertPopover-footer",
            {
                [additionalClassRoot + "-footer"]: additionalClassRoot,
            }
        );

        return <div
            className={classes}
            role="dialog"
            aria-describedby={this.props.popoverDescriptionID}
            aria-hidden={!this.props.isVisible}
            aria-labelledby={this.props.popoverTitleID}
            id={this.props.id}
        >
            <div className={headerClasses}>
                <h2 id={this.props.popoverTitleID} tabIndex="-1" className="H popover-title">
                    {this.props.title}
                </h2>
                <div id={this.props.popoverDescriptionID} className="sr-only">
                    {this.props.accessibleDescription}
                </div>
                <button type="button" onClick={this.props.closeMenu} className="Close richEditor-close">
                    <span className="Close-x" aria-hidden="true">×</span>
                    <span className="sr-only">{t('Close')}</span>
                </button>

                {this.props.additionalHeaderContent && this.props.additionalHeaderContent}
            </div>

            <div className={bodyClasses}>
                {this.props.body && this.props.body}
            </div>

            <div className={footerClasses}>
                {this.props.footer && this.props.footer}
            </div>
        </div>;
    }
}

export default withEditor(Popover);
