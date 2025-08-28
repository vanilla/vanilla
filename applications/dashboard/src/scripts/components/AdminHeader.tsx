/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardMenusApi, hasGroupLinkChildren } from "@dashboard/DashboardMenusApi";
import React, { useCallback, useEffect, useMemo, useState } from "react";
import { getMeta, getRelativeUrl } from "@library/utility/appUtils";

import DashboardTitleBar from "@dashboard/components/DashboardTitleBar";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import Heading from "@library/layout/Heading";
import { INavigationTreeItem } from "@library/@types/api/core";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { t } from "@vanilla/i18n";
import { useAppearanceNavItems } from "@dashboard/components/navigation/AppearanceNav.hooks";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    hamburgerContent?: React.ReactNode;
    activeSectionID?: string;
}

interface INavItemsProviderProps {
    onNavItemsChange: (items: INavigationTreeItem[]) => void;
}

export default function AdminHeader(props: IProps) {
    const { activeSectionID } = props;
    const dropdownClasses = dropDownClasses();
    const sections = DashboardMenusApi.useMenus();
    const [navItems, setNavItems] = useState<INavigationTreeItem[] | undefined>();
    const [activeSection, setActiveSection] = useState<DashboardMenusApi.Section | undefined>();
    const [activeItem, setActiveItem] = useState<DashboardMenusApi.Section | DashboardMenusApi.GroupLink | undefined>();
    const [registeredNavItems, setRegisteredNavItems] = useState<Record<string, INavigationTreeItem[]>>({});

    useEffect(() => {
        if (sections.data) {
            const activeItem = getActiveItem(sections.data);
            setActiveItem(activeItem);
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

    const appearanceNavItems = useAppearanceNavItems(useUniqueID("AppearanceNav"));

    const handleNavItemsUpdate = useCallback((sectionId: string, items: INavigationTreeItem[]) => {
        setRegisteredNavItems((prev) => ({
            ...prev,
            [sectionId]: items,
        }));
    }, []);

    const joinedSections = useMemo(() => {
        return sections.data?.map((section) => {
            if (section.id === "appearance") {
                return {
                    ...section,
                    children: convertNavItemsToGroups(appearanceNavItems),
                };
            }

            // Check if there are registered nav items for this section
            const sectionNavItems = registeredNavItems[section.id];
            if (sectionNavItems && sectionNavItems.length > 0) {
                return {
                    ...section,
                    children: convertNavItemsToGroups(sectionNavItems),
                };
            }

            return section;
        });
    }, [sections.data, appearanceNavItems, registeredNavItems]);

    return (
        <>
            {/* AIDEV-NOTE: Render registered nav item providers */}
            {AdminHeader.registeredNavProviders.map((provider, index) => {
                const ProviderComponent = provider.component;
                return (
                    <ProviderComponent
                        key={`${provider.sectionId}-${index}`}
                        onNavItemsChange={(items) => handleNavItemsUpdate(provider.sectionId, items)}
                    />
                );
            })}
            {joinedSections && (
                <>
                    <DashboardTitleBar
                        hamburgerContent={hamburgerContent}
                        sections={joinedSections}
                        activeSectionID={activeSection?.id}
                    />
                </>
            )}
        </>
    );
}

type NavItemsProvider = {
    sectionId: string;
    component: React.ComponentType<INavItemsProviderProps>;
};

// AIDEV-NOTE: Storage for registered navigation item providers
AdminHeader.registeredNavProviders = [] as NavItemsProvider[];

/**
 * Register a navigation items provider component for a specific admin section.
 * The component will be rendered by AdminHeader and should call onNavItemsChange with the nav items.
 *
 * @param sectionId - The ID of the admin section (e.g., "analytics", "appearance")
 * @param component - React component that provides nav items via onNavItemsChange callback
 */
AdminHeader.registerNavItemsProvider = (sectionId: string, component: React.ComponentType<INavItemsProviderProps>) => {
    AdminHeader.registeredNavProviders.push({
        sectionId,
        component,
    });
};

function convertNavItemsToGroups(navItems: INavigationTreeItem[]): DashboardMenusApi.Group[] {
    return navItems.map((item) => {
        // Special handling for "Layouts" - create nested Groups
        if (item.name === "Layouts" && item.children && item.children.length > 0) {
            return {
                name: item.name,
                id: item.recordID.toString(),
                children: item.children.map((child) => ({
                    name: child.name,
                    id: child.recordID.toString(),
                    children: convertNavItemsToGroupLinks(child.children || []),
                })),
            };
        } else {
            // Regular handling for other items - convert to GroupLinks
            return {
                name: item.name,
                id: item.recordID.toString(),
                children: convertNavItemsToGroupLinks(item.children || []),
            };
        }
    });
}

function convertNavItemsToGroupLinks(navItems: INavigationTreeItem[]): DashboardMenusApi.GroupLink[] {
    return navItems.flatMap((item) => {
        const groupLink: DashboardMenusApi.GroupLink = {
            name: item.name,
            id: item.recordID.toString(),
            parentID: item.parentID?.toString() || "0",
            url: item.url || "",
            react: true,
            ...(item.badge && { badge: item.badge }),
        };

        if (item.children && item.children.length > 0) {
            // Include this item and recursively include its children
            return [groupLink, ...convertNavItemsToGroupLinks(item.children)];
        } else {
            // This is a leaf item
            return [groupLink];
        }
    });
}

function getActiveItem(treeItems): DashboardMenusApi.Section | DashboardMenusApi.GroupLink {
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

function getActiveSection(treeItems, activeItemID, sections): DashboardMenusApi.Section {
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

            // Handle both GroupLink[] and Group[] children
            if (hasGroupLinkChildren(item)) {
                // Convert GroupLinks to INavigationTreeItem[]
                const children = item.children.map((child) => ({
                    sort: 0,
                    name: child.name,
                    recordType: "dashboardItem" as const,
                    parentID: child.parentID,
                    recordID: child.id,
                    url: getRelativeUrl(child.url),
                    children: [],
                }));
                navItemsTree[i].children = children;
            } else {
                // Recursively convert nested Groups
                const children = getNavItemsTree(item.children);
                navItemsTree[i].children = children;
            }
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
