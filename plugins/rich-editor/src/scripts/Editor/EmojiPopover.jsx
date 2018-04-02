/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Grid, AutoSizer } from 'react-virtualized';
import * as PropTypes from "prop-types";
import classNames from 'classnames';
import emojis from 'emojibase-data/en/data.json';
import { groups as emojiGroups } from 'emojibase-data/meta/groups.json';
import { t } from "@core/application";
import { log } from "@core/utility";
import EmojiButton from "./EmojiButton";
import * as Icons from "./Icons";
import Popover from "./Generic/Popover";
import { withEditor, editorContextTypes } from "./ContextProvider";

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

log("rowIndexesByGroupId: ", rowIndexesByGroupId);

export class EmojiPopover extends React.PureComponent {
    static propTypes = {
        ...editorContextTypes,
        isVisible: PropTypes.bool.isRequired,
        closeMenu: PropTypes.func.isRequired,
        blurHandler: PropTypes.func.isRequired,
        popoverTitleID: PropTypes.string.isRequired,
        popoverDescriptionID: PropTypes.string.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        log("Emojis Loaded: ", emojis);
        this.emojiGroupLength = Object.values(emojiGroups).length;
        this.state = {
            scrollTarget: 0,
            firstEmojiOfGroup: 0,
            overscanRowCount: 20,
            rowStartIndex: 0,
            lastRowIndex: null,
            alertMessage: null,
        };

        this.categoryPickerID = "emojiPicker-categories-" + props.editorID;
    }

    /**
     * Handler when new rows are rendered. We use this to figure out what category is current
     */
    handleOnSectionRendered = (event) => {
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
    handleEmojiScroll = () => {
        this.setState({
            scrollTarget: -1,
            firstEmojiOfGroup: -1,
        });
    };

    handleCategoryClick(event, categoryId) {
        event.preventDefault();
        this.scrollToCategory(categoryId);
    }

    /**
     * Scroll to category
     */
    scrollToCategory = (categoryId) => {
        this.setState({
            scrollTarget: rowIndexesByGroupId[categoryId],
            firstEmojiOfGroup: cellIndexesByGroupId[categoryId],
            selectedGroup: categoryId,
            alertMessage: t("Jumped to emoji category: ") + t(emojiGroups[categoryId]),
        });
    };

    /**
     * Render list row
     */
    cellRenderer = ({ columnIndex, rowIndex, style }) => {
        const pos = rowIndex * rowSize + columnIndex;
        const emojiData = emojis[pos];
        let result = null;
        const isSelectedButton = this.state.firstEmojiOfGroup >= 0 && this.state.firstEmojiOfGroup === pos;
        if(emojiData) {
            result = <EmojiButton
                isSelectedButton={isSelectedButton}
                style={style}
                closeMenu={this.props.closeMenu}
                key={"emoji-" + emojiData.hexcode}
                emojiData={emojiData}
                index={pos}
                rowIndex={rowIndex}
            />;
        }
        return result;
    };

    /**
     * Get Group SVG Path
     */
    getGroupSVGPath = (groupName) => {
        const functionSuffix = groupName.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
        return Icons["emojiGroup_" + functionSuffix]();
    };

    /**
     * Focus on Emoji Categories
     */
    focusOnCategories = () => {
        const categories = document.getElementById(this.categoryPickerID);
        if (categories) {
            const firstButton = categories.querySelector('.richEditor-button');
            if (firstButton) {
                firstButton.focus();
            }
        }
    };

    componentDidMount(){
        document.addEventListener("keydown", this.handleKeyDown, false);
    }

    componentWillUnmount(){
        document.removeEventListener("keydown", this.handleKeyDown, false);
    }

    /**
     * Jump to adjacent category
     *
     * @param {bool} isNext - Are we jumping to the next group
     */

    jumpToAdjacentCategory(isNext = true) {
        const offset = isNext ? 1 : -1;
        const groupLength = this.emojiGroupLength - 1;
        let targetGroupID = this.state.selectedGroup + offset;

        if (targetGroupID > groupLength) {
            targetGroupID = 0;
        } else if(targetGroupID < 0) {
            targetGroupID = groupLength;
        }
        this.scrollToCategory(targetGroupID);
    }

    /**
     * Handle key press.
     *
     * @param {React.KeyboardEvent} event - A synthetic keyboard event.
     */
    handleKeyDown = (event) => {
        if (this.props.isVisible) {
            switch(event.code) {
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

    /**
     * @inheritDoc
     */
    render() {
        const title = t('Smileys & Faces');
        const description = [
            t('Insert an emoji in your message.'),
            t('Use keyboard shortcuts "page up" and "page down" to cycle through available categories when menu is open.'),
        ].join(' ');

        const extraHeadingContent = <button type="button" className="accessibility-jumpTo" onClick={this.focusOnCategories}>
            {t('Jump past emoji list, to emoji categories.')}
        </button>;

        const body = <AutoSizer>
            {({ height, width }) => (
                <Grid
                    containerRole = ''
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

                    aria-readonly={null}
                    aria-label={null}
                    role={null}

                    onScroll={this.handleEmojiScroll}
                    onSectionRendered={this.handleOnSectionRendered}
                />
            )}
        </AutoSizer>;

        const footer = <div
            id={this.categoryPickerID}
            className="emojiGroups"
            aria-label={t('Emoji Categories')}
            tabIndex={-1}
        >
            {Object.values(emojiGroups).map((groupName, groupKey) => {
                const isSelected = this.state.selectedGroup === groupKey;
                const buttonClasses = classNames(
                    'richEditor-button',
                    'emojiGroup',
                    { isSelected }
                );

                let onBlur = () => {};
                if(groupKey + 1 === this.emojiGroupLength) {
                    onBlur = this.props.blurHandler;
                }

                return <button
                    type="button"
                    onClick={(event) => this.handleCategoryClick(event, groupKey)}
                    onBlur={onBlur}
                    aria-current={isSelected}
                    aria-label={t('Jump to emoji category: ') + t(groupName)}
                    key={'emojiGroup-' + groupName}
                    title={t(groupName)}
                    className={buttonClasses}
                >
                    {this.getGroupSVGPath(groupName)}
                    <span className="sr-only">
                        {t('Jump to Category: ') + t(groupName)}
                    </span>
                </button>;
            })}
        </div>;

        return <Popover
            id={this.props.id}
            title={title}
            accessibleDescription={description}
            alertMessage={this.state.alertMessage}
            additionalHeaderContent={extraHeadingContent}
            body={body}
            footer={footer}
            additionalClassRoot="insertEmoji"
            closeMenu={this.props.closeMenu}
            isVisible={this.props.isVisible}
            popoverTitleID={this.props.popoverTitleID}
            popoverDescriptionID={this.props.popoverDescriptionID}
            targetTitleOnOpen={this.props.targetTitleOnOpen}
        />;
    }
}

export default withEditor(EmojiPopover);
