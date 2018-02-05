/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/quill";
import { t } from "@core/utility";
import EditorMenuItem from "./EditorMenuItem";

/**
 * @typedef {Object} MenuItemData
 * @property {boolean} active - Whether the given item should be lit up.
 * @property {string} [value] - A value if applicable.
 * @property {function} [formatter] - A custom handler to run in addition to the default handler.
 */

/**
 * Component for declaring a dynamic toolbar linked to a quill instance.
 */
export default class EditorToolbar extends React.Component {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        menuItems: PropTypes.object,
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
        code: {
            active: false,
        },
        link: {
            active: false,
            value: "",
        },
    };

    /** @type {Quill} */
    quill;

    /** @type {Object<string, MenuItemData>} */
    state;

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        this.state = props.menuItems || EditorToolbar.defaultItems;

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;

        this.menuItemClickHandler = this.menuItemClickHandler.bind(this);
    }

    /**
     * Handle quill changes. Used to detect selection changes.
     *
     * @param {string} type - The change type.
     * @param {RangeStatic} range - The new selection range.
     */
    quillChangeHandler = (type, range) => {
        if (type === Quill.events.SELECTION_CHANGE) {
            this.update(range);
        }
    };

    /**
     * React to quill optimizations passes.
     */
    quillOptimizeHandler = () => {
        const [range] = this.quill.selection.getRange();
        this.update(range);
    };

    /**
     * Attach some quill listeners.
     */
    componentWillMount() {
        this.quill.on(Quill.events.EDITOR_CHANGE, this.quillChangeHandler);
        this.quill.on(Quill.events.SCROLL_OPTIMIZE, this.quillOptimizeHandler);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.quillChangeHandler);
        this.quill.off(Quill.events.SCROLL_OPTIMIZE, this.quillOptimizeHandler);
    }

    /**
     * @inheritDoc
     */
    render() {
        const menuItems = Object.keys(this.state).map((itemName, key) => {
            const isActive = this.state[itemName].active;

            return <EditorMenuItem propertyName={itemName} key={key} isActive={isActive} clickHandler={(e) => this.menuItemClickHandler(itemName, e)}/>;
        });

        return (
            <div className="richEditor-menu" role="dialog" aria-label={t("Inline Level Formatting Menu")}>
                <ul className="richEditor-menuItems MenuItems" role="menubar" aria-label={t("Inline Level Formatting Menu")}>
                    {menuItems}
                </ul>
            </div>
        );
    }

    /** MARK: Click handlers */

    /**
     * Generic item click handler. This will defer to other handlers where available.
     *
     * @param {string} itemKey - The key of the item that was clicked.
     * @param {React.SyntheticEvent} event - The click event.
     */
    menuItemClickHandler(itemKey, event) {
        const itemData = this.state[itemKey];

        if ("formatter" in itemData) {
            itemData.formatter();
        } else {
            // Fall back to simple boolean
            this.quill.format(itemKey, !itemData.active, Quill.sources.USER);
        }

        this.update();
    }


    /**
     * Update all toolbar items' states.
     *
     * @param {Object=} range - A quill range object. Defaults to currently selected range.
     */
    update(range = null) {
        if (!range) {
            [range] = this.quill.selection.getRange();
        }

        for (const [itemKey, itemData] of Object.entries(this.state)) {
            if ("value" in itemData) {

                // Handle the link thing.
                continue;
            }

            this.updateBooleanFormat(itemKey, itemData, range);
        }
    }

    /**
     * Handle the simple on/off inline formats (eg. bold, italic).
     *
     * @param {string} itemKey - The key of the item.
     * @param {MenuItemData} itemData - The item to modify.
     * @param {Object} range - The range to update.
     */
    updateBooleanFormat(itemKey, itemData, range) {
        let newActiveState = false;
        if (range !== null) {
            const formats = this.quill.getFormat(range);
            if (formats[itemKey]) {
                newActiveState = true;
            }
        }

        const newState = {
            [itemKey]: {
                ...itemData,
                active: newActiveState,
            },
        };

        this.setState(newState);
    }
}
