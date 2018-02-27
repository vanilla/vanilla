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

const buttonSize = 39;
const colSize = 7;
const rowSize = 7;

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
    }

    /**
     * Render list row
     */
    cellRenderer = ({ columnIndex, rowIndex, style }) => {
        const pos = rowIndex * rowSize + columnIndex;
        const emojiData = emojis[pos];
        let result = null;
        if(emojiData) {
            result = <EditorEmojiButton style={style} closeMenu={this.props.closeMenu} quill={this.props.quill} key={"emoji-" + emojiData.hexcode} emojiData={emojiData} />;
        }
        return result;
    }

    /**
     * @inheritDoc
     */
    render() {
        const componentClassNames = classNames(
            'richEditor-menu',
            'insertEmoji',
            'FlyoutMenu',
            'insertPopover',
            {
                isHidden: !this.props.isVisible,
            }
        );
        return <div id={this.props.menuID} className={componentClassNames} role="dialog" aria-hidden={!this.props.isVisible} aria-labelledby={this.props.menuTitleID}>
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
                        />
                    )}
                </AutoSizer>
            </div>
        </div>;
    }
}
