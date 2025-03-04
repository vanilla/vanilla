/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useDashboardSection } from "@dashboard/DashboardSectionHooks";
import { INavigationTreeItem } from "@library/@types/api/core";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import Heading from "@library/layout/Heading";
import SiteNav from "@library/navigation/SiteNav";
import { SiteNavNodeTypes } from "@library/navigation/SiteNavNodeTypes";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    id?: string;
    className?: string;
    collapsible?: boolean;
    asHamburger?: boolean;
    title: string;
    sectionID: string;
    navItems?: INavigationTreeItem[] | null;
}

function useDashboardNav(sectionID: string): INavigationTreeItem[] | null {
    const sections = useDashboardSection();

    const sectionNav = sections.data?.find((section) => section.id === sectionID);
    if (!sectionNav) {
        return null;
    }

    return sectionNav.children.map((item) => {
        return {
            name: item.name,
            parentID: "root",
            recordType: "panelMenu",
            recordID: item.id,
            children: item.children.map((child) => {
                return {
                    name: child.name,
                    parentID: item.id,
                    recordType: "link",
                    recordID: child.id,
                    url: child.url,
                    ...(child.badge && { badge: child.badge }),
                };
            }),
        };
    });
}

export function AdminNav(props: IProps) {
    const { collapsible = true } = props;
    const dropdownClasses = dropDownClasses();
    const ownNavItems = useDashboardNav(props.sectionID) ?? [];
    const navItems = props.navItems ?? ownNavItems;

    const ownID = useUniqueID("DashboardNav");
    const id = props.id ?? ownID;

    if (props.asHamburger) {
        return (
            <>
                <hr className={dropdownClasses.separator} />
                <Heading title={props.title} className={dropdownClasses.sectionHeading} />
                <DropDownPanelNav navItems={navItems} isNestable />
            </>
        );
    }

    return (
        <SiteNav
            className={props.className}
            initialOpenType="appearance"
            initialOpenDepth={1}
            id={id}
            collapsible={collapsible}
            siteNavNodeTypes={SiteNavNodeTypes.DASHBOARD}
            clickableCategoryLabels={true}
        >
            {navItems}
        </SiteNav>
    );
}
