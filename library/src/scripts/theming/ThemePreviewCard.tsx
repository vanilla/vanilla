/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { themeCardClasses } from "./themeCardStyles";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";

interface IProps {
    globalBg: string;
    globalFg: string;
    globalColor: string;
    titleBarBg: string;
    titleBarFg: string;
    headerImg: string;
    onApply: () => void;
    onPreview: () => void;
    onCopy: () => void;
}
interface IState {
    overlay: boolean;
}

export default class ThemePreviewCard extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }
    public state: IState = {
        overlay: false,
    };
    public render() {
        const tiles = [1, 2, 3, 4];
        //dummy data
        const variables = {
            data: {
                global: {
                    mainColors: {
                        primary: "#985E6D",
                        bg: "#111423",
                        fg: "#555a62",
                    },
                },
                titleBar: {
                    colors: {
                        bg: "#0291db",
                        fg: "#fff",
                    },
                },
            },
        };
        const classes = themeCardClasses();
        const titlebarStyle = {
            backgroundColor: `${variables.data.titleBar.colors.bg}`,
        };

        const titleBarLinks = {
            backgroundColor: `${variables.data.titleBar.colors.fg}`,
        };

        const containerStyle = {
            border: `1px solid ${variables.data.global.mainColors.bg}`,
        };

        const headerStyle = {
            backgroundColor: `${variables.data.global.mainColors.primary}`,
        };
        const subCommunityTileStyle = {
            border: `1px solid ${variables.data.global.mainColors.bg}`,
            boxShadow: `0 1px 3px 0 rgba(85,90,98,0.3)`,
            color: `${variables.data.global.mainColors.bg}`,
        };
        const tileImgStyle = {
            border: `1px solid ${variables.data.global.mainColors.bg}`,
        };
        const tileHeaderStyle = {
            backgroundColor: `${variables.data.global.mainColors.fg}`,
        };

        const tileTextStyle = {
            backgroundColor: `${variables.data.global.mainColors.fg}`,
        };

        return (
            <React.Fragment>
                <div style={containerStyle} className={classes.container}>
                    <div className={classes.wrapper}>
                        <div style={titlebarStyle} className={classes.titlebar}>
                            <ul className={classes.titleBarNav}>
                                <li style={titleBarLinks} className={classes.titleBarLinks}></li>
                                <li style={titleBarLinks} className={classes.titleBarLinks}></li>
                                <li style={titleBarLinks} className={classes.titleBarLinks}></li>
                            </ul>
                        </div>
                        <div style={headerStyle} className={classes.header}></div>

                        <div className={classes.subCommunityContent}>
                            <ul className={classes.subCommunityList}>
                                {tiles.map(() => (
                                    <li className={classes.subCommunityListItem}>
                                        <div style={subCommunityTileStyle} className={classes.subCommunityTile}>
                                            <div style={tileImgStyle} className={classes.tileImg}></div>
                                            <div style={tileHeaderStyle} className={classes.tileHeader}></div>
                                            <div className={classes.tileContent}>
                                                <p style={tileTextStyle} className={classes.text1}></p>
                                                <p style={tileTextStyle} className={classes.text2}></p>
                                                <p style={tileTextStyle} className={classes.text3}></p>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className={classes.actionButtons}>
                            <Button
                                baseClass={ButtonTypes.PRIMARY}
                                className={classes.buttons}
                                onClick={this.props.onApply}
                            >
                                Apply
                            </Button>
                            <Button
                                baseClass={ButtonTypes.PRIMARY}
                                className={classes.buttons}
                                onClick={this.props.onPreview}
                            >
                                Preview
                            </Button>
                            <Button
                                baseClass={ButtonTypes.PRIMARY}
                                className={classes.buttons}
                                onClick={this.props.onCopy}
                            >
                                Copy
                            </Button>
                        </div>
                    </div>
                </div>
            </React.Fragment>
        );
    }
}
