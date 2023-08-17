/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { siteNavNodeClasses, siteNavNodeDashboardClasses } from "@library/navigation/siteNavStyles";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { SiteNavContext } from "@library/navigation/SiteNavContext";
import { INavigationTreeItem } from "@library/@types/api/core";
import { Hoverable } from "@vanilla/react-utils";
import { TabHandler } from "@vanilla/dom-utils";
import { CheckCompactIcon, DownTriangleIcon, RightTriangleIcon } from "@library/icons/common";
import { RecordID } from "@vanilla/utils";
import { SiteNavNodeTypes } from "@library/navigation/SiteNavNodeTypes";
import { cx } from "@emotion/css";

interface IProps extends INavigationTreeItem {
    activeRecord: IActiveRecord | undefined;
    className?: string;
    titleID?: string;
    openParent?: () => void;
    depth: number;
    onSelectItem?(item: INavigationTreeItem);
    onItemHover?(item: INavigationTreeItem);
    clickableCategoryLabels?: boolean;
    collapsible: boolean;
    siteNavNodeTypes?: SiteNavNodeTypes;
    withCheckMark?: boolean;
}

export interface IActiveRecord {
    recordID: RecordID;
    recordType: string;
}

/**
 * Recursive component to generate site nav item
 */
export default class SiteNavNode extends React.Component<IProps> {
    public static contextType = SiteNavContext;
    declare context: React.ContextType<typeof SiteNavContext>;

    public render() {
        const { siteNavNodeTypes } = this.props;
        const collapsible = this.props.collapsible && this.context.categoryRecordType === this.props.recordType; // blocking collapsible
        const isCurrent = this.isActiveRecord();
        const isFirstLevel = this.props.depth === 0;
        const hasChildren = collapsible;
        const classes =
            siteNavNodeTypes && siteNavNodeTypes === SiteNavNodeTypes.DASHBOARD
                ? siteNavNodeDashboardClasses(isCurrent, isFirstLevel, hasChildren)
                : siteNavNodeClasses(isCurrent, isFirstLevel, hasChildren);

        let linkContents;

        if (this.props.clickableCategoryLabels && collapsible) {
            linkContents = (
                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    onKeyDownCapture={this.handleKeyDown}
                    className={classes.link}
                    onClick={this.handleToggleClick as any}
                >
                    <span className={classes.label}>{this.props.name}</span>
                </Button>
            );
        } else {
            const linkOrButtonContents = (
                <span className={cx(classes.label, { [`${classes.activeLink}`]: this.props.isLink })}>
                    {this.props.name}
                    {this.props.withCheckMark && <CheckCompactIcon className={siteNavNodeClasses().checkMark} />}
                </span>
            );
            linkContents = (
                <Hoverable onHover={this.handleHover} duration={50}>
                    {(provided) => {
                        return this.props.url ? (
                            <SmartLink
                                {...provided}
                                active={isCurrent}
                                aria-current={isCurrent ? "page" : undefined}
                                onKeyDownCapture={this.handleKeyDown}
                                onClick={this.handleSelect}
                                className={classes.link}
                                tabIndex={0}
                                to={this.props.url!}
                            >
                                {linkOrButtonContents}
                            </SmartLink>
                        ) : (
                            <Button
                                {...provided}
                                buttonType={ButtonTypes.CUSTOM}
                                onKeyDownCapture={this.handleKeyDown}
                                onClick={this.handleSelect}
                                className={classes.link}
                                tabIndex={0}
                            >
                                {linkOrButtonContents}
                            </Button>
                        );
                    }}
                </Hoverable>
            );
        }

        const childrenContents =
            collapsible &&
            this.props.children.map((child) => {
                const key = child.recordType + child.recordID;
                return (
                    <SiteNavNode
                        {...child}
                        activeRecord={this.props.activeRecord}
                        key={key}
                        openParent={this.openSelfAndParents}
                        depth={this.props.depth + 1}
                        collapsible={collapsible}
                        onSelectItem={this.props.onSelectItem}
                        onItemHover={this.props.onItemHover}
                        clickableCategoryLabels={!!this.props.clickableCategoryLabels}
                        aria-current="page"
                        siteNavNodeTypes={siteNavNodeTypes}
                    />
                );
            });
        return (
            <li
                className={cx("siteNavNode", this.props.className, classes.root)}
                role="treeitem"
                aria-expanded={this.isOpen}
            >
                {collapsible && this.props.children.length > 0 ? (
                    <div
                        className={cx(classes.buttonOffset, {
                            hasNoOffset: this.props.depth === 1,
                        })}
                    >
                        <Button
                            tabIndex={-1}
                            ariaHidden={true}
                            title={t("Toggle Category")}
                            ariaLabel={t("Toggle Category")}
                            onClick={this.handleToggleClick as any}
                            buttonType={ButtonTypes.CUSTOM}
                            className={classes.toggle}
                        >
                            {this.isOpen ? (
                                <DownTriangleIcon title={t("Expand")} />
                            ) : (
                                <RightTriangleIcon title={t("Collapse")} />
                            )}
                        </Button>
                    </div>
                ) : null}
                <div className={classes.contents}>
                    {linkContents}
                    {collapsible && (
                        <ul
                            className={cx(classes.children, {
                                isHidden: collapsible ? !this.isOpen : false,
                            })}
                            role="group"
                        >
                            {childrenContents}
                        </ul>
                    )}
                </div>
            </li>
        );
    }

    /**
     * Call the hover callback with the item data.
     */
    private handleHover = () => {
        if (this.props.onItemHover) {
            this.props.onItemHover(this.props);
        }
    };

    private get isOpen(): boolean {
        const records = this.context.openRecords[this.props.recordType];
        if (!records) {
            return false;
        }

        return records.has(this.props.recordID);
    }

    /**
     * Opens node. Optional callback if it's already open.
     * @param callbackIfAlreadyOpen
     */
    private open = (callbackIfAlreadyOpen?: any) => {
        if (!this.isOpen) {
            this.context.openItem(this.props.recordType, this.props.recordID);
        } else {
            if (callbackIfAlreadyOpen) {
                callbackIfAlreadyOpen();
            }
        }
    };

    /**
     * Closes node. Optional callback if already closed.
     */
    private close = (callbackIfAlreadyClosed?: any) => {
        if (this.isOpen) {
            this.context.closeItem(this.props.recordType, this.props.recordID);
        } else {
            if (callbackIfAlreadyClosed) {
                callbackIfAlreadyClosed();
            }
        }
    };

    /**
     * Opens self and calls same function on parent. Opens all the way to the root.
     */
    private openSelfAndParents = () => {
        this.open();
        if (this.props.openParent) {
            this.props.openParent();
        }
    };

    /**
     * Toggle node
     */
    private toggle = () => {
        this.context.toggleItem(this.props.recordType, this.props.recordID);
    };

    /**
     * Handles clicking on the chevron to toggle node
     * @param e
     */
    private handleSelect = (e) => {
        e.stopPropagation();
        if (this.props.onSelectItem) {
            this.props.onSelectItem(this.props);
        }
    };

    /**
     * Handles clicking on the chevron to toggle node
     * @param e
     */
    private handleToggleClick = (e) => {
        e.stopPropagation();
        this.toggle();
    };

    /**
     * Checks if we're on the current page
     * Note that this won't work with non-canonical URLs
     */
    private isActiveRecord(): boolean | undefined {
        return (
            this.props.activeRecord &&
            this.props.recordType === this.props.activeRecord.recordType &&
            this.props.recordID === this.props.activeRecord.recordID
        );
    }

    /**
     * When component gets added to DOM, check if we're the current node, if so open recursively up the tree
     * @param prevProps
     */
    public componentDidMount() {
        if (this.isActiveRecord()) {
            this.openSelfAndParents();
        }
    }

    /**
     * Select next visible elemnt in tree
     * @param tabHandler The tab handler handler
     * @param currentLink The starting point
     */
    private next = (tabHandler: TabHandler, currentLink: Element) => {
        const nextElement = tabHandler.getNext(currentLink, false, false);
        if (nextElement) {
            nextElement.focus();
        }
    };

    /**
     * Select prev visible elemnt in tree
     * @param tabHandler The tab handler handler
     * @param currentLink The starting point
     */
    private prev = (tabHandler: TabHandler, currentLink: Element) => {
        const prevElement = tabHandler.getNext(currentLink, true, false);
        if (prevElement) {
            prevElement.focus();
        }
    };

    /**
     * Keyboard handler for arrow up, arrow down, home and end.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on the SiteNavNode
     * @param event
     */

    /**
     * Keyboard handler for arrow right and arrow left.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNav.tsx
     * @param event
     */
    private handleKeyDown = (event) => {
        if (document.activeElement === null) {
            return;
        }
        const currentLink = document.activeElement;
        const siteNavRoot = currentLink.closest(".siteNav");
        const tabHandler = new TabHandler(siteNavRoot!);

        switch (event.key) {
            case "ArrowRight":
                /*
                    When focus is on a closed node, opens the node; focus does not move.
                    When focus is on a open node, moves focus to the first child node.
                    When focus is on an end node, does nothing.
                 */
                if (this.props.children && this.props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.open(() => {
                        this.next(tabHandler, currentLink);
                    });
                }
                break;
            case "ArrowLeft":
                /*
                    When focus is on an open node, closes the node.
                    When focus is on a child node that is also either an end node or a closed node, moves focus to its parent node.
                    When focus is on a root node that is also either an end node or a closed node, does nothing.
                */

                if (this.props.children && this.props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.close(() => {
                        this.prev(tabHandler, currentLink);
                    });
                } else {
                    this.prev(tabHandler, currentLink);
                }
                break;
        }
    };
}
