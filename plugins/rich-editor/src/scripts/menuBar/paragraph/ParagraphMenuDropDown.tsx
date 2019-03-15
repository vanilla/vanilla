/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill from "quill/core";
import Formatter from "@rich-editor/quill/Formatter";
import DropDown from "@library/flyouts/DropDown";
import * as editorIcons from "@library/icons/editorIcons";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { IWithEditorProps, withEditor } from "@rich-editor/editor/context";
import ActiveFormatIcon from "@rich-editor/toolbars/pieces/ActiveFormatIcon";
import MenuItems from "@rich-editor/toolbars/pieces/MenuItems";
import ParagraphMenuBarItems from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarItems";

interface IProps extends IWithEditorProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
}

interface IState {
    hasFocus: boolean;
}

/**
 * Implemented ParagraphMenuDropDown component, this is for mobile
 */
export class ParagraphMenuDropDown extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID: string;
    private menuRef: React.RefObject<MenuItems> = React.createRef();
    private formatter: Formatter;

    constructor(props: IProps) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
        this.formatter = new Formatter(this.quill);
        this.ID = this.props.editorID + "paragraphMenu";
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
                buttonBaseClass={ButtonTypes.ICON}
                disabled={this.props.disabled}
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
                contentsClassName="noMinWidth"
            >
                <ParagraphMenuBarItems
                    menuRef={this.menuRef}
                    formatter={this.formatter}
                    activeFormats={<ActiveFormatIcon activeFormats={this.props.activeFormats} />}
                    lastGoodSelection={this.props.lastGoodSelection}
                    onKeyDown={this.handlePilcrowKeyDown}
                />
            </DropDown>
        );
    }

    /**
     * Implement opening/closing keyboard shortcuts in accordance with the WAI-ARIA best practices for menuitems.
     *
     * @see https://www.w3.org/TR/wai-aria-practices/examples/menubar/menubar-2/menubar-2.html
     */
    private handlePilcrowKeyDown = (event: React.KeyboardEvent<any>) => {
        switch (event.key) {
            // Activates menu item, causing action to be executed, e.g., bold text, change font.
            case "Space":
            case "Enter":
                break;
            // Closes submenu.
            // Moves focus to parent menubar item.
            case "Escape":
                break;
            // Closes submenu.
            // Moves focus to next item in the menubar.
            // Opens submenu of newly focused menubar item, keeping focus on that parent menubar item.
            case "ArrowRight":
                break;
            // Closes submenu.
            // Moves focus to previous item in the menubar.
            // Opens submenu of newly focused menubar item, keeping focus on that parent menubar item.
            case "ArrowLeft":
                break;
            // Moves focus to previous item in the submenu.
            // If focus is on the first item, moves focus to the last item.
            case "ArrowUp":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusFirstItem();
                });
                break;
            // Moves focus to the next item in the submenu.
            // If focus is on the last item, moves focus to the first item.
            case "ArrowDown":
                event.preventDefault();
                this.setState({ hasFocus: true }, () => {
                    this.menuRef.current!.focusLastItem();
                });
                break;
            // 	Moves focus to the first item in the submenu.
            case "Home":
                break;
            // 	Moves focus to the last item in the submenu.
            case "End":
                break;
            // Moves focus to the next item having a name that starts with the typed character.
            // If none of the items have a name starting with the typed character, focus does not move.
            default:
                break;
        }
    };
}

export default withEditor<IProps>(ParagraphMenuDropDown);
