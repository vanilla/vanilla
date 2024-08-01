/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useState } from "react";
import DashboardTitleBar from "@dashboard/components/DashboardTitleBar";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { useDashboardSection } from "@dashboard/DashboardSectionHooks";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { IDashboardGroupLink, IDashboardSection } from "@dashboard/DashboardSectionType";
import { INavigationTreeItem } from "@library/@types/api/core";
import { getRelativeUrl } from "@library/utility/appUtils";

interface IProps {
    hamburgerContent?: React.ReactNode;
    activeSectionID?: string;
}

export default function AdminHeader(props: IProps) {
    const { activeSectionID } = props;
    const dropdownClasses = dropDownClasses();
    const sections = useDashboardSection();
    const [navItems, setNavItems] = useState<INavigationTreeItem[] | undefined>();
    const [activeSection, setActiveSection] = useState<IDashboardSection | undefined>();
    const [activeItem, setActiveItem] = useState<IDashboardSection | IDashboardGroupLink | undefined>();
    const [childlessSections, setChildlessSections] = useState<IDashboardSection[] | undefined>();

    useEffect(() => {
        if (sections.data) {
            const activeItem = getActiveItem(sections.data);
            setActiveItem(activeItem);

            const childlessSections = getChildlessSections(sections.data);
            setChildlessSections(childlessSections);
        }
    }, [sections.data, setActiveItem]);

    useEffect(() => {
        if (sections.data && (activeSectionID || activeItem)) {
            const activeSection = getActiveSection(sections.data, activeSectionID ?? activeItem?.id, sections.data);
            setActiveSection(activeSection);
        }
    }, [sections.data, activeItem, activeSectionID]);

    useEffect(() => {
        if (activeSection) {
            const navItems = getNavItemsTree(activeSection.children);
            setNavItems(navItems);
        }
    }, [activeSection]);

    const hamburgerContent = props.hamburgerContent ? (
        props.hamburgerContent
    ) : (
        <>
            {sections.data && activeSection && activeItem && navItems && (
                <>
                    <hr className={dropdownClasses.separator} />
                    <Heading title={t(activeSection.name)} className={dropdownClasses.sectionHeading} />
                    <DropDownPanelNav
                        navItems={navItems}
                        isNestable
                        activeRecord={{ recordID: activeItem.id, recordType: "customLink" }}
                    />
                </>
            )}
        </>
    );

    return (
        <>
            {childlessSections && (
                <>
                    <DashboardTitleBar
                        hamburgerContent={hamburgerContent}
                        sections={childlessSections}
                        activeSectionID={activeSection?.id}
                    />
                </>
            )}
        </>
    );
}

function getActiveItem(treeItems): IDashboardSection | IDashboardGroupLink {
    let activeItem;

    for (let item of treeItems) {
        if (item.url && window.location.href.includes(item.url.replace("~", ""))) {
            activeItem = item;
            break;
        }

        if (item.children && item.children.length > 0) {
            activeItem = getActiveItem(item.children);
            if (activeItem) {
                break;
            }
        }
    }

    return activeItem;
}

function getActiveSection(treeItems, activeItemID, sections): IDashboardSection {
    let activeSection;
    for (let item of treeItems) {
        if (item.id === activeItemID) {
            activeSection = item.hasOwnProperty("parentID")
                ? sections.filter((section) => section.id === item.parentID)[0]
                : item;
            break;
        }

        if (item.children && item.children.length > 0) {
            activeSection = getActiveSection(item.children, activeItemID, sections);
            if (activeSection) {
                break;
            }
        }
    }

    return activeSection;
}

function getNavItemsTree(items): INavigationTreeItem[] {
    let navItemsTree: INavigationTreeItem[] = [];

    items.forEach((item, i) => {
        if (item.children && item.children.length > 0) {
            navItemsTree.push({
                sort: 0,
                name: item.name,
                recordType: "panelMenu",
                parentID: item.parentID ?? 0,
                recordID: item.id,
                children: [],
            });
            const children = getNavItemsTree(item.children);
            navItemsTree[i].children = children;
        } else {
            navItemsTree.push({
                sort: 0,
                name: item.name,
                recordType: "dashboardItem",
                parentID: item.parentID,
                recordID: item.id,
                url: getRelativeUrl(item.url),
                children: [],
            });
        }
    });

    return navItemsTree;
}

function getChildlessSections(sections: IDashboardSection[]): IDashboardSection[] {
    return sections.map((section) => ({ ...section, children: [] })) as unknown as IDashboardSection[];
}
