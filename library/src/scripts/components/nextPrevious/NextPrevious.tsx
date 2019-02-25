/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { componentThemeVariables, debugHelper } from "@library/styles/styleHelpers";
import ScreenReaderContent from "@library/components/ScreenReaderContent";
import Heading from "@library/components/Heading";
import AdjacentLink, { LeftRight } from "@library/components/nextPrevious/AdjacentLink";
import { px } from "csx";

interface IUrlItem {
    name: string;
    url: string;
}

interface IProps {
    className?: string;
    accessibleTitle: string;
    prevItem?: IUrlItem | null;
    nextItem?: IUrlItem | null;
}

/**
 * Implement mobile next/previous nav to articles
 */
export default class NextPrevious extends React.Component<IProps> {
    public render() {
        const { accessibleTitle, className, prevItem, nextItem } = this.props;

        if (!nextItem && !prevItem) {
            return null; // skip if no sibling pages exist
        }

        const classes = this.nextPreviousStyles();
        return (
            <nav className={classNames(className, classes.root)}>
                <ScreenReaderContent>
                    <Heading title={accessibleTitle} />
                </ScreenReaderContent>
                {/* Left */}
                {prevItem && (
                    <AdjacentLink
                        className={classes.previous}
                        classes={classes}
                        direction={LeftRight.LEFT}
                        to={prevItem.url}
                        title={prevItem.name}
                    />
                )}
                {/* Right */}
                {nextItem && (
                    <AdjacentLink
                        className={classes.next}
                        classes={classes}
                        direction={LeftRight.RIGHT}
                        to={nextItem.url}
                        title={nextItem.name}
                    />
                )}
            </nav>
        );
    }

    public nextPreviousVariables = (theme?: object) => {
        const globalVars = globalVariables(theme);
        const themeVars = componentThemeVariables(theme, "nextPreviousVars");

        const fonts = {
            label: globalVars.fonts.size.small,
            title: globalVars.fonts.size.medium,
            ...themeVars.subComponentStyles("fonts"),
        };

        const lineHeights = {
            label: globalVars.lineHeights.condensed,
            title: globalVars.lineHeights.condensed,
            ...themeVars.subComponentStyles("lineHeights"),
        };

        const colors = {
            title: globalVars.mixBgAndFg(0.9),
            label: globalVars.mixBgAndFg(0.85),
            hover: globalVars.mainColors.primary,
            ...themeVars.subComponentStyles("colors"),
        };
        return { lineHeights, fonts, colors };
    };

    public nextPreviousStyles = (theme?: object) => {
        const globalVars = globalVariables(theme);
        const vars = this.nextPreviousVariables(theme);
        const debug = debugHelper("nextPrevious");
        const activeStyles = {
            color: vars.colors.title.toString(),
            $nest: {
                ".adjacentLinks-icon": {
                    color: globalVars.mainColors.primary.toString(),
                },
            },
        };

        const root = style({
            display: "flex",
            alignItems: "flex-start",
            flexWrap: "wrap",
            justifyContent: "space-between",
            color: globalVars.mainColors.fg.toString(),
            ...debug.name(),
        });

        const directionLabel = style({
            display: "block",
            fontSize: px(globalVars.fonts.size.small),
            lineHeight: globalVars.lineHeights.condensed,
            color: vars.colors.label.toString(),
            marginBottom: px(2),
            ...debug.name("label"),
        });

        const title = style({
            display: "block",
            position: "relative",
            fontSize: px(globalVars.fonts.size.medium),
            lineHeight: globalVars.lineHeights.condensed,
            fontWeight: globalVars.fonts.weights.semiBold,
            ...debug.name("title"),
        });

        const chevron = style({
            position: "absolute",
            top: px((vars.fonts.title * vars.lineHeights.title) / 2),
            transform: `translateY(-50%)`,
            color: globalVars.mixBgAndFg(0.75).toString(),
            ...debug.name("chevron"),
        });

        const chevronLeft = style({
            left: px(0),
            marginLeft: px(-globalVars.icon.sizes.default),
            ...debug.name("chevronLeft"),
        });

        const chevronRight = style({
            right: px(0),
            marginRight: px(-globalVars.icon.sizes.default),
            ...debug.name("chevronRight"),
        });

        // Common to both left and right
        const adjacent = style({
            display: "block",
            marginTop: px(8),
            marginBottom: px(8),
            color: vars.colors.title.toString(),
            $nest: {
                "&.focus-visible": activeStyles,
                "&:hover": activeStyles,
                "&:active": activeStyles,
            },
            ...debug.name("adjacent"),
        });

        const previous = style({
            paddingLeft: px(globalVars.icon.sizes.default),
            paddingRight: px(globalVars.icon.sizes.default / 2),
            ...debug.name("previous"),
        });

        const next = style({
            marginLeft: "auto",
            textAlign: "right",
            paddingRight: px(globalVars.icon.sizes.default),
            paddingLeft: px(globalVars.icon.sizes.default / 2),
            ...debug.name("next"),
        });

        return { root, adjacent, previous, next, title, chevron, directionLabel, chevronLeft, chevronRight };
    };
}
