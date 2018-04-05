/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill from "quill/core";
import { RangeStatic, Sources } from "quill";
import Emitter from "quill/core/emitter";
import { t } from "@core/application";
import Toolbar from "./Generic/Toolbar";
import { pilcrow as PilcrowIcon } from "./Icons";
import { closeEditorFlyouts, CLOSE_FLYOUT_EVENT } from "../Quill/utility";
import { withEditor, IEditorContextProps } from "./ContextProvider";

const menuItems = {
    title: {
        formatName: "header",
        enableValue: 1,
        active: false,
    },
    subtitle: {
        formatName: "header",
        enableValue: 2,
        active: false,
    },
    blockquote: {
        formatName: "blockquote-line",
        active: false,
    },
    codeBlock: {
        formatName: "code-block",
        active: false,
    },
    spoiler: {
        formatName: "spoiler-line",
        active: false,
    },
};

interface IState {
    range: RangeStatic;
    showMenu: boolean;
    showPilcrow: boolean;
}

export class ParagraphToolbar extends React.PureComponent<IEditorContextProps, IState> {

    private quill: Quill;
    private toolbarNode: HTMLElement;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;

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
            showMenu: false,
            range: {
                index: 0,
                length: 0,
            },
        };
    }

    /**
     * Mount quill listeners.
     */
    public componentDidMount() {
        this.quill.on(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.addEventListener("keydown", this.escFunction, false);
        document.addEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Emitter.events.EDITOR_CHANGE, this.handleEditorChange);
        document.removeEventListener("keydown", this.escFunction, false);
        document.removeEventListener(CLOSE_FLYOUT_EVENT, this.closeMenu);
    }

    public render() {
        let pilcrowClasses = "richEditor-button richEditorParagraphMenu-handle";
        if (!this.state.showPilcrow) {
            pilcrowClasses += " isHidden";
        }

        return <div id={this.componentID} style={this.getPilcrowStyles()} className="richEditor-menu richEditorParagraphMenu">
            <button
                type="button"
                id={this.buttonID}
                aria-label={t('richEditor.menu.paragraph')}
                aria-controls={this.menuID}
                aria-expanded={this.state.showMenu}
                disabled={!this.state.showPilcrow}
                className={pilcrowClasses}
                aria-haspopup="menu"
                onClick={this.pilcrowClickHandler}
                onKeyDown={this.handleKeyPress}
            >
                <PilcrowIcon/>
            </button>
            <div id={this.menuID} className={this.getToolbarClasses()} style={this.getToolbarStyles()} ref={(ref) => this.toolbarNode = ref} role="menu">
                <Toolbar quill={this.quill} menuItems={menuItems} isHidden={!this.state.showMenu} checkForExternalFocus={this.checkForExternalFocus} itemRole="menuitem"/>
                <div role="presentation" className="richEditor-nubPosition">
                    <div className="richEditor-nub"/>
                </div>
            </div>
        </div>;
    }

    /**
     * Close the menu.
     */
    private closeMenu = (event: any) => {
        if (event.detail && event.detail.firingKey === this.constructor.name) {
            return;
        }

        const activeElement = document.activeElement;
        const parentElement = document.getElementById(this.componentID);

        this.setState({
            showMenu: false,
        });

        if (parentElement && activeElement && parentElement.contains(activeElement)) {
            document.getElementById(this.buttonID).focus();
        }
    }

    /**
     * Handle the escape key.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    private escFunction = (event) => {
        if(event.keyCode === 27 && this.state.showMenu) {
            this.closeMenu(event);
            document.getElementById(this.buttonID).focus();
        }
    }

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

            if (range != null) {
                this.setState({
                    range,
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
            numLines = this.quill.getLines(range.index || 0, range.length || 0);
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
    }

    private getPilcrowStyles() {
        const bounds = this.quill.getBounds(this.state.range);

        // This is the pixel offset from the top needed to make things align correctly.
        const offset = 9 + 2;

        return {
            top: (bounds.top + bounds.bottom) / 2 - offset,
        };
    }

    private getToolbarClasses() {
        const bounds = this.quill.getBounds(this.state.range);
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
        closeEditorFlyouts(this.constructor.name);
        const menu = document.getElementById(this.menuID);
        const firstButton = menu ? menu.querySelector('.richEditor-button') : false;
        if (firstButton instanceof HTMLElement) {
            setImmediate(() => {
                firstButton.focus();
            });
        }
    };

    /**
     * Close if we lose focus on the component
     */
    private checkForExternalFocus = (event: React.FocusEvent<any>) => {
        setImmediate(() => {
            const activeElement = document.activeElement;
            const paragraphMenu = document.getElementById(this.componentID);
            if (activeElement !== paragraphMenu && !paragraphMenu.contains(activeElement)) {
                this.closeMenu(event);
            }
        });
    };

    /**
     * Get element containing menu items
     */
    private getMenuContainer = () => {
        const parentElement = document.getElementById(this.menuID);
        if (parentElement) {
            const menu = parentElement.querySelector('.richEditor-menuItems');
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
            this.setState({
                showMenu: true,
            }, () => {
                setImmediate(() => {
                    const menu = this.getMenuContainer();
                    if (menu instanceof HTMLElement && menu.firstChild instanceof HTMLElement) {
                        menu.firstChild.focus();
                    }
                });
            });
            break;
        case "ArrowDown":
            event.preventDefault();
            this.setState({
                showMenu: true,
            }, () => {
                setImmediate(() => {
                    const menu = this.getMenuContainer();
                    if (menu instanceof HTMLElement && menu.lastChild instanceof HTMLElement) {
                        menu.lastChild.focus();
                    }
                });
            });
            break;
        }
        closeEditorFlyouts(this.constructor.name);
    }
}

export default withEditor(ParagraphToolbar);
