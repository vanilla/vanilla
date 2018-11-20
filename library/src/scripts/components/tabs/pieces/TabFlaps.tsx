/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import Condition from "@library/components/Condition";
import TabHandler from "@library/TabHandler";

interface ITabFlap {
    controls: string;
    contents: React.ReactNode;
    openContents?: React.ReactNode; // Optional overwrite when open
}

interface IProps {
    parentID: string;
    tabs: ITabFlap[];
    className?: string;
    selectedTab: number;
    setTab: (selectedTab: number) => void;
    label: string;
    getTabFlapID: (index: number) => string;
    getTabPanelID: (index: number) => string;
}

/**
 * Clean up conditional renders with this component
 */
export default class TabFlaps extends React.Component<IProps> {
    private tabFlaps: React.RefObject<HTMLDivElement> = React.createRef();
    public render() {
        const { className, label, tabs, selectedTab, getTabFlapID, getTabPanelID } = this.props;
        const content = tabs.map((tab: ITabFlap, index) => {
            const isSelected = selectedTab === index;
            const hasAlternateContents = !!tab.openContents;
            return (
                <Button
                    id={getTabFlapID(index)}
                    aria-controls={getTabPanelID(index)}
                    aria-selected={isSelected}
                    key={`tabFlap-${index}`}
                    baseClass={ButtonBaseClass.TAB}
                    className={classNames("tabFlap", isSelected)}
                    role="tab"
                    tabIndex={isSelected ? 0 : -1}
                >
                    {!hasAlternateContents || (!isSelected && tab.contents)}
                    {hasAlternateContents && isSelected && tab.openContents}
                </Button>
            );
        });
        return (
            <div
                onKeyPressCapture={this.handleKeyPress}
                role="tablist"
                aria-label={label}
                className={classNames("tabs", className)}
                ref={this.tabFlaps}
            >
                {content}
            </div>
        );
    }

    /**
     * Keyboard handler for accessibility
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/tabs/tabs-2/tabs.html
     * @param event
     */
    private handleKeyPress = (event: React.KeyboardEvent) => {
        const currentLink = document.activeElement;
        const tabHandler = new TabHandler(this.tabFlaps.current!);

        switch (
            event.key // See SiteNavNode for the rest of the keyboard handler
        ) {
            case "ArrowRight":
                /*
                    Moves focus one row or one cell down, depending on whether a row or cell is currently focused.
                    If focus is on the bottom row, focus does not move.
                 */
                // if (siteNavRoot) {
                //     event.preventDefault();
                //     event.stopPropagation();
                //     if (selectedNode && currentLink) {
                //         const nextElement = tabHandler.getNext(currentLink, false, false);
                //         if (nextElement) {
                //             nextElement.focus();
                //         }
                //     }
                // }
                break;
            case "ArrowLeft":
                break;
            case "Home":
                break;
            case "End":
                break;
        }
    };
}
