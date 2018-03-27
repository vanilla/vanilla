/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import * as Icons from "./Icons";
import classnames from "classnames";
import EditorToolbar from "./EditorToolbar";
import { t } from "@core/utility";

/**
 * Component for a single item in a EditorToolbar.
 */
export default class EditorMenuItem extends React.Component {

    static propTypes = {
        propertyName: PropTypes.string.isRequired,
        label: PropTypes.string.isRequired,
        clickHandler: PropTypes.func.isRequired,
        isActive: PropTypes.bool.isRequired,
        checkForExternalFocus: PropTypes.func,
        isFirst: PropTypes.bool.isRequired,
        isLast: PropTypes.bool.isRequired,
        role: PropTypes.string,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        this.checkForExternalFocus = (props.isLast && props.checkForExternalFocus !== undefined) ? props.checkForExternalFocus : doNothingOnBlur => {};
        this.buttonRole = props.role !== undefined ? props.role : "button";
    }

    render() {
        const { propertyName, isActive, clickHandler } = this.props;
        const Icon = Icons[propertyName];
        const buttonClasses = classnames("richEditor-button", "richEditor-menuItem", {
            isActive: isActive || false,
        });

        return <button className={buttonClasses} type="button" aria-label={t('richEditor.menu.' + this.props.propertyName)} role={this.props.role} aria-pressed={this.props.isActive} onClick={clickHandler} onBlur={this.checkForExternalFocus}>
            <Icon />
        </button>;
    }
}
