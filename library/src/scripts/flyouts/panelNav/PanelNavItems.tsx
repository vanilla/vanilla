/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef, useEffect } from "react";
import { INavigationTreeItem } from "@vanilla/library/src/scripts/@types/api/core";
import { useSiteNavContext } from "@library/navigation/SiteNavContext";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { LeftChevronIcon, RightChevronIcon } from "@library/icons/common";
import classNames from "classnames";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import Heading from "@library/layout/Heading";

interface IProps {
    navItems: INavigationTreeItem[];
    pushParentItem: (item: INavigationTreeItem) => void;
    popParentItem: () => void;
    isNestable: boolean;
    nestedTitle?: string;
    canGoBack?: boolean;
    extraSections?: React.ReactNode;
    isActive?: boolean;
}

export function PanelNavItems(props: IProps) {
    const { isActive } = props;
    const buttonRef = useRef<HTMLButtonElement | null>(null);
    const { categoryRecordType } = useSiteNavContext();
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
                    <DropDownItemSeparator />
                    <Heading
                        title={props.nestedTitle}
                        className={classNames("dropDown-sectionHeading", classes.sectionHeading)}
                    />
                </>
            )}
            <div className={classes.panelNavItems}>
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
                <div className={classNames(classes.panelContent, { isNested: props.canGoBack })}>
                    <ul className={classes.sectionContents}>
                        {props.navItems.map((navItem, i) => {
                            const showChildren = categoryRecordType === navItem.recordType && props.isNestable;

                            if (showChildren) {
                                return (
                                    <DropDownItemButton
                                        key={i}
                                        onClick={() => {
                                            props.pushParentItem(navItem);
                                        }}
                                    >
                                        <span className={classes.text}>{navItem.name}</span>
                                        <RightChevronIcon className={classes.arrow} />
                                    </DropDownItemButton>
                                );
                            } else {
                                return (
                                    <DropDownItemLink key={i} to={navItem.url}>
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
