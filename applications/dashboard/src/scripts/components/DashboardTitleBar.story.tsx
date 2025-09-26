/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */
import DashboardTitleBar from "@dashboard/components/DashboardTitleBar";
import type { DashboardMenusApi } from "@dashboard/DashboardMenusApi";
import { DashboardMenusApiFixture } from "@dashboard/DashboardMenusApi.fixture";
import { IMe } from "@library/@types/api/users";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { PermissionsFixtures } from "@library/features/users/Permissions.fixtures";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import Heading from "@library/layout/Heading";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { setMeta } from "@library/utility/appUtils";
import { useEffect } from "react";

export default {
    title: "Headers/Dashboard Title Bar",
};

const mockSections: DashboardMenusApi.Section[] = DashboardMenusApiFixture.mockMenus();

const mockRegisterUserInfo: IMe = UserFixture.createMockUser({
    name: "Neena",
    userID: 1,
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
    emailConfirmed: true,
});

const dumbDashboardTreeItems = [
    { children: [], name: "Some Link", parentID: 0, recordID: 1, recordType: "dashboardItem", sort: 0, url: "#" },
    { children: [], name: "Another link", parentID: 0, recordID: 2, recordType: "dashboardItem", sort: 0, url: "#" },
];

const dumbHumburgerContent = (
    <>
        <hr className={dropDownClasses().separator} />
        <Heading title={"Some Content Section"} className={dropDownClasses().sectionHeading} />
        <DropDownPanelNav
            navItems={dumbDashboardTreeItems}
            isNestable={false}
            activeRecord={{
                recordID: "notspecified",
                recordType: "customLink",
            }}
        />
    </>
);

export const RegularDashboardTitleBar = storyWithConfig({ useWrappers: false }, () => {
    return <DashboardTitleBar sections={mockSections} />;
});

export const TitleBarWithHamburgerOpenOnSmallerViews = storyWithConfig(
    {
        useWrappers: false,
    },
    () => {
        useEffect(() => {
            setMeta("context.siteID", 1);
        });
        return (
            <CurrentUserContextProvider currentUser={mockRegisterUserInfo}>
                <PermissionsFixtures.AllPermissions>
                    <DashboardTitleBar
                        hamburgerContent={dumbHumburgerContent}
                        isCompact={true}
                        forceHamburgerOpen={true}
                        sections={[]}
                    />
                </PermissionsFixtures.AllPermissions>
            </CurrentUserContextProvider>
        );
    },
);
