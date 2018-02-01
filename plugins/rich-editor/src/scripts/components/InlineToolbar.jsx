import React from "react";
import * as PropTypes from "prop-types";
import * as Icons from "./Icons";
import Quill from "quill";
import { t } from "@core/utility";
import classnames from "classnames";

/**
 * @typedef {Object} Props
 * @property {Quill} quill - A quill instance.
 */

/**
 * Menu Item component.
 *
 // * @param {QuillMenuItemProps} props
 * @returns {React.Element}
 * @constructor
 */
function QuillMenuItem (props) {
    const { propertyName, isActive, clickHandler } = props;
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

QuillMenuItem.propTypes = {
    propertyName: PropTypes.string,
    isActive: PropTypes.bool,
    clickHandler: PropTypes.func,
};

export default class QuillTooltip extends React.Component {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        menuItems: PropTypes.arrayOf(PropTypes.oneOf([
            PropTypes.string,
            PropTypes.object,
        ])),
    };

    static defaultItems = {
        bold: {
            active: false,
        },
        italic: {
            active: false,
        },
        strike: {
            active: false,
        },
        inlineCode: {
            active: false,
        },
        link: {
            active: false,
        },
    };

    /** @type {Quill} */
    quill;

    constructor(props) {
        super(props);

        this.state = {
            menuItems: props.menuItems || QuillTooltip.defaultItems,
        };

        this.quill = props.quill;

        this.menuItemClickHandler = this.menuItemClickHandler.bind(this);
    }

    /**
     * Render function
     *
     * @param {Props} props - Props
     */
    render() {
        const menuItems = Object.keys(this.state.menuItems).map((itemName, key) => {
            const isActive = this.state.menuItems[itemName].active;

            return <QuillMenuItem propertyName={itemName} key={key} isActive={isActive} clickHandler={() => this.menuItemClickHandler(itemName)}/>;
        });

        return (
            <div className="richEditor-menu" role="dialog" aria-label={t("Inline Level Formatting Menu")}>
                <ul className="richEditor-menuItems MenuItems" role="menubar" aria-label={t("Inline Level Formatting Menu")}>
                    {menuItems}
                </ul>
            </div>
        );
    }

    menuItemClickHandler(itemKey) {
        const value = !this.state.menuItems[itemKey]["active"];
        this.quill.format(itemKey, value, Quill.sources.USER);
        this.updateItem(itemKey);
    }

    updateItem(itemKey) {
        const [ range ] = this.quill.selection.getRange();
        const { menuItems } = this.state;
        const active = range === null ? false : !menuItems[itemKey].active;

        const newState = {
            menuItems: {
                ...menuItems,
                [itemKey]: {
                    ...menuItems[itemKey],
                    active,
                },
            },
        };

        this.setState(newState);
    }
}
