/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Grid, AutoSizer } from "react-virtualized";
import classNames from "classnames";
import { t } from "@dashboard/application";
import * as Icons from "@rich-editor/components/icons";
import Popover from "./Popover";
import { IPopoverControllerChildParameters } from "./PopoverController";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { EMOJI_GROUPS, EMOJIS } from "./emojiData";
import EmojiButton from "./EmojiButton";

const BUTTON_SIZE = 36;
const COL_SIZE = 7;
const ROW_SIZE = 7;
const rowIndexesByGroupId = {};
const cellIndexesByGroupId = {};

// window.console.log("EMOJIS: ", EMOJIS.length);

/**
 * Get start positions for each category
 */
EMOJIS.forEach((data, key) => {
    const groupID = data.group;
    if (!(groupID in rowIndexesByGroupId)) {
        rowIndexesByGroupId[groupID] = Math.floor(key / COL_SIZE);
        cellIndexesByGroupId[groupID] = key;
    }
});

// window.console.log("rowIndexesByGroupId: ", rowIndexesByGroupId);
// window.console.log("cellIndexesByGroupId: ", cellIndexesByGroupId);

const emojiGroupLength = Object.values(EMOJI_GROUPS).length;
const lastEmojiIndex = EMOJIS.length - 1;

interface IProps extends IWithEditorProps, IPopoverControllerChildParameters {
    contentID: string;
}

interface IState {
    id: string;
    contentID: string;
    activeIndex: number;
    alertMessage?: string;
    title: string;
}

export class EmojiPicker extends React.PureComponent<IProps, IState> {
    private categoryPickerID: string;
    private gridEl: Grid;
    private rowCount = this.getRowFromIndex(EMOJIS.length) + 1;

    constructor(props) {
        super(props);
        this.state = {
            id: props.id,
            contentID: props.contentID,
            activeIndex: 0,
            title: t("Emojis"),
        };

        this.categoryPickerID = "emojiPicker-categories-" + props.editorID;
    }

    get descriptionID(): string {
        return this.state.id + "-description";
    }
    get titleID(): string {
        return this.state.id + "-title";
    }

    public render() {
        const description = [
            t("Insert an emoji in your message."),
            t(
                'Use keyboard shortcuts "page up" and "page down" to cycle through available categories when menu is open.',
            ),
        ].join(" ");

        const extraHeadingContent = (
            <button type="button" className="accessibility-jumpTo" onClick={this.focusOnCategories}>
                {t("Jump past emoji list, to emoji categories.")}
            </button>
        );

        const Icon = <Icons.emoji />;

        const footer = (
            <div id={this.categoryPickerID} className="emojiGroups" aria-label={t("Emoji Categories")} tabIndex={-1}>
                {Object.values(EMOJI_GROUPS).map((groupName: string, groupKey) => {
                    const isSelected = this.activeCategoryIndex === groupKey;
                    const buttonClasses = classNames("richEditor-button", "emojiGroup", { isSelected });

                    const onClick = event => this.handleCategoryClick(event, groupKey);

                    return (
                        <button
                            type="button"
                            onClick={onClick}
                            aria-current={isSelected}
                            aria-label={t("Jump to emoji category: ") + t(groupName)}
                            key={"emojiGroup-" + groupName}
                            title={t(groupName)}
                            className={buttonClasses}
                        >
                            {this.getGroupSVGPath(groupName)}
                            <span className="sr-only">{t("Jump to Category: ") + t(groupName)}</span>
                        </button>
                    );
                })}
            </div>
        );

        const grid = (
            <AutoSizer>
                {({ height, width }) => (
                    <Grid
                        containerRole=""
                        cellRenderer={this.cellRenderer}
                        columnCount={COL_SIZE}
                        columnWidth={BUTTON_SIZE}
                        rowCount={this.rowCount}
                        rowHeight={BUTTON_SIZE}
                        height={height}
                        width={width}
                        overscanRowCount={20}
                        tabIndex={-1}
                        scrollToAlignment="start"
                        scrollToRow={this.activeRow}
                        aria-readonly={undefined}
                        aria-label={""}
                        role={""}
                        // onScroll={this.handleEmojiScroll}
                        onSectionRendered={this.handleOnSectionRendered}
                        ref={gridEl => {
                            this.gridEl = gridEl as Grid;
                        }}
                    />
                )}
            </AutoSizer>
        );

        return (
            <Popover
                id={this.state.id}
                descriptionID={this.descriptionID}
                titleID={this.titleID}
                title={this.state.title}
                titleRef={this.props.initialFocusRef}
                accessibleDescription={description}
                alertMessage={this.state.alertMessage}
                additionalHeaderContent={extraHeadingContent}
                body={grid}
                footer={footer}
                additionalClassRoot="insertEmoji"
                onCloseClick={this.props.closeMenuHandler}
                isVisible={this.props.isVisible}
            />
        );
    }

    public componentDidMount() {
        document.addEventListener("keydown", this.handleKeyDown, false);
    }

    public componentWillUnmount() {
        document.removeEventListener("keydown", this.handleKeyDown, false);
    }

    private getRowFromIndex(index): number {
        return Math.floor(index / COL_SIZE);
    }

    private get activeRow(): number {
        return this.getRowFromIndex(this.state.activeIndex);
    }

    private get activeColumn(): number {
        return this.state.activeIndex % COL_SIZE;
    }

    private get activeCategoryIndex(): number {
        return 0;
    }

    /**
     * Handler when new rows are rendered. We use this to figure out what category is current
     */
    private handleOnSectionRendered = event => {
        const newRowIndex = event.rowStartIndex;
        let selectedGroup = 0;

        Object.values(rowIndexesByGroupId).map((groupRow, groupKey) => {
            if (newRowIndex >= groupRow) {
                selectedGroup = groupKey;
            }
        });

        this.setState({
            alertMessage: t("In emoji category: ") + t(EMOJI_GROUPS[selectedGroup]),
            title: t(EMOJI_GROUPS[selectedGroup]),
        });
    };

    private handleCategoryClick(event: React.MouseEvent<any>, categoryID: number) {
        event.preventDefault();
        event.stopPropagation();
        this.scrollToCategory(categoryID);
    }

    /**
     * Scroll to category
     */
    private scrollToCategory = (categoryID: number) => {
        window.console.log("categoryID: ", categoryID);
        this.setState({
            activeIndex: cellIndexesByGroupId[categoryID],
            alertMessage: t("Jumped to emoji category: ") + t(EMOJI_GROUPS[categoryID]),
        });
    };

    /**
     * Render list row
     */
    private cellRenderer = ({ columnIndex, rowIndex, style }) => {
        const pos = rowIndex * ROW_SIZE + columnIndex;
        const emojiData = EMOJIS[pos];

        return emojiData ? (
            <EmojiButton
                activeIndex={this.state.activeIndex}
                style={style}
                closeMenuHandler={this.props.closeMenuHandler}
                key={"emoji-" + emojiData.emoji}
                emojiData={emojiData}
                index={pos}
                onKeyUp={this.jumpRowUp}
                onKeyDown={this.jumpRowDown}
            />
        ) : null;
    };

    /**
     * Get Group SVG Path
     */
    private getGroupSVGPath = (groupName: string) => {
        const functionSuffix = groupName.replace(/-([a-z])/g, g => g[1].toUpperCase());
        return Icons["emojiGroup_" + functionSuffix]();
    };

    /**
     * Focus on Emoji Categories
     */
    private focusOnCategories = () => {
        const categories = document.getElementById(this.categoryPickerID);
        if (categories) {
            const firstButton = categories.querySelector(".richEditor-button");
            if (firstButton instanceof HTMLElement) {
                firstButton.focus();
            }
        }
    };

    /**
     * Jump to adjacent category
     *
     * @param offset - How many rows to jump
     */

    private jumpRows = (offset: number) => {
        let newIndex = this.state.activeIndex + offset;
        newIndex = Math.min(newIndex, EMOJIS.length - 1);
        newIndex = Math.max(newIndex, 0);
        console.log("Jumping to new index!");
        this.setState({ activeIndex: newIndex });
    };

    private jumpRowDown = () => {
        this.jumpRows(ROW_SIZE);
    };

    private jumpRowUp = () => {
        this.jumpRows(-ROW_SIZE);
    };

    /**
     * Handle key press.
     *
     * @param event - A synthetic keyboard event.
     */
    private handleKeyDown = (event: KeyboardEvent) => {
        if (this.props.isVisible) {
            switch (event.code) {
                case "PageUp":
                    event.preventDefault();
                    event.stopPropagation();
                    this.jumpRows(-ROW_SIZE);
                    break;
                case "PageDown":
                    event.preventDefault();
                    event.stopPropagation();
                    this.jumpRows(ROW_SIZE);
                    break;
            }
        }
    };
}

export default withEditor<IProps>(EmojiPicker);
