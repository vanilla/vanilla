/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { Grid, AutoSizer } from "react-virtualized";
import classNames from "classnames";
import { t } from "@library/application";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import { EMOJIS, EMOJI_GROUPS } from "@rich-editor/components/popovers/pieces/emojiData";
import Popover from "@rich-editor/components/popovers/pieces/Popover";
import EmojiButton from "@rich-editor/components/popovers/pieces/EmojiButton";
import { IPopoverControllerChildParameters } from "@library/components/PopoverController";
import { emoji } from "@library/components/icons/editorIcons";
import { EmojiGroupButton } from "@rich-editor/components/popovers/pieces/EmojiGroupButton";

const BUTTON_SIZE = 36;
const COL_SIZE = 7;
const ROW_SIZE = 7;
const rowIndexesByGroupId = {};
const cellIndexesByGroupId = {};

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

const lastEmojiIndex = EMOJIS.length - 1;

interface IProps extends IWithEditorProps, IPopoverControllerChildParameters {
    contentID: string;
    renderAbove?: boolean;
    renderLeft?: boolean;
}

interface IState {
    id: string;
    contentID: string;
    activeIndex: number;
    rowStartIndex: number;
    scrollToRow: number;
    selectedGroupIndex: number;
    alertMessage?: string;
    title: string;
}

export class EmojiPicker extends React.PureComponent<IProps, IState> {
    private categoryPickerID: string;
    private gridEl: Grid;
    private lastRowIndex = this.getRowFromIndex(EMOJIS.length);

    constructor(props) {
        super(props);
        this.state = {
            id: props.id,
            contentID: props.contentID,
            activeIndex: 0,
            title: t("Emojis"),
            scrollToRow: 0,
            rowStartIndex: 0,
            selectedGroupIndex: 0,
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
        const description = [t("Insert an emoji in your message."), t("richEditor.emoji.pagingInstructions")].join(" ");

        const extraHeadingContent = (
            <button type="button" className="accessibility-jumpTo" onClick={this.focusOnCategories}>
                {t("Jump past emoji list, to emoji categories.")}
            </button>
        );

        const footer = (
            <div id={this.categoryPickerID} className="emojiGroups" aria-label={t("Emoji Categories")} tabIndex={-1}>
                {Object.values(EMOJI_GROUPS).map((group, groupIndex) => {
                    const { name, icon } = group;
                    const isSelected = this.state.selectedGroupIndex === groupIndex;

                    return (
                        <EmojiGroupButton
                            key={groupIndex}
                            name={name}
                            icon={icon}
                            isSelected={isSelected}
                            navigateToGroup={this.scrollToCategory}
                            groupIndex={groupIndex}
                        />
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
                        rowCount={this.lastRowIndex + 1}
                        rowHeight={BUTTON_SIZE}
                        height={height}
                        width={width}
                        overscanRowCount={20}
                        tabIndex={-1}
                        scrollToAlignment="start"
                        scrollToRow={this.state.scrollToRow}
                        aria-readonly={undefined}
                        aria-label={""}
                        role={""}
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
                renderAbove={this.props.renderAbove}
                renderLeft={this.props.renderLeft}
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

    /**
     * Handler when new rows are rendered. We use this to figure out what category is current
     */
    private handleOnSectionRendered = event => {
        const newRowIndex = event.rowStartIndex;
        let selectedGroupIndex = 0;

        Object.values(rowIndexesByGroupId).map((groupRow, groupKey) => {
            if (newRowIndex >= groupRow) {
                selectedGroupIndex = groupKey;
            }
        });

        this.setState({
            rowStartIndex: event.rowStartIndex,
            selectedGroupIndex,
            alertMessage: t("In emoji category: ") + t(EMOJI_GROUPS[selectedGroupIndex].name),
            title: t(EMOJI_GROUPS[selectedGroupIndex].name),
        });
    };

    /**
     * Scroll to category
     */
    private scrollToCategory = (categoryID: number) => {
        const newIndex = cellIndexesByGroupId[categoryID];
        this.setState({
            activeIndex: newIndex,
            scrollToRow: this.getRowFromIndex(newIndex),
            alertMessage: t("Jumped to emoji category: ") + t(EMOJI_GROUPS[categoryID].name),
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
                onKeyLeft={this.jumpIndexLeft}
                onKeyRight={this.jumpIndexRight}
            />
        ) : null;
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

    private jumpIndex = (offset: number) => {
        const targetFocusPosition = this.keepEmojiIndexInBounds(this.state.activeIndex + offset);
        const targetRowPosition = this.getRowFromIndex(targetFocusPosition);

        let scrollToRow;
        if (targetRowPosition >= this.state.rowStartIndex + ROW_SIZE) {
            scrollToRow = this.keepRowIndexInBounds(targetRowPosition - ROW_SIZE + 1);
        } else if (targetRowPosition < this.state.rowStartIndex) {
            scrollToRow = targetRowPosition;
        }
        this.setState({ activeIndex: targetFocusPosition, scrollToRow }, () => {
            this.gridEl.forceUpdate();
        });
    };

    /**
     * Make sure target row is within bounds
     *
     * @param targetRow
     */
    private keepRowIndexInBounds(targetRow: number) {
        if (targetRow < 0) {
            return 0;
        } else if (targetRow > this.lastRowIndex - 1) {
            return this.lastRowIndex - 1;
        } else {
            return targetRow;
        }
    }

    /**
     * Make sure target index is within bounds
     *
     * @param targetIndex
     */
    private keepEmojiIndexInBounds(targetIndex: number) {
        if (targetIndex < 0) {
            return 0;
        } else if (targetIndex > lastEmojiIndex) {
            return lastEmojiIndex;
        } else {
            return targetIndex;
        }
    }

    private jumpRowDown = () => {
        this.jumpIndex(COL_SIZE);
    };

    private jumpRowUp = () => {
        this.jumpIndex(-COL_SIZE);
    };

    private jumpIndexRight = () => {
        this.jumpIndex(1);
    };

    private jumpIndexLeft = () => {
        this.jumpIndex(-1);
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
                    event.stopImmediatePropagation();
                    this.jumpIndex(-ROW_SIZE * COL_SIZE);
                    break;
                case "PageDown":
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.jumpIndex(ROW_SIZE * COL_SIZE);
                    break;
                case "Home":
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.jumpIndex(-lastEmojiIndex);
                    event.stopImmediatePropagation();
                    break;
                case "End":
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.jumpIndex(lastEmojiIndex);
                    break;
            }
        }
    };
}

export default withEditor<IProps>(EmojiPicker);
