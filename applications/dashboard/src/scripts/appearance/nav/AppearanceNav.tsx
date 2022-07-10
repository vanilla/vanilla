/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import SiteNav from "@library/navigation/SiteNav";
import appearanceNavClasses from "./AppearanceNav.classes";
import { useUniqueID } from "@library/utility/idUtils";
import { SiteNavNodeTypes } from "@library/navigation/SiteNavNodeTypes";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { useAppearanceNavItems } from "@dashboard/appearance/nav/AppearanceNav.hooks";
import { useLocation } from "react-router-dom";
import { findMatchingPath, flattenItems } from "@library/routing/routingUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Heading from "@library/layout/Heading";
import { t } from "@vanilla/i18n";
import { useLayout } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";

interface IProps {
    id?: string;
    className?: string;
    collapsible?: boolean;
    title?: string;
    asHamburger?: boolean;
}

export function AppearanceNav(props: IProps) {
    const { collapsible = true } = props;
    const dropdownClasses = dropDownClasses();

    const location = useLocation();

    const classes = appearanceNavClasses();
    const ownID = useUniqueID("AppearanceNav");
    const id = props.id ?? ownID;

    const navItems = useAppearanceNavItems(id);

    const flatNavItems = flattenItems(navItems, "children");
    const urls = flatNavItems.filter((item) => !!item.url).map(({ url }) => url!);
    const matchingPath = findMatchingPath(urls, location.pathname);
    const matchingNavItem = flatNavItems.find(({ url }) => url === matchingPath);

    const [lastHovered, setLastHovered] = React.useState("");
    useLayout(lastHovered);

    if (props.asHamburger) {
        return (
            <>
                <hr className={dropdownClasses.separator} />
                <Heading title={t("Appearance")} className={dropdownClasses.sectionHeading} />
                <DropDownPanelNav
                    navItems={navItems}
                    isNestable
                    activeRecord={matchingNavItem ?? { recordID: "notspecified", recordType: "customLink" }}
                />
            </>
        );
    }

    return (
        <SiteNav
            activeRecord={matchingNavItem}
            id={id}
            collapsible={collapsible}
            className={classes.root}
            siteNavNodeTypes={SiteNavNodeTypes.DASHBOARD}
            clickableCategoryLabels={true}
            title={props.title}
            onItemHover={(item) => {
                item.recordType === "customLayout" && setLastHovered(`${item.recordID}`);
            }}
        >
            {navItems}
        </SiteNav>
    );
}
