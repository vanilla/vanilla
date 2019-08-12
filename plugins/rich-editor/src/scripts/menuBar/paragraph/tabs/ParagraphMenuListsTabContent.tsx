/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuCheckRadioClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { t } from "@library/utility/appUtils";
import ParagraphMenuBarRadioGroup, {
    IMenuBarRadioButton,
} from "@rich-editor/menuBar/paragraph/items/ParagraphMenuBarRadioGroup";
import ParagraphMenuSeparator from "@rich-editor/menuBar/paragraph/items/ParagraphMenuSeparator";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { IndentIcon, OutdentIcon } from "@library/icons/editorIcons";

interface IProps {
    closeMenu: () => void;
    closeMenuAndSetCursor: () => void;
    items: IMenuBarRadioButton[];
    setRovingIndex: () => void;
    indent: () => void;
    outdent: () => void;
    canIndent: boolean;
    canOutdent: boolean;
    disabled?: boolean;
}

/**
 * Implemented tab content for menu list
 */
export default class ParagraphMenuListsTabContent extends React.Component<IProps> {
    public render() {
        const classes = richEditorClasses(false);
        const checkRadioClasses = paragraphMenuCheckRadioClasses();
        const handleClick = (data: IMenuBarRadioButton, index: number) => {
            this.props.items[index].formatFunction();
            this.props.setRovingIndex();
            this.props.closeMenuAndSetCursor();
        };
        const indentDisabled = !!this.props.disabled || !this.props.canIndent;
        const outdentDisabled = !!this.props.disabled || !this.props.canOutdent;
        const indentLabel = t("Indent");
        const outdentLabel = t("Outdent");
        return (
            <>
                <ParagraphMenuBarRadioGroup
                    className={classes.paragraphMenuPanel}
                    label={t("List Types")}
                    items={this.props.items}
                    handleClick={handleClick}
                    disabled={!!this.props.disabled}
                />
                <ParagraphMenuSeparator />
                <div className={checkRadioClasses.group}>
                    <button
                        className={classNames(checkRadioClasses.checkRadio)}
                        type="button"
                        onClick={this.props.indent}
                        disabled={indentDisabled}
                        tabIndex={indentDisabled ? -1 : 0}
                        data-firstletter={indentLabel.toLowerCase().substr(0, 1)}
                    >
                        <span className={checkRadioClasses.icon}>{<IndentIcon />}</span>
                        <span className={checkRadioClasses.checkRadioLabel}>{indentLabel}</span>
                    </button>
                    <button
                        className={classNames(checkRadioClasses.checkRadio)}
                        type="button"
                        onClick={this.props.outdent}
                        disabled={outdentDisabled}
                        tabIndex={outdentDisabled ? -1 : 0}
                        data-firstletter={outdentLabel.toLowerCase().substr(0, 1)}
                    >
                        <span className={checkRadioClasses.icon}>{<OutdentIcon />}</span>
                        <span className={checkRadioClasses.checkRadioLabel}>{outdentLabel}</span>
                    </button>
                </div>
            </>
        );
    }
}
