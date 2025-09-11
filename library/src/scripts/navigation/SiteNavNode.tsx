/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect } from "react";
import { siteNavNodeClasses, siteNavNodeDashboardClasses } from "@library/navigation/siteNavStyles";
import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import SmartLink from "@library/routing/links/SmartLink";
import { SiteNavContext, useSiteNavContext } from "@library/navigation/SiteNavContext";
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
export default function SiteNavNode(props: IProps) {
    const context = useSiteNavContext();
    const { siteNavNodeTypes } = props;
    const collapsible = props.collapsible && context.categoryRecordType === props.recordType; // blocking collapsible
    const isActiveRecord =
        props.activeRecord &&
        props.recordType === props.activeRecord.recordType &&
        props.recordID === props.activeRecord.recordID;

    const isCurrent = isActiveRecord;
    const isFirstLevel = props.depth === 0;
    const hasChildren = collapsible;
    const dashboardClasses = siteNavNodeDashboardClasses.useAsHook(isCurrent, isFirstLevel, hasChildren);
    const normalClasses = siteNavNodeClasses.useAsHook(isCurrent, isFirstLevel, hasChildren);

    useEffect(() => {
        if (isActiveRecord) {
            openSelfAndParents();
        }
    }, []);

    /**
     * Keyboard handler for arrow right and arrow left.
     * For full accessibility docs, see https://www.w3.org/TR/wai-aria-practices-1.1/examples/treeview/treeview-1/treeview-1a.html
     * Note that some of the events are on SiteNav.tsx
     * @param event
     */
    const handleKeyDown = (event: React.KeyboardEvent) => {
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
                if (props.children && props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    open(() => {
                        tabHandler.getNext(currentLink, false, false)?.focus();
                    });
                }
                break;
            case "ArrowLeft":
                /*
                When focus is on an open node, closes the node.
                When focus is on a child node that is also either an end node or a closed node, moves focus to its parent node.
                When focus is on a root node that is also either an end node or a closed node, does nothing.
            */

                if (props.children && props.children.length > 0) {
                    event.preventDefault();
                    event.stopPropagation();
                    close(() => {
                        tabHandler.getNext(currentLink, true, false)?.focus();
                    });
                } else {
                    tabHandler.getNext(currentLink, true, false)?.focus();
                }
                break;
        }
    };

    /**
     * Call the hover callback with the item data.
     */
    const handleHover = () => {
        if (props.onItemHover) {
            props.onItemHover(props);
        }
    };

    const isOpen = context.openRecords[props.recordType]?.has(props.recordID);

    /**
     * Opens node. Optional callback if it's already open.
     * @param callbackIfAlreadyOpen
     */
    const open = (callbackIfAlreadyOpen?: any) => {
        if (!isOpen) {
            context.openItem(props.recordType, props.recordID);
        } else {
            if (callbackIfAlreadyOpen) {
                callbackIfAlreadyOpen();
            }
        }
    };

    /**
     * Closes node. Optional callback if already closed.
     */
    const close = (callbackIfAlreadyClosed?: any) => {
        if (isOpen) {
            context.closeItem(props.recordType, props.recordID);
        } else {
            if (callbackIfAlreadyClosed) {
                callbackIfAlreadyClosed();
            }
        }
    };

    /**
     * Opens self and calls same function on parent. Opens all the way to the root.
     */
    const openSelfAndParents = () => {
        open();
        if (props.openParent) {
            props.openParent();
        }
    };

    /**
     * Toggle node
     */
    const toggle = () => {
        context.toggleItem(props.recordType, props.recordID);
    };

    /**
     * Handles clicking on the chevron to toggle node
     * @param e
     */
    const handleSelect = (e: React.SyntheticEvent) => {
        e.stopPropagation();
        if (props.onSelectItem) {
            props.onSelectItem(props);
        }
    };

    /**
     * Handles clicking on the chevron to toggle node
     * @param e
     */
    const handleToggleClick = (e: React.SyntheticEvent) => {
        e.stopPropagation();
        toggle();
    };

    const classes =
        siteNavNodeTypes === SiteNavNodeTypes.DASHBOARD
            ? {
                  ...normalClasses,
                  ...dashboardClasses,
              }
            : normalClasses;

    let linkContents;

    if (props.clickableCategoryLabels && collapsible) {
        linkContents = (
            <Button
                buttonType={ButtonTypes.CUSTOM}
                onKeyDownCapture={handleKeyDown}
                className={classes.link}
                onClick={handleToggleClick}
            >
                <span className={classes.label}>
                    {props.iconPrefix}
                    <span className={classes.labelText}>{props.name}</span>
                    {props.iconSuffix}
                </span>
            </Button>
        );
    } else {
        const linkOrButtonContents = (
            <span className={cx(classes.label, { [`${classes.activeLink}`]: props.isLink })}>
                {props.iconPrefix}
                <span className={classes.labelText}>
                    {props.name}
                    {props.badge && props.badge.text && <span className={classes.badge}>{props.badge.text}</span>}
                </span>
                {props.iconSuffix}
                {props.withCheckMark && <CheckCompactIcon className={siteNavNodeClasses().checkMark} />}
            </span>
        );
        linkContents = (
            <Hoverable onHover={handleHover} duration={50}>
                {(provided) => {
                    return props.url ? (
                        <SmartLink
                            {...provided}
                            active={isCurrent}
                            aria-current={isCurrent ? "page" : undefined}
                            onKeyDownCapture={handleKeyDown}
                            onClick={handleSelect}
                            className={classes.link}
                            tabIndex={0}
                            to={props.url!}
                        >
                            {linkOrButtonContents}
                        </SmartLink>
                    ) : (
                        <Button
                            {...provided}
                            buttonType={ButtonTypes.CUSTOM}
                            onKeyDownCapture={handleKeyDown}
                            onClick={handleSelect}
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
        props.children?.map((child, i) => {
            const key = i;
            return (
                <SiteNavNode
                    {...child}
                    activeRecord={props.activeRecord}
                    key={key}
                    openParent={openSelfAndParents}
                    depth={props.depth + 1}
                    collapsible={collapsible}
                    onSelectItem={props.onSelectItem}
                    onItemHover={props.onItemHover}
                    clickableCategoryLabels={!!props.clickableCategoryLabels}
                    aria-current="page"
                    siteNavNodeTypes={siteNavNodeTypes}
                />
            );
        });
    return (
        <li className={cx("siteNavNode", props.className, classes.root)} role="treeitem" aria-expanded={isOpen}>
            {collapsible && (props.children?.length ?? 0) > 0 ? (
                <div
                    className={cx(classes.buttonOffset, {
                        hasNoOffset: props.depth === 1,
                    })}
                >
                    <Button
                        tabIndex={-1}
                        ariaHidden={true}
                        title={t("Toggle Category")}
                        ariaLabel={t("Toggle Category")}
                        onClick={handleToggleClick}
                        buttonType={ButtonTypes.CUSTOM}
                        className={classes.toggle}
                    >
                        {isOpen ? (
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
                            isHidden: collapsible ? !isOpen : false,
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
