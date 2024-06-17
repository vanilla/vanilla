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
import { t } from "@vanilla/i18n";

interface IProps {
    id?: string;
    className?: string;
    collapsible?: boolean;
    title?: string;
    asHamburger?: boolean;
}

function useModerationNav(): INavigationTreeItem[] | null {
    const sections = useDashboardSection();

    const moderationNav = sections.data?.find((section) => section.id === "moderation");
    if (!moderationNav) {
        return null;
    }

    return moderationNav.children.map((item) => {
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

export function ModerationNav(props: IProps) {
    const { collapsible = true } = props;
    const dropdownClasses = dropDownClasses();
    const navItems = useModerationNav() ?? [];

    const ownID = useUniqueID("ModerationNav");
    const id = props.id ?? ownID;

    // const navItems: INavigationTreeItem[] = [
    //     {
    //         name: "Content",
    //         parentID: "root",
    //         recordType: "panelMenu",
    //         recordID: "content",
    //         children: [
    //             {
    //                 name: "Triage",
    //                 parentID: "content",
    //                 recordType: "link",
    //                 recordID: "triage",
    //                 url: "/dashboard/content/triage",
    //             },
    //             {
    //                 name: "Reports",
    //                 parentID: "content",
    //                 recordType: "link",
    //                 recordID: "reports",
    //                 url: "/dashboard/content/reports",
    //             },
    //             {
    //                 name: "Escalations",
    //                 parentID: "content",
    //                 recordType: "link",
    //                 recordID: "escalations",
    //                 url: "/dashboard/content/escalations",
    //             },
    //         ],
    //     },
    //     {
    //         name: "Requests",
    //         parentID: "root",
    //         recordType: "panelMenu",
    //         recordID: "requests",
    //         children: [
    //             {
    //                 name: "Applications",
    //                 parentID: "requests",
    //                 recordType: "link",
    //                 recordID: "applications",
    //                 url: "/dashboard/user/applicants",
    //             },
    //             {
    //                 name: "Badge Requests",
    //                 parentID: "requests",
    //                 recordType: "link",
    //                 recordID: "badge-requests",
    //                 url: "/badge/requests",
    //             },
    //         ],
    //     },
    //     {
    //         name: "Site",
    //         parentID: "root",
    //         recordType: "panelMenu",
    //         recordID: "site",
    //         children: [
    //             {
    //                 name: "Users",
    //                 parentID: "site",
    //                 recordType: "link",
    //                 recordID: "users",
    //                 url: "/user",
    //             },
    //             {
    //                 name: "Messages",
    //                 parentID: "site",
    //                 recordType: "link",
    //                 recordID: "messages",
    //                 url: "/dashboard/settings/bans",
    //             },
    //             {
    //                 name: "Ban Rules",
    //                 parentID: "site",
    //                 recordType: "link",
    //                 recordID: "ban-rules",
    //                 url: "/dashboard/message",
    //             },
    //             {
    //                 name: "Flood Control",
    //                 parentID: "site",
    //                 recordType: "link",
    //                 recordID: "flood-control",
    //                 url: "/vanilla/settings/flood-control",
    //             },
    //             {
    //                 name: "Change Log",
    //                 parentID: "site",
    //                 recordType: "link",
    //                 recordID: "change-log",
    //                 url: "/dashboard/log/edits",
    //             },
    //         ],
    //     },
    // ];

    if (props.asHamburger) {
        return (
            <>
                <hr className={dropdownClasses.separator} />
                <Heading title={t("Moderation")} className={dropdownClasses.sectionHeading} />
                <DropDownPanelNav navItems={navItems} isNestable />
            </>
        );
    }

    return (
        <SiteNav
            initialOpenType="appearance"
            initialOpenDepth={1}
            id={id}
            collapsible={collapsible}
            siteNavNodeTypes={SiteNavNodeTypes.DASHBOARD}
            clickableCategoryLabels={true}
            title={props.title}
        >
            {navItems}
        </SiteNav>
    );
}
