/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { RangeStatic } from "quill";
import Quill, { Sources } from "quill/core";
import LinkBlot from "quill/formats/link";
import { t } from "@dashboard/application";
import MenuItem, { IMenuItemData } from "./MenuItem";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";
import { rangeContainsBlot } from "@rich-editor/quill/utility";

interface IProps extends IEditorContextProps {
    menuItems?: {
        [key: string]: IMenuItemData;
    };
    isHidden?: boolean;
    itemRole?: string;
    restrictedFormats?: null | string[];
}

interface IState {
    range: RangeStatic;
    menuItems: {
        [key: string]: IMenuItemData;
    };
}

/**
 * Component for declaring a dynamic toolbar linked to a quill instance.
 */
export class MenuItems extends React.Component<IProps, IState> {
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

        this.state = {
            range: {
                index: 0,
                length: 0,
            },
            menuItems: props.menuItems || MenuItems.defaultItems,
        };

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
    }

    public shouldComponentUpdate(nextProps) {
        if (nextProps.isHidden) {
            return false;
        }

        return true;
    }

    public componentWillReceiveProps(nextProps) {
        if (this.props.isHidden && !nextProps.isHidden) {
            this.setState({
                range: this.quill.getSelection(),
            });
            this.update();
        }
    }

    /**
     * Attach some quill listeners.
     */
    public componentWillMount() {
        this.quill.on(Quill.events.SELECTION_CHANGE, this.handleSelectionChange);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.SELECTION_CHANGE, this.handleSelectionChange);
    }

    public render() {
        const menuItemList = Object.keys(this.state.menuItems);
        const numberOfActiveItems = Object.values(this.state.menuItems)
            .filter(item => !item.isFallback)
            .reduce((accumulator, currentItem) => {
                return currentItem.active ? accumulator + 1 : accumulator;
            }, 0);

        const menuItems = menuItemList.map((itemName, key) => {
            const isDisabled = this.props.restrictedFormats ? this.props.restrictedFormats.includes(itemName) : false;
            const isActive = this.state.menuItems[itemName].active;
            const isActiveFallback = (numberOfActiveItems === 0 && this.state.menuItems[itemName].isFallback) || false;

            const clickHandler = () => {
                this.formatItem(itemName);
            };

            return (
                <MenuItem
                    propertyName={itemName}
                    label={t("richEditor.menu." + itemName)}
                    key={itemName}
                    disabled={isDisabled}
                    isActive={!isDisabled && (isActive || isActiveFallback)}
                    isLast={key + 1 === menuItemList.length}
                    isFirst={key === 0}
                    onClick={clickHandler}
                    role={this.props.itemRole}
                />
            );
        });

        return (
            <div className="richEditor-menu" role="menu">
                <div className="richEditor-menuItems MenuItems">{menuItems}</div>
            </div>
        );
    }

    private setMenuState(
        menuState: {
            [key: string]: IMenuItemData;
        },
        callback?: () => void,
    ) {
        this.setState(prevState => {
            return {
                menuItems: {
                    ...prevState.menuItems,
                    ...menuState,
                },
            };
        }, callback);
    }

    /**
     * Handle selection changes.
     */
    private handleSelectionChange = (range: RangeStatic, oldRange: RangeStatic, source: Sources) => {
        if (this.props.isHidden || source === Quill.sources.SILENT) {
            return;
        }

        this.setState({ range });
        this.update();
    };

    /** MARK: Click handlers */

    /**
     * Generic item click handler. This will defer to other handlers where available.
     *
     * @param itemKey - The key of the item that was clicked.
     */
    private formatItem(itemKey: string) {
        const itemData = this.state.menuItems[itemKey];

        if (itemData.formatter) {
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
    private update() {
        const { range } = this.state;
        if (!range) {
            return;
        }

        for (const [itemKey, itemData] of Object.entries(this.state.menuItems)) {
            if (itemKey === "link") {
                const newState = {
                    [itemKey]: {
                        ...itemData,
                        active: rangeContainsBlot(this.quill, LinkBlot, range),
                    },
                };
                this.setMenuState(newState);
                continue;
            }

            this.updateBooleanFormat(itemKey, itemData);
        }
    }

    /**
     * Handle the simple on/off inline formats (eg. bold, italic).
     *
     * @param itemKey - The key of the item.
     * @param itemData - The item to modify.
     * @param range - The range to update.
     */
    private updateBooleanFormat(itemKey: string, itemData: IMenuItemData) {
        const { range } = this.state;
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

        this.setMenuState(newState);
    }
}

export default withEditor<IProps>(MenuItems);
