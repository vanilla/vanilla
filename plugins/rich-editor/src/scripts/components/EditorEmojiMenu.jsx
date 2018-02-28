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

/**
 * Get start positions for each category
 */
emojis.map((data, key) => {
    const groupID = data.group;
    if (!(groupID in rowIndexesByGroupId)) {
        const rowIndex = Math.floor(key / colSize);
        rowIndexesByGroupId[groupID] = rowIndex;
    }
});

utility.log("rowIndexesByGroupId: ", rowIndexesByGroupId);
// utility.log("groupIdByRowIndex: ", groupIdByRowIndex);

export default class EditorEmojiMenu extends React.PureComponent {
    static propTypes = {
        quill: PropTypes.instanceOf(Quill).isRequired,
        isVisible: PropTypes.bool.isRequired,
        closeMenu: PropTypes.func.isRequired,
        menuID: PropTypes.string.isRequired,
        menuTitleID: PropTypes.string.isRequired,
    };

    /**
     * @inheritDoc
     */
    constructor(props) {
        super(props);
        utility.log("Emojis Loaded: ", emojis);
        this.state = {
            scrollTarget: 0,
            selectedGroup: emojiGroups[0],
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
        });
    };

    /**
     * Scroll to category
     */
    scrollToCategory = (categoryId) => {
        this.setState({
            scrollTarget: rowIndexesByGroupId[categoryId],
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
        if(emojiData) {
            result = <EditorEmojiButton style={style} closeMenu={this.props.closeMenu} quill={this.props.quill} key={"emoji-" + emojiData.hexcode} emojiData={emojiData} index={pos} rowIndex={rowIndex} />;
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
        return <div id={this.props.menuID} className={componentClasses} role="dialog" aria-hidden={!this.props.isVisible} aria-labelledby={this.props.menuTitleID}>
            <div className="insertPopover-header">
                <h2 id={this.props.menuTitleID} className="H insertMedia-title">
                    {t('Smileys & Faces')}
                </h2>
                <a href="#" aria-label={t('Close')} onClick={this.props.closeMenu} className="Close richEditor-close">
                    <span>×</span>
                </a>
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

                            scrollToAlignment="start"
                            scrollToRow={this.state.scrollTarget}

                            onScroll={this.handleEmojiScroll}
                            onSectionRendered={this.handleOnSectionRendered}
                        />
                    )}
                </AutoSizer>
            </div>
            <div className="insertPopover-footer">
                <div className="emojiGroups">
                    {Object.values(emojiGroups).map((groupName, groupKey) => {
                        const isSelected = this.state.selectedGroup === groupKey;
                        const componentClasses = classNames(
                            'richEditor-button',
                            'emojiGroup',
                            { isSelected }
                        );
                        return <button type="button" onClick={() => this.scrollToCategory(groupKey)} aria-current={isSelected} key={'emojiGroup-' + groupName} title={t(groupName)} aria-label={t('Jump to emoji category: ') + t(groupName)} className={componentClasses}>
                            {this.getGroupSVGPath(groupName)}
                        </button>;
                    })}
                </div>
            </div>
        </div>;
    }
}
