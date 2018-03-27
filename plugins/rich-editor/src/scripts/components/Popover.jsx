/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@core/utility";
import * as PropTypes from "prop-types";
import classNames from 'classnames';
import { withEditor, editorContextTypes } from "./EditorProvider";

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
        additionalClassRoot: PropTypes.string,
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

        const menuDescriptionID = "editor-description-" + this.props.editorID;
        const titleID = "editor-title-" + this.props.editorID;

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
            aria-describedby={menuDescriptionID}
            aria-hidden={!this.props.isVisible}
            aria-labelledby={titleID}
            id={this.props.id}
        >
            <div className={headerClasses}>
                <h2 id={titleID} className="H insertMedia-title">
                    {this.props.title}
                </h2>
                <div id={menuDescriptionID} className="sr-only">
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
