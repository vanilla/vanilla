/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    ICommunityManagementRecord,
    IEscalation,
    IReason,
    IReport,
    ITriageRecord,
} from "@dashboard/moderation/CommunityManagementTypes";
import { IUser, IUserFragment } from "@library/@types/api/users";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { STORY_IPSUM_LONG, STORY_IPSUM_LONG2 } from "@library/storybook/storyData";
import { configureStore, createReducer } from "@reduxjs/toolkit";
import { labelize, notEmpty, stableObjectHash } from "@vanilla/utils";
import { CurrentUserContextProvider } from "@library/features/users/userHooks";
import { LiveAnnouncer } from "react-aria-live";
import { LoadStatus } from "@library/@types/api/core";
import { Provider } from "react-redux";
import { Router } from "react-router-dom";
import { STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { blessStringAsSanitizedHtml } from "@vanilla/dom-utils";
import { createMemoryHistory } from "history";
import random from "lodash-es/random";
import { setMeta } from "@library/utility/appUtils";

export class CommunityManagementFixture {
    private static getRecord(record?: Partial<ICommunityManagementRecord>): ICommunityManagementRecord {
        return {
            recordType: "discussion",
            recordID: "1",
            placeRecordType: "category",
            placeRecordID: 1,
            recordName: "Example Discussion Name",
            recordFormat: "html",
            recordUrl: "https://example.tld",
            recordIsLive: true,
            recordWasEdited: true,
            recordExcerpt: STORY_IPSUM_SHORT,
            recordUserID: 7,
            recordUser: UserFixture.createMockUser({ name: "John", userID: 7 }),
            placeRecordUrl: "https://example.tld",
            placeRecordName: "Example Category Name",
            recordDateInserted: new Date(2024, 3, 5).toISOString(),
            recordHtml: blessStringAsSanitizedHtml(STORY_IPSUM_LONG + STORY_IPSUM_LONG2),
            ...record,
        };
    }

    public static getReason(reason?: Partial<IReason>): IReason {
        return {
            reportReasonJunctionID: 1,
            reportReasonID: "1",
            reportID: 1,
            name: "Spam",
            description: `This post violates ${reason?.name || "spam"} rules.`,
            sort: 0,
            countReports: 5,
            deleted: false,
            ...reason,
        };
    }

    public static getReasons(names: string[], common: Partial<IReason> = {}): IReason[] {
        const reasons = names.map((reason, index) => this.getReason({ name: labelize(reason), reportID: index }));
        return reasons;
    }

    public static getReportGroup(reportGroup?: Partial<ITriageRecord>): ITriageRecord {
        const record = this.getRecord(reportGroup);
        const reasons = this.getReasons(["spam", "community-rules"], { reportID: 1 });
        const reportUsers = ["Alice", "Mary", "Bob", "Some", "One", "Else"].map((name, index) =>
            UserFixture.createMockUser({ name, userID: index + 3 }),
        );
        return {
            ...record,
            reportReasons: reasons,
            countReportUsers: 24,
            reportUserIDs: reportUsers.map((user) => user.userID),
            countReports: reportUsers.length,
            dateLastReport: new Date(2024, 3, 10).toISOString(),
            reportUsers,
        };
    }

    public static getReport(report?: Partial<IReport>): IReport {
        const record = this.getRecord(report);
        const reasons = this.getReasons(["spam", "community-rules"], { reportID: 1 });
        const reportID = report?.reportID ?? random(1, 10000);
        return {
            ...record,
            reportID,
            dateInserted: new Date(2024, 3, 12).toISOString(),
            dateUpdated: new Date(2024, 3, 12).toISOString(),
            insertUserID: 2,
            insertUser: {
                ...UserFixture.createMockUser({ ...report?.insertUser }),
            } as IUserFragment,
            updateUserID: 2,
            status: "Open",
            noteHtml: blessStringAsSanitizedHtml(
                "<p>This post is spam. This user is re-posting the same content everywhere on the community</p>",
            ),
            reasons: reasons,
            isPending: false,
            isPendingUpdate: false,
            escalationID: null,
            escalationUrl: null,
            ...report,
        };
    }

    public static getEscalation(escalation: Partial<IEscalation> = {}): IEscalation {
        const record = this.getRecord(escalation);
        const countReports = escalation.countReports ?? 3;
        const reports = Array.from({ length: countReports }, (_, index) => this.getReport({ reportID: index + 1 }));

        return {
            ...record,
            url: "#",
            name: record.recordName,
            escalationID: 1,
            dateInserted: new Date(2024, 3, 12).toISOString(),
            dateUpdated: new Date(2024, 3, 12).toISOString(),
            insertUserID: 2,
            insertUser: UserFixture.createMockUser({ name: "AdamC" }),
            updateUserID: 2,
            updateUser: UserFixture.createMockUser({ name: "AdamC" }),
            status: "Open",
            countComments: 5,
            assignedUserID: null,
            assignedUser: null,
            dateLastReport: new Date(2024, 3, 12).toISOString(),
            countReports: 5,
            countReportUsers: 5,
            reportUsers: reports.map((report) => report.insertUser!),
            dateAssigned: null,
            reportReasons: this.getReasons(["spam", "community-rules"]),
            reports,
            reportIDs: reports.map((report) => report.reportID),
            reportReasonIDs: reports.flatMap((report) => report.reasons.map((reason) => `${reason.reportID}`)),
            reportUserIDs: reports.map((report) => report?.insertUser?.userID).filter(notEmpty),
            recordID: record.recordID ?? "4",
            ...escalation,
        };
    }

    public static getWrappedComponent(children: React.ReactNode) {
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    enabled: true,
                    retry: false,
                    staleTime: Infinity,
                },
            },
        });

        const history = createMemoryHistory();

        // AIDEV-NOTE: Mock global meta values for site sections used by AdminHeader useAppearanceNavItems
        setMeta("siteSection", {
            basePath: "",
            contentLocale: "en",
            sectionGroup: "vanilla",
            sectionID: "0",
            name: "Test Site",
            apps: { forum: true },
            attributes: {},
        });
        setMeta("defaultSiteSection", {
            basePath: "",
            contentLocale: "en",
            sectionGroup: "vanilla",
            sectionID: "0",
            name: "Test Site",
            apps: { forum: true },
            attributes: {},
        });
        setMeta("siteSectionSlugs", []);
        setMeta("postTypes", ["discussion"]);

        const testReducer = createReducer(
            {
                users: {
                    current: UserFixture.adminAsCurrent,
                    usersByID: {
                        2: {
                            ...UserFixture.adminAsCurrent,
                        },
                    },
                },
                // AIDEV-NOTE: Add config state to fix AdminHeader useAppearanceNavItems dependency
                config: {
                    configsByLookupKey: {
                        [stableObjectHash(["customLayout.home"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                        [stableObjectHash(["customLayout.discussionList"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                        [stableObjectHash(["customLayout.categoryList"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                        [stableObjectHash(["layoutEditor.home"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                        [stableObjectHash(["layoutEditor.discussionList"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                        [stableObjectHash(["layoutEditor.categoryList"])]: {
                            status: LoadStatus.SUCCESS,
                            data: {},
                        },
                    },
                },
                dashboard: {
                    dashboardSections: {
                        status: "SUCCESS",
                        data: [],
                    },
                },
            },
            () => {},
        );
        const store = configureStore({ reducer: testReducer });

        return (
            <Router history={history}>
                <Provider store={store}>
                    <LiveAnnouncer>
                        <CurrentUserContextProvider currentUser={UserFixture.adminAsCurrent.data}>
                            <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
                        </CurrentUserContextProvider>
                    </LiveAnnouncer>
                </Provider>
            </Router>
        );
    }
}
