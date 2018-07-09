/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { Blot } from "quill/core";
import { RangeStatic, Sources } from "quill";
import { t } from "@dashboard/application";
import MenuItems from "./pieces/MenuItems";
import * as Icons from "@rich-editor/components/icons";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";
import { IMenuItemData } from "./pieces/MenuItem";
import { watchFocusInDomTree } from "@dashboard/dom";
import { createEditorFlyoutEscapeListener, getIDForQuill, getBlotAtIndex } from "@rich-editor/quill/utility";
import { connect } from "react-redux";
import IStoreState from "@rich-editor/state/IState";
import { FOCUS_CLASS } from "@dashboard/embeds";
import FocusableEmbedBlot from "@rich-editor/quill/blots/abstract/FocusableEmbedBlot";

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

interface IOwnProps extends IEditorContextProps {}

interface IProps extends IOwnProps {
    lastGoodSelection?: RangeStatic;
    currentSelection?: RangeStatic;
}

interface IState {
    hasFocus: boolean;
}

export class ParagraphToolbar extends React.Component<IProps, IState> {
    private quill: Quill;
    private toolbarNode: HTMLElement;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
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
            hasFocus: false,
        };
        this.initializeToolbarValues();
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        watchFocusInDomTree(this.selfRef.current!, newHasFocusState => {
            if (!newHasFocusState) {
                this.setState({ hasFocus: false });
            }
        });
        createEditorFlyoutEscapeListener(this.selfRef.current!, this.buttonRef.current!, () => {
            this.setState({ hasFocus: false });
        });
    }

    public render() {
        let pilcrowClasses = "richEditor-button richEditorParagraphMenu-handle";

        if (!this.isPilcrowVisible || this.isEmbedSelected) {
            pilcrowClasses += " isHidden";
        }

        const Icon = Icons[this.activeFormatKey];

        return (
            <div
                id={this.componentID}
                style={this.pilcrowStyles}
                className="richEditor-menu richEditorParagraphMenu"
                ref={this.selfRef}
            >
                <button
                    type="button"
                    id={this.buttonID}
                    ref={this.buttonRef}
                    aria-label={t("richEditor.menu.paragraph")}
                    aria-controls={this.menuID}
                    aria-expanded={this.isMenuVisible}
                    disabled={!this.isPilcrowVisible}
                    className={pilcrowClasses}
                    aria-haspopup="menu"
                    onClick={this.pilcrowClickHandler}
                    onKeyDown={this.handleKeyPress}
                >
                    <Icon />
                </button>
                <div
                    id={this.menuID}
                    className={this.toolbarClasses}
                    style={this.toolbarStyles}
                    ref={ref => (this.toolbarNode = ref!)}
                    role="menu"
                >
                    <MenuItems menuItems={this.toolbarItems} isHidden={!this.isMenuVisible} itemRole="menuitem" />
                    <div role="presentation" className="richEditor-nubPosition">
                        <div className="richEditor-nub" />
                    </div>
                </div>
            </div>
        );
    }

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
            formatter: this.paragraphFormatter,
        };

        this.toolbarItems = {
            pilcrow,
            ...initialToolbarItems,
        };
    }

    /**
     * Grab the current line directly and format it as a paragraph.
     */
    private paragraphFormatter = () => {
        if (!this.props.lastGoodSelection) {
            return;
        }
        const blot: Blot = this.quill.getLine(this.props.lastGoodSelection.index)[0];
        blot.replaceWith("block");
        this.quill.update(Quill.sources.USER);
        this.quill.setSelection(this.props.lastGoodSelection, Quill.sources.USER);
    };

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
     * Get the active format for the current line.
     */
    private get activeFormatKey() {
        const { lastGoodSelection } = this.props;
        const DEFAULT_FORMAT_KEY = "pilcrow";
        if (!lastGoodSelection) {
            return DEFAULT_FORMAT_KEY;
        }
        // Check which paragraph formatting items are ready.
        const activeFormats = this.quill.getFormat(lastGoodSelection);
        for (const [formatName, formatValue] of Object.entries(activeFormats)) {
            if (formatName in PARAGRAPH_ITEMS) {
                let item = PARAGRAPH_ITEMS[formatName];

                // In case its a heading
                if (formatName === "header" && (formatValue as string) in item) {
                    item = item[formatValue as string];
                }

                return item.name;
            }
        }

        return DEFAULT_FORMAT_KEY;
    }

    /**
     * Determine whether or not we should show pilcrow at all.
     */
    private get isPilcrowVisible() {
        const { lastGoodSelection } = this.props;
        if (!lastGoodSelection) {
            return false;
        }

        if (this.isEmbedSelected) {
            return false;
        }

        const numLines = this.quill.getLines(lastGoodSelection.index || 0, lastGoodSelection.length || 0).length;
        return numLines <= 1;
    }

    /**
     * Show the menu if we have a valid selection, and a valid focus.
     */
    private get isMenuVisible() {
        const { lastGoodSelection } = this.props;
        return !!lastGoodSelection && this.state.hasFocus;
    }

    /**
     * Determine if and Embed inside of this class is focused.
     */
    private get isEmbedSelected() {
        const { lastGoodSelection } = this.props;
        if (!lastGoodSelection) {
            return false;
        }
        const potentialEmbedBlot = getBlotAtIndex(this.quill, lastGoodSelection.index, FocusableEmbedBlot);
        return !!potentialEmbedBlot;
    }

    /**
     * Get the inline styles for the pilcrow. This is mostly just positioning it on the Y access currently.
     */
    private get pilcrowStyles(): React.CSSProperties {
        const { lastGoodSelection } = this.props;

        if (!lastGoodSelection) {
            return {};
        }
        const bounds = this.quill.getBounds(lastGoodSelection.index, lastGoodSelection.length);

        // This is the pixel offset from the top needed to make things align correctly.
        const offset = 9 + 2;

        return {
            top: (bounds.top + bounds.bottom) / 2 - offset,
        };
    }

    /**
     * Get the classes for the toolbar.
     */
    private get toolbarClasses(): string {
        const { lastGoodSelection } = this.props;

        if (!lastGoodSelection) {
            return "";
        }
        const bounds = this.quill.getBounds(lastGoodSelection.index, lastGoodSelection.length);
        let classes = "richEditor-toolbarContainer richEditor-paragraphToolbarContainer";

        if (bounds.top > 30) {
            classes += " isUp";
        } else {
            classes += " isDown";
        }

        return classes;
    }

    /**
     * Get the inline styles for the toolbar. This just a matter of hiding it.
     * This could likely be replaced by a CSS class in the future.
     */
    private get toolbarStyles(): React.CSSProperties {
        if (this.isMenuVisible && !this.isEmbedSelected) {
            return {};
        } else {
            // We hide the toolbar when its not visible.
            return {
                visibility: "hidden",
                position: "absolute",
                zIndex: -1,
            };
        }
    }

    /**
     * Click handler for the Pilcrow
     */
    private pilcrowClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.setState({ hasFocus: true });
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
    private get menuContainer() {
        const parentElement = document.getElementById(this.menuID);
        if (parentElement) {
            const menu = parentElement.querySelector(".richEditor-menuItems");
            if (menu) {
                return menu;
            }
        }
        return false;
    }

    /**
     * Handle key presses
     */
    private handleKeyPress = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowUp":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    setImmediate(() => {
                        const menu = this.menuContainer;
                        if (menu instanceof HTMLElement && menu.firstChild instanceof HTMLElement) {
                            menu.firstChild.focus();
                        }
                    });
                });
                break;
            case "ArrowDown":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    setImmediate(() => {
                        const menu = this.menuContainer;
                        if (menu instanceof HTMLElement && menu.lastChild instanceof HTMLElement) {
                            menu.lastChild.focus();
                        }
                    });
                });
                break;
        }
    };
}

/**
 * Map in the instance state of the current editor.
 */
function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { quill } = ownProps;
    if (!quill) {
        return {};
    }

    const id = getIDForQuill(quill);
    const instanceState = state.editor.instances[id];
    return instanceState;
}

const withRedux = connect(mapStateToProps);
export default withEditor(withRedux(ParagraphToolbar));
