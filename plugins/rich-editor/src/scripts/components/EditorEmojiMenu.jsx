/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import * as PropTypes from "prop-types";
import Quill from "quill/core";
import { t } from "@core/utility";
import EditorEmojiButton from "../components/EditorEmojiButton";
import emojis from 'emojibase-data/en/data.json';
import classNames from 'classnames';
import { Grid, AutoSizer } from 'react-virtualized';
import { groups as emojiGroups } from 'emojibase-data/meta/groups.json';
import * as utility from '@core/utility';
import * as Icons from "./Icons";

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
        const rowIndex = Math.floor(key / colSize);
        rowIndexesByGroupId[groupID] = rowIndex;
        cellIndexesByGroupId[groupID] = key;
    }
});

utility.log("rowIndexesByGroupId: ", rowIndexesByGroupId);

export default class EditorEmojiMenu extends React.PureComponent {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        isVisible: PropTypes.bool.isRequired,
        closeMenu: PropTypes.func.isRequired,
        menuID: PropTypes.string.isRequired,
        menuTitleID: PropTypes.string.isRequired,
        menuDescriptionID: PropTypes.string.isRequired,
        emojiCategoriesID: PropTypes.string.isRequired,
        pickerID: PropTypes.string.isRequired,
        checkForExternalFocus: PropTypes.func.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        utility.log("Emojis Loaded: ", emojis);
        this.emojiGroupLength = Object.values(emojiGroups).length;
        this.state = {
            scrollTarget: 0,
            firstEmojiOfGroup: 0,
            overscanRowCount: 20,
            rowStartIndex: 0,
            lastRowIndex: null,
        };
    }

    /**
     * Handler when new rows are rendered. We use this to figure out what category is current
     */
    handleOnSectionRendered = (event) => {
        const lastRowIndex = this.state.rowStartIndex;
        const newRowIndex = event.rowStartIndex;
        let selectedGroup = 0;
        let targetRow = 0;

        if ( newRowIndex > lastRowIndex) {
            targetRow = newRowIndex;
        } else {
            targetRow = lastRowIndex;
        }

        Object.values(rowIndexesByGroupId).map((groupRow, groupKey) => {
            if (newRowIndex >= groupRow) {
                selectedGroup = groupKey;
            }
        });

        this.setState({
            rowStartIndex: event.rowStartIndex,
            lastRowIndex,
            selectedGroup,
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

    /**
     * Scroll to category
     */
    scrollToCategory = (categoryId) => {
        this.setState({
            scrollTarget: rowIndexesByGroupId[categoryId],
            firstEmojiOfGroup: cellIndexesByGroupId[categoryId],
            selectedGroup: categoryId,
        });
    };

    /**
     * Render list row
     */
    cellRenderer = ({ columnIndex, rowIndex, style }) => {
        const pos = rowIndex * rowSize + columnIndex;
        const emojiData = emojis[pos];
        let result = null;
        const selectedButton = this.state.firstEmojiOfGroup >= 0 && this.state.firstEmojiOfGroup === pos ;
        if(emojiData) {
            result = <EditorEmojiButton selectedButton={selectedButton} style={style} closeMenu={this.props.closeMenu} quill={this.props.quill} key={"emoji-" + emojiData.hexcode} emojiData={emojiData} index={pos} rowIndex={rowIndex} />;
        }
        return result;
    };

    /**
     * Get Group SVG Path
     */
    getGroupSVGPath = (groupName) => {
        const functionSuffix = groupName.replace(/-([a-z])/g, function (g) { return g[1].toUpperCase(); });
        return Icons["emojiGroup_" + functionSuffix]();
    };

    /**
     * Focus on Emoji Categories
     */
    focusOnCategories = () => {
        const categories = document.getElementById(this.props.emojiCategoriesID);
        if (categories) {
            const firstButton = categories.querySelector('.richEditor-button');
            if (firstButton) {
                firstButton.focus();
            }
        }
    };

    /**
     * @inheritDoc
     */
    render() {
        const componentClasses = classNames(
            'richEditor-menu',
            'insertEmoji',
            'FlyoutMenu',
            'insertPopover',
            {
                isHidden: !this.props.isVisible,
            }
        );
        return <div id={this.props.menuID} className={componentClasses} role="dialog" aria-describedby={this.menuDescriptionID} aria-hidden={!this.props.isVisible} aria-labelledby={this.props.menuTitleID}>
            <div className="insertPopover-header">
                <h2 id={this.props.menuTitleID} className="H insertMedia-title">
                    {t('Smileys & Faces')}
                </h2>
                <div id={this.menuDescriptionID} className="sr-only">
                    {t('Insert an emoji in your message.')}
                </div>
                <button type="button" onClick={this.props.closeMenu} className="Close richEditor-close">
                    <span className="Close-x" aria-hidden="true">×</span>
                    <span className="sr-only">{t('Close')}</span>
                </button>
                <button type="button" className="accessibility-jumpTo" onClick={() => this.focusOnCategories()}>
                    {t('Jump past emoji list, to emoji categories.')}
                </button>
            </div>
            <div className="insertPopover-body">
                <AutoSizer>
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

                            onScroll={this.handleEmojiScroll}
                            onSectionRendered={this.handleOnSectionRendered}
                        />
                    )}
                </AutoSizer>
            </div>
            <div className="insertPopover-footer">
                <div id={this.props.emojiCategoriesID} className="emojiGroups" aria-label={t('Emoji Categories')} tabIndex={-1}>
                    {Object.values(emojiGroups).map((groupName, groupKey) => {
                        const isSelected = this.state.selectedGroup === groupKey;
                        const componentClasses = classNames(
                            'richEditor-button',
                            'emojiGroup',
                            { isSelected }
                        );

                        let onBlur = () => {};
                        if(groupKey + 1 === this.emojiGroupLength) {
                            onBlur = this.props.checkForExternalFocus;
                        }

                        return <button type="button" onClick={() => this.scrollToCategory(groupKey)} onBlur={onBlur} aria-current={isSelected} key={'emojiGroup-' + groupName} title={t(groupName)} aria-label={t('Jump to emoji category: ') + t(groupName)} className={componentClasses}>
                            {this.getGroupSVGPath(groupName)}
                            <span className="sr-only">
                                {t('Jump to Category: ') + t(groupName)}
                            </span>
                        </button>;
                    })}
                </div>
            </div>
        </div>;
    }
}
