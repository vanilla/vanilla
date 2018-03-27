/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import LinkBlot from "quill/formats/link";
import { t } from "@core/utility";
import EditorMenuItem from "./EditorMenuItem";
import * as quillUtilities from "../quill-utilities";

/**
 * @typedef {Object} MenuItemData
 * @property {boolean} active - Whether the given item should be lit up.
 * @property {string} [value] - A value if applicable.
 * @property {string} [formatName] - The name of the format if it is different than the item's key.
 * @property {Object} [enableValue] - The value to use to enable this item.
 * @property {function} [formatter] - A custom handler to run in addition to the default handler.
 */

/**
 * Component for declaring a dynamic toolbar linked to a quill instance.
 */
export default class EditorToolbar extends React.PureComponent {

    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        menuItems: PropTypes.object.isRequired,
        isHidden: PropTypes.bool,
        checkForExternalFocus: PropTypes.func,
        itemRole: PropTypes.string,
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
    }

    /**
     * Handle quill changes. Used to detect selection changes.
     *
     * @param {string} type - The change type.
     * @param {RangeStatic} range - The new selection range.
     */
    quillChangeHandler = (type, range) => {
        if (!this.props.isHidden) {
            this.update(range);
        }
    };

    componentWillReceiveProps(nextProps) {
        if (this.props.isHidden && !nextProps.isHidden) {
            const [range] = this.quill.selection.getRange();
            this.update(range);
        }
    }

    /**
     * React to quill optimizations passes.
     */
    quillOptimizeHandler = () => {
        if (!this.props.isHidden) {
            const [range] = this.quill.selection.getRange();
            this.update(range);
        }
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
        const menuItemList = Object.keys(this.state);
        const checkForExternalFocus = this.props.checkForExternalFocus;
        const menuItems = menuItemList.map((itemName, key) => {
            const isActive = this.state[itemName].active;
            return <EditorMenuItem
                propertyName={itemName}
                label={t('itemName')}
                key={key}
                isActive={isActive}
                isLast={key + 1 === menuItemList.length}
                isFirst={key === 0}
                clickHandler={this.formatItem.bind(this, itemName, event)}
                checkForExternalFocus={checkForExternalFocus}
                role={this.props.itemRole}
            />;
        });

        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">
                    {menuItems}
                </div>
            </div>
        );
    }

    /** MARK: Click handlers */

    /**
     * Generic item click handler. This will defer to other handlers where available.
     *
     * @param {string} itemKey - The key of the item that was clicked.
     */
    formatItem(itemKey) {
        const itemData = this.state[itemKey];

        if ("formatter" in itemData) {
            itemData.formatter(itemData);
        } else {
            const formatName = itemData.formatName || itemKey;
            let value;

            if (itemData.active) {
                value = false;
            } else {
                value = itemData.enableValue || true;
            }
            // Fall back to simple boolean
            this.quill.format(formatName, value, Quill.sources.USER);
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
            return;
        }

        for (const [itemKey, itemData] of Object.entries(this.state)) {
            if (itemKey === "link") {
                const newState = {
                    [itemKey]: {
                        ...itemData,
                        active: quillUtilities.rangeContainsBlot(this.quill, range, LinkBlot),
                    },
                };
                this.setState(newState);
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
            const lookupKey = itemData.formatName || itemKey;
            const value = formats[lookupKey];
            if (itemData.enableValue ? value === itemData.enableValue : value) {
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
