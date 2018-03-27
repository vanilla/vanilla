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

export class InsertPopover extends React.PureComponent {

    static propTypes = {
        ...editorContextTypes,
        title: PropTypes.string.isRequired,
        accessibleDescription: PropTypes.string.isRequired,
        isVisible: PropTypes.bool.isRequired,
        body: PropTypes.element.isRequired,
        closeMenu: PropTypes.func.isRequired,
        footer: PropTypes.element,
        additionalHeaderContent: PropTypes.element,
        className: PropTypes.string,
    };

    render() {
        let classes = classNames(
            'richEditor-menu',
            'FlyoutMenu',
            'insertPopover',
            {
                isHidden: !this.props.isVisible,
            }
        );

        classes += this.props.className ? ` ${this.props.className}` : "";

        const menuDescriptionID = "editor-description-" + this.props.editorID;
        const titleID = "editor-title-" + this.props.editorID;

        return <div
            className={classes}
            role="dialog"
            aria-describedby={menuDescriptionID}
            aria-hidden={!this.props.isVisible}
            aria-labelledby={titleID}
        >
            <div className="insertPopover-header">
                <h2 id={titleID} className="H insertMedia-title">
                    {t('Smileys & Faces')}
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

            <div className="insertPopover-body">
                {this.props.body && this.props.body}
            </div>

            <div className="insertMedia-footer Footer">
                {this.props.footer && this.props.footer}
            </div>
        </div>;
    }
}

export default withEditor(InsertPopover);
