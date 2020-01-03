/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeCardClasses } from "./themeCardStyles";
import Button from "@library/forms/Button";
import { t } from "@library/utility/appUtils";

interface IProps {
    globalBg: string;
    globalFg: string;
    globalPrimary: string;
    titleBarBg: string;
    titleBarFg: string;
    headerImg?: string;
    onApply?: () => void;
    onPreview?: () => void;
    onCopy?: () => void;
    isActiveTheme: boolean;
}
interface IState {}

export default class ThemePreviewCard extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }

    public render() {
        const tiles = [1, 2, 3, 4];
        const { globalBg, globalPrimary, globalFg, titleBarBg, titleBarFg } = this.props;
        const classes = themeCardClasses();
        const titlebarStyle = {
            backgroundColor: titleBarBg,
        };

        const titleBarLinks = {
            backgroundColor: titleBarFg,
        };

        const containerStyle = {
            backgroundColor: `${globalBg}`,
        };

        const headerStyle = {
            backgroundColor: globalPrimary,
        };

        return (
            <React.Fragment>
                <div
                    style={containerStyle}
                    className={this.props.isActiveTheme ? classes.noActions : classes.container}
                >
                    <div className={classes.menuBar}>
                        {[0, 1, 2].map(key => (
                            <span key={key} className={classes.dots}></span>
                        ))}
                    </div>
                    <div className={classes.wrapper}>
                        <div style={titlebarStyle} className={classes.titlebar}>
                            <ul className={classes.titleBarNav}>
                                {[0, 1, 2].map(key => (
                                    <li key={key} style={titleBarLinks} className={classes.titleBarLinks}></li>
                                ))}
                            </ul>
                        </div>
                        <div style={headerStyle} className={classes.header}>
                            <div className={classes.title}></div>
                            <div className={classes.search}>
                                <span className={classes.bar}></span>
                                <span className={classes.search_btn}>
                                    <span className={classes.searchText}></span>
                                </span>
                            </div>
                        </div>

                        <div className={classes.content}>
                            <ul className={classes.contentList}>
                                {tiles.map((val, key) => (
                                    <li key={key} className={classes.contentListItem}>
                                        <div className={classes.contentTile}>
                                            <div className={classes.tileImg}></div>
                                            <div className={classes.tileHeader}></div>
                                            <div className={classes.tileContent}>
                                                <p className={classes.text1}></p>
                                                <p className={classes.text2}></p>
                                                <p className={classes.text3}></p>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className={this.props.isActiveTheme ? classes.noOverlay : classes.overlay}>
                            <div className={classes.actionButtons}>
                                <Button className={classes.buttons} onClick={this.props.onApply}>
                                    {t("Apply")}
                                </Button>
                                <Button className={classes.buttons} onClick={this.props.onPreview}>
                                    {t("Preview")}
                                </Button>
                                <Button className={classes.buttons} onClick={this.props.onCopy}>
                                    {t("Copy")}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </React.Fragment>
        );
    }
}
