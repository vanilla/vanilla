/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill from "quill/core";
import { t } from "@dashboard/application";
import * as Icons from "@rich-editor/components/icons";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { watchFocusInDomTree } from "@dashboard/dom";
import { createEditorFlyoutEscapeListener, isEmbedSelected } from "@rich-editor/quill/utility";
import Formatter from "@rich-editor/quill/Formatter";
import ParagraphToolbarMenuItems from "@rich-editor/components/toolbars/pieces/ParagraphToolbarMenuItems";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import HeadingBlot from "quill/formats/header";

interface IProps extends IWithEditorProps {}

interface IState {
    hasFocus: boolean;
}

export class ParagraphToolbar extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private toolbarNode: HTMLElement;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private formatter: Formatter;

    /**
     * @inheritDoc
     */

    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.formatter = new Formatter(this.quill);
        this.ID = this.props.editorID + "paragraphMenu";
        this.componentID = this.ID + "-component";
        this.menuID = this.ID + "-menu";
        this.buttonID = this.ID + "-button";
        this.state = {
            hasFocus: false,
        };
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

        if (!this.isPilcrowVisible || isEmbedSelected(this.quill, this.props.instanceState.lastGoodSelection)) {
            pilcrowClasses += " isHidden";
        }

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
                    {this.activeFormatIcon}
                </button>
                <div
                    id={this.menuID}
                    className={this.toolbarClasses}
                    style={this.toolbarStyles}
                    ref={ref => (this.toolbarNode = ref!)}
                    role="menu"
                >
                    <ParagraphToolbarMenuItems
                        formatter={this.formatter}
                        activeFormats={this.props.activeFormats}
                        lastGoodSelection={this.props.instanceState.lastGoodSelection}
                    />
                    <div role="presentation" className="richEditor-nubPosition">
                        <div className="richEditor-nub" />
                    </div>
                </div>
            </div>
        );
    }

    /**
     * Get the active format for the current line.
     */
    private get activeFormatIcon(): JSX.Element {
        const { activeFormats } = this.props;
        if (activeFormats[HeadingBlot.blotName] === 2) {
            return Icons.heading2();
        }
        if (activeFormats[HeadingBlot.blotName] === 3) {
            return Icons.heading3();
        }
        if (activeFormats[BlockquoteLineBlot.blotName] === true) {
            return Icons.blockquote();
        }
        if (activeFormats[CodeBlockBlot.blotName] === true) {
            return Icons.codeBlock();
        }
        if (activeFormats[SpoilerLineBlot.blotName] === true) {
            return Icons.spoiler();
        }

        // Fallback to paragraph formatting.
        return Icons.pilcrow();
    }

    /**
     * Determine whether or not we should show pilcrow at all.
     */
    private get isPilcrowVisible() {
        const { currentSelection } = this.props.instanceState;
        if (!currentSelection) {
            return false;
        }

        const numLines = this.quill.getLines(currentSelection.index || 0, currentSelection.length || 0).length;
        return numLines <= 1;
    }

    /**
     * Show the menu if we have a valid selection, and a valid focus.
     */
    private get isMenuVisible() {
        const { instanceState } = this.props;
        return !!instanceState.lastGoodSelection && this.state.hasFocus;
    }

    /**
     * Get the inline styles for the pilcrow. This is mostly just positioning it on the Y access currently.
     */
    private get pilcrowStyles(): React.CSSProperties {
        const { instanceState } = this.props;

        if (!instanceState.lastGoodSelection) {
            return {};
        }
        const bounds = this.quill.getBounds(
            instanceState.lastGoodSelection.index,
            instanceState.lastGoodSelection.length,
        );

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
        const { instanceState } = this.props;

        if (!instanceState.lastGoodSelection) {
            return "";
        }
        const bounds = this.quill.getBounds(
            instanceState.lastGoodSelection.index,
            instanceState.lastGoodSelection.length,
        );
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
        if (this.isMenuVisible && !isEmbedSelected(this.quill, this.props.instanceState.lastGoodSelection)) {
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

export default withEditor<IProps>(ParagraphToolbar);
