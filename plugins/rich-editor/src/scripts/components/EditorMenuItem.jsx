/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import * as Icons from "./Icons";
import classnames from "classnames";

/**
 * Component for a single item in a EditorToolbar.
 */
export default class EditorMenuItem extends React.Component {

    static propTypes = {
        propertyName: PropTypes.string,
        isActive: PropTypes.bool,
        clickHandler: PropTypes.func,
    };

    render() {
        const { propertyName, isActive, clickHandler } = this.props;
        const Icon = Icons[propertyName];

        const buttonClasses = classnames("richEditor-button", {
            isActive,
        });

        return <li className="richEditor-menuItem" role="menuitem">
            <button className={buttonClasses} type="button" onClick={clickHandler}>
                <Icon />
            </button>
        </li>;
    }
}
