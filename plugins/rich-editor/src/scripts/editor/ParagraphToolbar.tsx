/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { Blot } from "quill/core";
import { RangeStatic, Sources } from "quill";
import Emitter from "quill/core/emitter";
import Parchment from "parchment";
import { t } from "@dashboard/application";
import Toolbar from "./generic/Toolbar";
import * as Icons from "./Icons";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./generic/MenuItem";
import FocusableEmbedBlot from "../quill/blots/abstract/FocusableEmbedBlot";
import { watchFocusInDomTree } from "@dashboard/dom";

const PARAGRAPH_ITEMS = {
    header: {
        1: {
            name: "title",
        },
        2: {
            name: "subtitle",
        },
    },
    "blockquote-line": {
        name: "blockquote",
    },
    "code-block": {
        name: "codeBlock",
    },
    "spoiler-line": {
        name: "spoiler",
    },
};

interface IState {
    range: RangeStatic;
    showMenu: boolean;
    showPilcrow: boolean;
    isEmbedFocused: boolean;
    activeFormatKey: string;
}

export class ParagraphToolbar extends React.PureComponent<IEditorContextProps, IState> {
    private quill: Quill;
    private toolbarNode: HTMLElement;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private toolbarItems: {
        [key: string]: IMenuItemData;
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.ID = this.props.editorID + "paragraphMenu";
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";
        this.state = {
            showPilcrow: true,
            isEmbedFocused: false,
            showMenu: false,
            range: {
                index: 0,
                length: 0,
            },
            activeFormatKey: "pilcrow",
        };
        this.initializeToolbarValues();
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.addEventListener("keydown", this.escFunction, false);
        watchFocusInDomTree(this.selfRef.current!, this.handleFocusChange);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.removeEventListener("keydown", this.escFunction, false);
    }

    public render() {
        let pilcrowClasses = "richEditor-button richEditorParagraphMenu-handle";

        if (!this.state.showPilcrow || this.state.isEmbedFocused) {
            pilcrowClasses += " isHidden";
        }

        const Icon = Icons[this.state.activeFormatKey];

        return (
            <div
                id={this.componentID}
                style={this.getPilcrowStyles()}
                className="richEditor-menu richEditorParagraphMenu"
                ref={this.selfRef}
            >
                <button
                    type="button"
                    id={this.buttonID}
                    aria-label={t("richEditor.menu.paragraph")}
                    aria-controls={this.menuID}
                    aria-expanded={this.state.showMenu}
                    disabled={!this.state.showPilcrow}
                    className={pilcrowClasses}
                    aria-haspopup="menu"
                    onClick={this.pilcrowClickHandler}
                    onKeyDown={this.handleKeyPress}
                >
                    <Icon />
                </button>
                <div
                    id={this.menuID}
                    className={this.getToolbarClasses()}
                    style={this.getToolbarStyles()}
                    ref={ref => (this.toolbarNode = ref!)}
                    role="menu"
                >
                    <Toolbar menuItems={this.toolbarItems} isHidden={!this.state.showMenu} itemRole="menuitem" />
                    <div role="presentation" className="richEditor-nubPosition">
                        <div className="richEditor-nub" />
                    </div>
                </div>
            </div>
        );
    }

    private handleFocusChange = hasFocus => {
        if (!hasFocus) {
            this.setState({ showMenu: false });
        }
    };

    private initializeToolbarValues() {
        const initialToolbarItems: any = {};

        // Parse our items that we use for detecting the active state into a format the toolbar can represent.
        for (const [formatName, contents] of Object.entries(PARAGRAPH_ITEMS)) {
            if (formatName === "header") {
                for (const [enableValue, headerContents] of Object.entries(contents)) {
                    initialToolbarItems[headerContents.name] = {
                        formatName,
                        enableValue: parseInt(enableValue, 10),
                        active: false,
                    };
                }
            } else {
                initialToolbarItems[(contents as any).name] = {
                    formatName,
                    enableValue: true,
                    active: false,
                };

                if (formatName === "code-block") {
                    initialToolbarItems[(contents as any).name].formatter = this.codeFormatter;
                }
            }
        }

        const pilcrow = {
            formatName: "pilcrow",
            active: true,
            enableValue: null,
            isFallback: true,
            formatter: () => {
                // We have to grab the current line directly and work with it because quill doesn't properly unformat the code block otherwise.
                const blot: Blot = this.quill.getLine(this.state.range.index)[0];
                blot.replaceWith("block");
                this.quill.update(Quill.sources.USER);
                this.quill.setSelection(this.state.range, Quill.sources.USER);
            },
        };

        this.toolbarItems = {
            pilcrow,
            ...initialToolbarItems,
        };
    }

    /**
     * Be sure to strip out all other formats before formatting as code.
     */
    private codeFormatter = () => {
        const selection = this.quill.getSelection(true);
        const [line] = this.quill.getLine(selection.index);
        this.quill.removeFormat(line.offset(), line.length(), Quill.sources.API);
        this.quill.formatLine(line.offset(), line.length(), "code-block", Quill.sources.USER);
    };

    /**
     * Close the menu.
     */
    private closeMenu = () => {
        const activeElement = document.activeElement;
        const parentElement = document.getElementById(this.componentID);

        this.setState({
            showMenu: false,
        });

        if (parentElement && activeElement && parentElement.contains(activeElement)) {
            const button = document.getElementById(this.buttonID);
            if (button instanceof HTMLElement) {
                button.focus();
            }
        }
    };

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    private escFunction = event => {
        if (event.keyCode === 27 && this.state.showMenu) {
            this.closeMenu();
            const button = document.getElementById(this.buttonID);
            if (button instanceof HTMLElement) {
                button.focus();
            }
        }
    };

    /**
     * Handle changes from the editor.
     *
     * @param type - The event type. See {quill/core/emitter}
     * @param range - The new range.
     * @param oldRange - The old range.
     * @param source - The source of the change.
     */
    private handleEditorChange = (type: string, range: RangeStatic, oldRange: RangeStatic, source: Sources) => {
        if (range) {
            if (typeof range.index !== "number") {
                range = this.quill.getSelection();
            }

            // Check which paragraph formatting items are ready.
            if (range != null) {
                const activeFormats = this.quill.getFormat(range);
                let activeFormatKey = "pilcrow";
                for (const [formatName, formatValue] of Object.entries(activeFormats)) {
                    if (formatName in PARAGRAPH_ITEMS) {
                        let item = PARAGRAPH_ITEMS[formatName];

                        // In case its a heading
                        if (formatName === "header" && (formatValue as string) in item) {
                            item = item[formatValue as string];
                        }

                        activeFormatKey = item.name;
                    }
                }

                const [descendantAtIndex] = this.quill.scroll.descendant(FocusableEmbedBlot as any, range.index);

                this.setState({
                    range,
                    activeFormatKey,
                    isEmbedFocused: !!descendantAtIndex,
                });
            }
        }

        if (source !== Quill.sources.SILENT) {
            this.setState({
                showMenu: false,
            });
        }

        let numLines = 0;

        if (range) {
            numLines = this.quill.getLines(range.index || 0, range.length || 0).length;
        }

        if (numLines <= 1 && !this.state.showPilcrow) {
            this.setState({
                showPilcrow: true,
            });
        } else if (numLines > 1) {
            this.setState({
                showPilcrow: false,
            });
        }
    };

    private getPilcrowStyles() {
        const bounds = this.quill.getBounds(this.state.range.index, this.state.range.length);

        // This is the pixel offset from the top needed to make things align correctly.
        const offset = 9 + 2;

        return {
            top: (bounds.top + bounds.bottom) / 2 - offset,
        };
    }

    private getToolbarClasses() {
        const bounds = this.quill.getBounds(this.state.range.index, this.state.range.length);
        let classes = "richEditor-toolbarContainer richEditor-paragraphToolbarContainer";

        if (bounds.top > 30) {
            classes += " isUp";
        } else {
            classes += " isDown";
        }

        return classes;
    }

    private getToolbarStyles() {
        const hiddenStyles = {
            visibility: "hidden",
            position: "absolute",
            zIndex: -1,
        };

        return this.state.showMenu ? {} : hiddenStyles;
    }

    /**
     * Click handler for the Pilcrow
     */
    private pilcrowClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.setState({
            showMenu: !this.state.showMenu,
        });
        const menu = document.getElementById(this.menuID);
        const firstButton = menu ? menu.querySelector(".richEditor-button") : false;
        if (firstButton instanceof HTMLElement) {
            setImmediate(() => {
                firstButton.focus();
            });
        }
    };

    /**
     * Get element containing menu items
     */
    private getMenuContainer = () => {
        const parentElement = document.getElementById(this.menuID);
        if (parentElement) {
            const menu = parentElement.querySelector(".richEditor-menuItems");
            if (menu) {
                return menu;
            }
        }
        return false;
    };

    /**
     * Handle key presses
     */
    private handleKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowUp":
                event.preventDefault();
                this.setState(
                    {
                        showMenu: true,
                    },
                    () => {
                        setImmediate(() => {
                            const menu = this.getMenuContainer();
                            if (menu instanceof HTMLElement && menu.firstChild instanceof HTMLElement) {
                                menu.firstChild.focus();
                            }
                        });
                    },
                );
                break;
            case "ArrowDown":
                event.preventDefault();
                this.setState(
                    {
                        showMenu: true,
                    },
                    () => {
                        setImmediate(() => {
                            const menu = this.getMenuContainer();
                            if (menu instanceof HTMLElement && menu.lastChild instanceof HTMLElement) {
                                menu.lastChild.focus();
                            }
                        });
                    },
                );
                break;
        }
    };
}

export default withEditor(ParagraphToolbar);
