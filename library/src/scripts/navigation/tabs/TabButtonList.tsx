/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { tabButtonListClasses } from "@library/forms/radioTabs/tabButtonListStyles";
import TabButton from "@library/navigation/tabs/TabButton";
import TabHandler from "@library/dom/TabHandler";

export interface ITabButton {
    buttonContent: React.ReactNode;
    openButtonContent?: React.ReactNode; // Optional overwrite when open
}

interface IProps {
    parentID: string;
    tabs: ITabButton[];
    className?: string;
    selectedTab: number;
    setTab: (selectedTab: number) => void;
    label: string;
    getTabButtonID: (index: number) => string;
    getTabPanelID: (index: number) => string;
    buttonClass?: string;
    extraContent?: React.ReactNode;
}

interface IState {
    setAllTabIndexes: boolean;
}

/**
 * Clean up conditional renders with this component
 */
export default class TabButtonList extends React.Component<IProps, IState> {
    private tabButtons: React.RefObject<HTMLDivElement> = React.createRef();
    public state = {
        setAllTabIndexes: false,
    };
    public render() {
        const { className, label, tabs, selectedTab, getTabButtonID, getTabPanelID, buttonClass } = this.props;
        const classes = tabButtonListClasses();
        const content = tabs.map((tab: ITabButton, index) => {
            const isSelected = selectedTab === index;
            const hasAlternateContents = !!tab.openButtonContent;
            return (
                <TabButton
                    id={getTabButtonID(index)}
                    ariaControls={getTabPanelID(index)}
                    ariaSelected={isSelected}
                    key={`tabButton-${index}`}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(isSelected, buttonClass, classes.button)}
                    tabIndex={isSelected || this.state.setAllTabIndexes ? 0 : -1}
                    index={index}
                    setTab={this.props.setTab}
                    onKeyDown={this.handleKeyDown}
                >
                    {!hasAlternateContents || (!isSelected && tab.buttonContent)}
                    {hasAlternateContents && isSelected && tab.openButtonContent}
                </TabButton>
            );
        });
        return (
            <div
                role="tablist"
                aria-label={label}
                className={classNames("tabButtonList", className, classes.root)}
                ref={this.tabButtons}
            >
                {content}
                {this.props.extraContent}
            </div>
        );
    }

    /**
     * Keyboard handler for accessibility
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/tabs/tabs-2/tabs.html
     * @param event
     */
    private handleKeyDown = (event: React.KeyboardEvent) => {
        event.persist();
        const currentEl = event.currentTarget;
        this.setState(
            {
                setAllTabIndexes: true,
            },
            () => {
                const tabHandler = new TabHandler(this.tabButtons.current!);
                switch (event.key) {
                    case "ArrowRight": {
                        event.stopPropagation();
                        const nextElement = tabHandler.getNext(currentEl, false, true);
                        if (nextElement) {
                            nextElement.focus();
                        }
                        break;
                    }
                    case "ArrowLeft": {
                        event.stopPropagation();
                        const prevElement = tabHandler.getNext(currentEl, true, true);
                        if (prevElement) {
                            prevElement.focus();
                        }
                        break;
                    }
                    case "Home": {
                        event.stopPropagation();
                        const firstElement = tabHandler.getInitial();
                        if (firstElement) {
                            firstElement.focus();
                        }
                        break;
                    }
                    case "End": {
                        event.stopPropagation();
                        const lastElement = tabHandler.getLast();
                        if (lastElement) {
                            lastElement.focus();
                        }
                        break;
                    }
                }
                this.setState({
                    setAllTabIndexes: false,
                });
            },
        );
    };
}
