/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */
import * as React from "react";
import DashboardTitleBar from "@library/headers/DashboardTitleBar";
import { testStoreState } from "@library/__tests__/testStoreState";
import { LoadStatus } from "@library/@types/api/core";
import { IMe } from "@library/@types/api/users";
import { Provider } from "react-redux";
import getStore from "@library/redux/getStore";
import { setMeta } from "@library/utility/appUtils";
import { useEffect } from "react";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Heading from "@library/layout/Heading";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import { storyWithConfig } from "@library/storybook/StoryContext";

export default {
    title: "Headers/Dashboard Title Bar",
};

const makeMockRegisterUserInfo: IMe = {
    name: "Neena",
    userID: 1,
    permissions: [],
    isAdmin: true,
    photoUrl: "",
    dateLastActive: "",
    countUnreadNotifications: 1,
    countUnreadConversations: 1,
};

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

const initialState = testStoreState({
    users: {
        current: {
            status: LoadStatus.SUCCESS,
            data: makeMockRegisterUserInfo,
        },
        permissions: {
            status: LoadStatus.SUCCESS,
            data: {
                isAdmin: true,
                permissions: [],
            },
        },
    },
});

export const RegularDashboardTitleBar = storyWithConfig({ useWrappers: false }, () => {
    return <DashboardTitleBar />;
});

export const TitleBarWithMeboxOpen = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", -1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar forceMeBoxOpen={true} />
        </Provider>
    );
});

export const MeboxOpenWithAccountAndSupportLinks = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", 1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar forceMeBoxOpen={true} />
        </Provider>
    );
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
            <Provider store={getStore(initialState, true)}>
                <DashboardTitleBar hamburgerContent={dumbHumburgerContent} isCompact={true} forceHamburgerOpen={true} />
            </Provider>
        );
    },
);

export const MeboxOpenOnSmallerViews = storyWithConfig({ useWrappers: false }, () => {
    useEffect(() => {
        setMeta("context.siteID", 1);
    });
    return (
        <Provider store={getStore(initialState, true)}>
            <DashboardTitleBar forceMeBoxOpen={true} forceMeBoxOpenAsModal={true} isCompact={true} />
        </Provider>
    );
});
