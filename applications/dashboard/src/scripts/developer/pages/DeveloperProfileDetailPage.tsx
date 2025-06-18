/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { StaffAdminLayout } from "@dashboard/components/navigation/StaffAdminLayout";
import { developerProfileClasses } from "@dashboard/developer/pages/DeveloperProfilePage.classes";
import { DeveloperProfileProvider } from "@dashboard/developer/profileViewer/DeveloperProfile.context";
import { DeveloperProfileDetailsPanel } from "@dashboard/developer/profileViewer/DeveloperProfile.DetailsPanel";
import { DeveloperProfileFilterPanel } from "@dashboard/developer/profileViewer/DeveloperProfile.FilterPanel";
import { DeveloperProfileFlameChart } from "@dashboard/developer/profileViewer/DeveloperProfile.FlameChart";
import { DeveloperProfileOptionsMenu } from "@dashboard/developer/profileViewer/DeveloperProfile.OptionsMenu";
import { DeveloperProfilerTimers } from "@dashboard/developer/profileViewer/DeveloperProfile.Timers";
import { useDeveloperProfileDetailsQuery } from "@dashboard/developer/profileViewer/DeveloperProfiles.hooks";
import { DeveloperProfileMetas } from "@dashboard/developer/profileViewer/DeveloperProfiles.metas";
import { CoreErrorMessages } from "@library/errorPages/CoreErrorMessages";
import Loader from "@library/loaders/Loader";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { Metas } from "@library/metas/Metas";
import BackLink from "@library/routing/links/BackLink";
import { useFallbackBackUrl } from "@library/routing/links/BackRoutingProvider";
import { useQueryStringSync } from "@library/routing/QueryString";
import { useQueryParam } from "@library/routing/routingUtils";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";
import { useEffect, useRef, useState } from "react";
import { useRouteMatch } from "react-router";

export function DeveloperProfilesDetailPage() {
    const profileID = useRouteMatch().params["profileID"];
    const query = useDeveloperProfileDetailsQuery(profileID);

    const classes = developerProfileClasses();
    useFallbackBackUrl("/settings/vanilla-staff/profiles");
    const tabRef = useRef<HTMLDivElement | null>(null);

    return (
        <DeveloperProfileProvider>
            <StaffAdminLayout
                title={
                    <>
                        <BackLink className={classes.backlink} />
                        {query.data?.name ?? <LoadingRectangle width={180} height={22} />}
                    </>
                }
                description={
                    query.data ? (
                        <Metas>
                            <DeveloperProfileMetas {...query.data} />
                        </Metas>
                    ) : (
                        <LoadingRectangle width={320} height={22} />
                    )
                }
                titleBarActions={query.data && <DeveloperProfileOptionsMenu profile={query.data} />}
                rightPanel={query.data && <DeveloperProfileFilterPanel profile={query.data} />}
                content={
                    <>
                        <ProfileDetailsContent query={query} tabRef={tabRef} />
                        {query.data && <DeveloperProfileDetailsPanel profile={query.data} />}
                    </>
                }
                contentClassNames={developerProfileClasses().pageContent}
            />
        </DeveloperProfileProvider>
    );
}

function ProfileDetailsContent(props: {
    query: ReturnType<typeof useDeveloperProfileDetailsQuery>;
    tabRef: React.RefObject<HTMLDivElement>;
}) {
    const tabs = ["timeline", "timers"];
    const queryTab = useQueryParam("tab", "timeline");
    const [selectedTabIndex, setSelectedTabIndex] = useState(0);
    useEffect(() => {
        const initialTabIndex = tabs.findIndex((t) => t === queryTab) ?? 0;
        setSelectedTabIndex(initialTabIndex);
    }, []);
    useQueryStringSync(
        {
            tab: tabs[selectedTabIndex],
        },
        {
            tab: "timeline",
        },
    );

    const { query } = props;

    if (query.isLoading) {
        return <Loader />;
    }

    if (query.isError) {
        return (
            <CoreErrorMessages
                error={
                    (query.error as any)?.response?.data?.message ?? (query.error as any)?.message ?? "Unknown Error"
                }
            />
        );
    }

    return (
        <Tabs
            activeTab={selectedTabIndex}
            setActiveTab={setSelectedTabIndex}
            tabType={TabsTypes.BROWSE}
            largeTabs
            data={[
                {
                    tabID: "timeline",
                    label: "Timeline",
                    contents: <DeveloperProfileFlameChart profile={query.data} />,
                },
                {
                    tabID: "timers",
                    label: "Timers",
                    contents: <DeveloperProfilerTimers profile={query.data} />,
                },
            ]}
        />
    );
}

export default DeveloperProfilesDetailPage;
