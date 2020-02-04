/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { EntranceAnimation, FromDirection } from "@library/animation/EntranceAnimation";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { PanelNavItems } from "@library/flyouts/panelNav/PanelNavItems";
import Heading from "@library/layout/Heading";
import { useSiteNavContext } from "@library/navigation/SiteNavContext";
import { INavigationTreeItem } from "@vanilla/library/src/scripts/@types/api/core";
import classNames from "classnames";
import React, { useState } from "react";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";

interface IProps {
    title: string;
    navItems: INavigationTreeItem[];
    afterNavSections?: React.ReactNode;
    isNestable: boolean;
}

export function DropDownPanelNav(props: IProps) {
    const [parentNavItems, setParentNavItems] = useState<Array<INavigationTreeItem | null>>([null]);
    const [isPopping, setIsPopping] = useState(false);

    const classes = dropDownClasses();

    const popParentItem = () => {
        if (parentNavItems.length > 1) {
            // We can't pop off the root.
            parentNavItems.pop();
            setIsPopping(true);
            setParentNavItems(Array.from(parentNavItems));
        }
    };

    const pushParentItem = (item: INavigationTreeItem) => {
        parentNavItems.push(item);
        setParentNavItems(Array.from(parentNavItems));
    };

    return (
        <>
            <DropDownItemSeparator />
            <Heading title={props.title} className={classNames("dropDown-sectionHeading", classes.sectionHeading)} />
            <EntranceAnimation
                onDestroyed={() => {
                    setIsPopping(false);
                }}
                isEntered={true}
                fromDirection={FromDirection.RIGHT}
                className={classes.panel}
                aria-hidden={true}
                firstItemProps={{ className: classes.panelFirst }}
                lastItemProps={
                    isPopping || parentNavItems.length > 1
                        ? { className: classes.panelLast, "aria-hidden": "false" }
                        : undefined
                }
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
                            // The first page gets the extra sections.
                            extraSections={parent === null && props.afterNavSections}
                        />
                    );
                })}
            </EntranceAnimation>
        </>
    );
}
