/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import { RangeStatic } from "quill";
import Quill from "quill/core";
import LinkBlot from "quill/formats/link";
import { t } from "@core/application";
import MenuItem from "./MenuItem";
import * as quillUtilities from "../../Quill/utility";
import { withEditor, IEditorContextProps } from "../ContextProvider";
import { IMenuItemData } from "./MenuItem";

interface IProps extends IEditorContextProps{
    menuItems?: {
        [key: string]: IMenuItemData;
    };
    isHidden?: boolean;
    onBlur?: (event: React.FocusEvent<any>) => void;
    itemRole?: string;
}

interface IState {
    [key: string]: IMenuItemData;
}

/**
 * Component for declaring a dynamic toolbar linked to a quill instance.
 */
export class Toolbar extends React.PureComponent<IProps, IState> {

    private static defaultItems = {
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

    private quill: Quill;

    constructor(props) {
        super(props);

        this.state = props.menuItems || Toolbar.defaultItems;

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
    }

    public componentWillReceiveProps(nextProps) {
        if (this.props.isHidden && !nextProps.isHidden) {
            const [range] = this.quill.selection.getRange();
            this.update(range);
        }
    }

    /**
     * Attach some quill listeners.
     */
    public componentWillMount() {
        this.quill.on(Quill.events.EDITOR_CHANGE, this.quillChangeHandler);
        this.quill.on(Quill.events.SCROLL_OPTIMIZE, this.quillOptimizeHandler);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.quillChangeHandler);
        this.quill.off(Quill.events.SCROLL_OPTIMIZE, this.quillOptimizeHandler);
    }

    public render() {
        const menuItemList = Object.keys(this.state);
        const menuItems = menuItemList.map((itemName, key) => {
            const isActive = this.state[itemName].active;

            return <MenuItem
                propertyName={itemName}
                label={t('richEditor.menu.' + itemName)}
                key={key}
                isActive={isActive}
                isLast={key + 1 === menuItemList.length}
                isFirst={key === 0}
                onClick={() => { this.formatItem(itemName); } }
                onBlur={this.props.onBlur}
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

    /**
     * Handle quill changes. Used to detect selection changes.
     *
     * @param type - The change type.
     * @param range - The new selection range.
     */
    private quillChangeHandler = (type: string, range: RangeStatic) => {
        if (!this.props.isHidden) {
            this.update(range);
        }
    }

    /**
     * React to quill optimizations passes.
     */
    private quillOptimizeHandler = () => {
        if (!this.props.isHidden) {
            const [range] = this.quill.selection.getRange();
            this.update(range);
        }
    }

    /** MARK: Click handlers */

    /**
     * Generic item click handler. This will defer to other handlers where available.
     *
     * @param {string} itemKey - The key of the item that was clicked.
     */
    private formatItem(itemKey) {
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
     * @param range - A quill range object. Defaults to currently selected range.
     */
    private update(range?: RangeStatic) {
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
     * @param itemKey - The key of the item.
     * @param itemData - The item to modify.
     * @param range - The range to update.
     */
    private updateBooleanFormat(itemKey: string, itemData: IMenuItemData, range: RangeStatic) {
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

export default withEditor(Toolbar);
