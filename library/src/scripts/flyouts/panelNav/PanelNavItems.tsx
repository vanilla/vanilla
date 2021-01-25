/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useEffect } from "react";
import { INavigationTreeItem } from "@vanilla/library/src/scripts/@types/api/core";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { LeftChevronIcon, RightChevronIcon, CloseTinyIcon } from "@library/icons/common";
import classNames from "classnames";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import Heading from "@library/layout/Heading";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { t } from "@vanilla/i18n";

export interface IPanelNavItemsProps {
    navItems: INavigationTreeItem[];
    activeRecord: IActiveRecord;
    pushParentItem: (item: INavigationTreeItem) => void;
    popParentItem: () => void;
    isNestable: boolean;
    nestedTitle?: string;
    canGoBack?: boolean;
    extraSections?: React.ReactNode;
    isActive?: boolean;
    onClose?: () => void;
}

export function PanelNavItems(props: IPanelNavItemsProps) {
    const { isActive } = props;
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const prevFocusedRef = useRef<HTMLElement | null>(null);

    useEffect(() => {
        if (isActive) {
            prevFocusedRef.current = document.activeElement as HTMLElement;
            setTimeout(() => {
                buttonRef.current?.focus();
            }, 200);

            return () => {
                prevFocusedRef.current?.focus();
            };
        }
    }, [isActive]);

    const classes = dropDownClasses();
    return (
        <>
            {props.nestedTitle && (
                <>
                    <Heading
                        title={props.nestedTitle}
                        className={classNames("dropDown-sectionHeading", classes.sectionHeading)}
                    >
                        <div className={classes.headingContentContainer}>
                            {props.canGoBack && (
                                <Button
                                    buttonRef={buttonRef}
                                    baseClass={ButtonTypes.ICON_COMPACT}
                                    onClick={props.popParentItem}
                                    className={classes.backButton}
                                >
                                    <LeftChevronIcon className={classes.arrow} />
                                </Button>
                            )}
                            <div className={classes.headingTitleContainer}> {props.nestedTitle} </div>
                            <Button
                                className={classes.closeButton}
                                onClick={props.onClose}
                                baseClass={ButtonTypes.ICON_COMPACT}
                            >
                                <ScreenReaderContent>{t("Close")}</ScreenReaderContent>
                                <CloseTinyIcon />
                            </Button>
                        </div>
                    </Heading>
                </>
            )}
            <div className={classes.panelNavItems}>
                <div className={classNames(classes.panelContent, { isNested: props.canGoBack })}>
                    <ul className={classes.sectionContents}>
                        {props.navItems.map((navItem, i) => {
                            const showChildren = props.isNestable && navItem.children.length > 0;
                            const isActive =
                                navItem.recordType === props.activeRecord.recordType &&
                                navItem.recordID === props.activeRecord.recordID;

                            if (showChildren) {
                                return (
                                    <DropDownItemButton
                                        isActive={isActive}
                                        key={i}
                                        onClick={() => {
                                            props.pushParentItem(navItem);
                                        }}
                                        className={classes.itemButton}
                                    >
                                        <span className={classes.text}>{navItem.name}</span>
                                        <RightChevronIcon className={classes.arrow} />
                                    </DropDownItemButton>
                                );
                            } else {
                                return navItem.isLink ? (
                                    <DropDownItemLink
                                        className={classes.itemButton}
                                        isActive={isActive}
                                        key={i}
                                        to={navItem.url}
                                    >
                                        <span className={classes.text}>{navItem.name}</span>
                                        <RightChevronIcon className={classes.arrow} />
                                    </DropDownItemLink>
                                ) : (
                                    <DropDownItemLink
                                        className={classes.itemButton}
                                        isActive={isActive}
                                        key={i}
                                        to={navItem.url}
                                    >
                                        {navItem.name}
                                    </DropDownItemLink>
                                );
                            }
                        })}
                    </ul>
                    {props.extraSections}
                </div>
            </div>
        </>
    );
}
