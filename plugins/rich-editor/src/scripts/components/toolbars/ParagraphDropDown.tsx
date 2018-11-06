/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import HeadingBlot from "quill/formats/header";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import Formatter from "@rich-editor/quill/Formatter";
import ParagraphToolbarMenuItems from "@rich-editor/components/toolbars/pieces/ParagraphToolbarMenuItems";
import CodeBlockBlot from "@rich-editor/quill/blots/blocks/CodeBlockBlot";
import BlockquoteLineBlot from "@rich-editor/quill/blots/blocks/BlockquoteBlot";
import SpoilerLineBlot from "@rich-editor/quill/blots/blocks/SpoilerBlot";
import MenuItems from "@rich-editor/components/toolbars/pieces/MenuItems";
import classNames from "classnames";
import FocusWatcher from "@library/FocusWatcher";
import DropDown from "@library/components/dropdown/DropDown";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { heading2, heading3, blockquote, codeBlock, spoiler } from "@library/components/icons/editor";
import * as editorIcons from "@library/components/icons/editor";

interface IProps extends IWithEditorProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
}

interface IState {
    hasFocus: boolean;
}

export class ParagraphDropDown extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID: string;
    private componentID: string;
    private menuID: string;
    private buttonID: string;
    private selfRef: React.RefObject<HTMLDivElement> = React.createRef();
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();
    private menuRef: React.RefObject<MenuItems> = React.createRef();
    private formatter: Formatter;
    private focusWatcher: FocusWatcher;

    constructor(props: IProps) {
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

    public render() {
        return (
            <DropDown
                id={this.ID}
                className="mobileParagraphMenu-dropDown"
                buttonContents={editorIcons.pilcrow()}
                buttonBaseClass={ButtonBaseClass.ICON}
                disabled={this.props.disabled}
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
            >
                <ParagraphToolbarMenuItems
                    menuRef={this.menuRef}
                    formatter={this.formatter}
                    activeFormats={this.props.activeFormats}
                    lastGoodSelection={this.props.instanceState.lastGoodSelection}
                />
            </DropDown>
        );
    }

    /**
     * Get the active format for the current line.
     */
    private get activeFormatIcon(): JSX.Element {
        const { activeFormats } = this.props;
        const headingFormat = activeFormats[HeadingBlot.blotName];
        if (typeof headingFormat === "object") {
            if (headingFormat.level === 2) {
                return heading2();
            }
            if (headingFormat.level === 3) {
                return heading3();
            }
        }
        if (headingFormat === 2) {
            return heading2();
        }
        if (headingFormat === 3) {
            return heading3();
        }
        if (activeFormats[BlockquoteLineBlot.blotName] === true) {
            return blockquote();
        }
        if (activeFormats[CodeBlockBlot.blotName] === true) {
            return codeBlock();
        }
        if (activeFormats[SpoilerLineBlot.blotName] === true) {
            return spoiler("richEditorButton-icon");
        }

        // Fallback to paragraph formatting.
        return editorIcons.pilcrow();
    }

    /**
     * Implement opening/closing keyboard shortcuts in accordance with the WAI-ARIA best practices for menuitems.
     *
     * @see https://www.w3.org/TR/wai-aria-practices/examples/menubar/menubar-2/menubar-2.html
     */
    private handlePilcrowKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            case "ArrowUp":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusFirstItem();
                });
                break;
            case "ArrowDown":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusLastItem();
                });
                break;
        }
    };
}

export default withEditor<IProps>(ParagraphDropDown);
