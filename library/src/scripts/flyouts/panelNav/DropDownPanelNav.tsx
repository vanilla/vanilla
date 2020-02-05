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

interface IProps {
    title: string;
    navItems: INavigationTreeItem[];
    afterNavSections?: React.ReactNode;
    isNestable: boolean;
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
            <DropDownItemSeparator />
            <Heading
                title={props.title}
                className={classNames("dropDown-sectionHeading", classes.sectionHeading)}
                aria-hidden={parentNavItems.length > 0}
            />
            <div className={classNames(classes.panel, classes.panelFirst)} aria-hidden={parentNavItems.length > 0}>
                <PanelNavItems
                    isActive={false}
                    navItems={props.navItems}
                    isNestable={props.isNestable}
                    popParentItem={popParentItem}
                    pushParentItem={pushParentItem}
                    canGoBack={false}
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
                            isActive={i === parentNavItems.length - 1}
                            key={key}
                            navItems={currentItems}
                            isNestable={props.isNestable}
                            popParentItem={popParentItem}
                            pushParentItem={pushParentItem}
                            canGoBack={parent !== null}
                            nestedTitle={parent?.name}
                        />
                    );
                })}
            </EntranceAnimation>
        </>
    );
}
