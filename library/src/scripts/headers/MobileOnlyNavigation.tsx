/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo } from "react";
import { varItemToNavTreeItem, getActiveRecord } from "@library/flyouts/Hamburger";
import { navigationVariables } from "@library/headers/navigationVariables";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { PanelNavItems } from "@library/flyouts/panelNav/PanelNavItems";
import { INavigationTreeItem } from "@library/@types/api/core";
import { notEmpty } from "@vanilla/utils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { cx } from "@library/styles/styleShim";

export default function MobileOnlyNavigation(props: {}) {
    const classes = dropDownClasses();

    const { mobileOnlyNavigationItems } = navigationVariables();

    const [treeItems, activeRecord] = useMemo(() => {
        const treeItems = mobileOnlyNavigationItems.map((item) => varItemToNavTreeItem(item)).filter(notEmpty);
        const activeRecord = getActiveRecord(treeItems);
        return [treeItems, activeRecord];
    }, [mobileOnlyNavigationItems]);

    return (
        <>
            {treeItems.length > 0 && (
                <>
                    <DropDownItemSeparator />
                    <div className={cx(classes.panelMobileOnly)}>
                        <PanelNavItems
                            navItems={treeItems}
                            activeRecord={activeRecord}
                            popParentItem={() => {}}
                            pushParentItem={(item: INavigationTreeItem) => {}}
                            isNestable={false}
                        />
                    </div>
                </>
            )}
        </>
    );
}
