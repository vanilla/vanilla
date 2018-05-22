/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Grid, AutoSizer } from "react-virtualized";
import classNames from "classnames";
import { t } from "@dashboard/application";
import * as Icons from "./Icons";
import Popover from "./generic/Popover";
import PopoverController, { IPopoverControllerChildParameters } from "./generic/PopoverController";
import { withEditor, IEditorContextProps } from "./ContextProvider";

// Emoji
import emojis from "emojibase-data/en/data.json";
import { groups as emojiGroups } from "emojibase-data/meta/groups.json";
import EmojiButton from "./EmojiButton";

const buttonSize = 39;
const colSize = 7;
const rowSize = 7;
const rowIndexesByGroupId = {};
const cellIndexesByGroupId = {};

/**
 * Get start positions for each category
 */
emojis.map((data, key) => {
    const groupID = data.group;
    if (!(groupID in rowIndexesByGroupId)) {
        rowIndexesByGroupId[groupID] = Math.floor(key / colSize);
        cellIndexesByGroupId[groupID] = key;
    }
});

const emojiGroupLength = Object.values(emojiGroups).length;

interface IProps extends IEditorContextProps, IPopoverControllerChildParameters {
    contentID: string;
}

interface IState {
    id: string;
    contentID: string;
    scrollTarget: number;
    firstEmojiOfGroup: number;
    overscanRowCount: number;
    rowStartIndex: number;
    selectedGroup: number;
    lastRowIndex: number;
    alertMessage?: string;
}

export class EmojiPicker extends React.PureComponent<IProps, IState> {
    private categoryPickerID: string;

    constructor(props) {
        super(props);
        this.state = {
            id: props.id,
            contentID: props.contentID,
            scrollTarget: 0,
            firstEmojiOfGroup: 0,
            overscanRowCount: 20,
            rowStartIndex: 0,
            selectedGroup: 0,
            lastRowIndex: 0,
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
        const title = t("Smileys & Faces");
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
                {Object.values(emojiGroups).map((groupName: string, groupKey) => {
                    const isSelected = this.state.selectedGroup === groupKey;
                    const buttonClasses = classNames("richEditor-button", "emojiGroup", { isSelected });

                    let onBlur = (event: React.FocusEvent<any>) => {
                        return;
                    };
                    if (groupKey + 1 === emojiGroupLength) {
                        onBlur = this.props.blurHandler;
                    }

                    const onClick = event => this.handleCategoryClick(event, groupKey);

                    return (
                        <button
                            type="button"
                            onClick={onClick}
                            onBlur={onBlur}
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
                        columnCount={colSize}
                        columnWidth={buttonSize}
                        rowCount={Math.ceil(emojis.length / colSize)}
                        rowHeight={buttonSize}
                        height={height}
                        width={width}
                        overscanRowCount={this.state.overscanRowCount}
                        tabIndex={-1}
                        scrollToAlignment="start"
                        scrollToRow={this.state.scrollTarget}
                        aria-readonly={undefined}
                        aria-label={""}
                        role={""}
                        onScroll={this.handleEmojiScroll}
                        onSectionRendered={this.handleOnSectionRendered}
                    />
                )}
            </AutoSizer>
        );

        return (
            <Popover
                id={this.state.id}
                descriptionID={this.descriptionID}
                titleID={this.titleID}
                title={title}
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

    /**
     * Handler when new rows are rendered. We use this to figure out what category is current
     */
    private handleOnSectionRendered = event => {
        const lastRowIndex = this.state.rowStartIndex;
        const newRowIndex = event.rowStartIndex;
        let selectedGroup = 0;

        Object.values(rowIndexesByGroupId).map((groupRow, groupKey) => {
            if (newRowIndex >= groupRow) {
                selectedGroup = groupKey;
            }
        });

        this.setState({
            rowStartIndex: event.rowStartIndex,
            lastRowIndex,
            selectedGroup,
            alertMessage: t("In emoji category: ") + t(emojiGroups[selectedGroup]),
        });
    };

    /**
     * Handle Emoji Scroll
     */
    private handleEmojiScroll = () => {
        this.setState({
            scrollTarget: -1,
            firstEmojiOfGroup: -1,
        });
    };

    private handleCategoryClick(event: React.MouseEvent<any>, categoryID: number) {
        event.preventDefault();
        this.scrollToCategory(categoryID);
    }

    /**
     * Scroll to category
     */
    private scrollToCategory = (categoryID: number) => {
        this.setState({
            scrollTarget: rowIndexesByGroupId[categoryID],
            firstEmojiOfGroup: cellIndexesByGroupId[categoryID],
            selectedGroup: categoryID,
            alertMessage: t("Jumped to emoji category: ") + t(emojiGroups[categoryID]),
        });
    };

    /**
     * Render list row
     */
    private cellRenderer = ({ columnIndex, rowIndex, style }) => {
        const pos = rowIndex * rowSize + columnIndex;
        const emojiData = emojis[pos];
        let result: JSX.Element | null = null;
        const isSelectedButton = this.state.firstEmojiOfGroup >= 0 && this.state.firstEmojiOfGroup === pos;
        if (emojiData) {
            result = (
                <EmojiButton
                    isSelectedButton={isSelectedButton}
                    style={style}
                    closeMenuHandler={this.props.closeMenuHandler}
                    key={"emoji-" + emojiData.hexcode}
                    emojiData={emojiData}
                    index={pos}
                    rowIndex={rowIndex}
                />
            );
        }
        return result;
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
     * @param isNext - Are we jumping to the next group
     */

    private jumpToAdjacentCategory(isNext = true) {
        const offset = isNext ? 1 : -1;
        const groupLength = emojiGroupLength - 1;
        let targetGroupID = this.state.selectedGroup ? this.state.selectedGroup + offset : offset;

        if (targetGroupID > groupLength) {
            targetGroupID = 0;
        } else if (targetGroupID < 0) {
            targetGroupID = groupLength;
        }
        this.scrollToCategory(targetGroupID);
    }

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
                    this.jumpToAdjacentCategory(false);
                    break;
                case "PageDown":
                    event.preventDefault();
                    this.jumpToAdjacentCategory(true);
                    break;
            }
        }
    };
}

export default withEditor<IProps>(EmojiPicker);
