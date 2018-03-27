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
        isLast: PropTypes.bool.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        this.checkForExternalFocus = (props.isLast && props.checkForExternalFocus !== undefined) ? props.checkForExternalFocus : doNothingOnBlur => {};
    }

    render() {
        const { propertyName, isActive, clickHandler } = this.props;
        const Icon = Icons[propertyName];
        const buttonClasses = classnames("richEditor-button", {
            isActive: isActive || false,
        });

        return <li className="richEditor-menuItem" role="presentation">
            <button className={buttonClasses} type="button" aria-label={this.props.propertyName} role="menuitem" aria-pressed={this.props.isActive} onClick={clickHandler} onBlur={this.checkForExternalFocus}>
                <Icon />
            </button>
        </li>;
    }
}
