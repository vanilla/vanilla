/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EntranceAnimation, FromDirection } from "@library/animation/EntranceAnimation";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { PanelNavItems } from "@library/flyouts/panelNav/PanelNavItems";
import Heading from "@library/layout/Heading";
import { INavigationTreeItem } from "@vanilla/library/src/scripts/@types/api/core";
import classNames from "classnames";
import React, { useRef, useState } from "react";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { CloseTinyIcon } from "@library/icons/common";
import { t } from "@vanilla/i18n";

interface IProps {
    title: string;
    navItems: INavigationTreeItem[];
    activeRecord: IActiveRecord;
    afterNavSections?: React.ReactNode;
    isNestable: boolean;
    onClose?: () => void;
}

export function DropDownPanelNav(props: IProps) {
    const [parentNavItems, setParentNavItems] = useState<Array<INavigationTreeItem | null>>([]);

    const classes = dropDownClasses();

    const popParentItem = () => {
        parentNavItems.pop();
        setParentNavItems(Array.from(parentNavItems));
    };

    const pushParentItem = (item: INavigationTreeItem) => {
        parentNavItems.push(item);
        setParentNavItems(Array.from(parentNavItems));
    };

    return (
        <>
            <Heading
                title={props.title}
                className={classNames("dropDown-sectionHeading", classes.sectionHeading)}
                aria-hidden={parentNavItems.length > 0}
            >
                <div className={classes.headingContentContainer}>
                    <div className={classes.headingTitleContainer}> {props.title} </div>
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
            <div className={classNames(classes.panel, classes.panelFirst)} aria-hidden={parentNavItems.length > 0}>
                <PanelNavItems
                    activeRecord={props.activeRecord}
                    isActive={false}
                    navItems={props.navItems}
                    isNestable={props.isNestable}
                    popParentItem={popParentItem}
                    pushParentItem={pushParentItem}
                    canGoBack={false}
                    onClose={props.onClose}
                    // The first page gets the extra sections.
                    extraSections={props.afterNavSections}
                />
            </div>
            <EntranceAnimation
                isEntered={true}
                fromDirection={FromDirection.RIGHT}
                className={classes.panel}
                aria-hidden={true}
                lastItemProps={{ className: classes.panelLast, "aria-hidden": "false" }}
            >
                {parentNavItems.map((parent, i) => {
                    const currentItems = parent ? parent.children : props.navItems;
                    let key = "root";
                    if (parent) {
                        key = parent.recordType + parent.recordID;
                    }

                    return (
                        <PanelNavItems
                            activeRecord={props.activeRecord}
                            isActive={i === parentNavItems.length - 1}
                            key={key}
                            navItems={currentItems}
                            isNestable={props.isNestable}
                            popParentItem={popParentItem}
                            pushParentItem={pushParentItem}
                            canGoBack={parent !== null}
                            onClose={props.onClose}
                            nestedTitle={parent?.name}
                        />
                    );
                })}
            </EntranceAnimation>
        </>
    );
}
