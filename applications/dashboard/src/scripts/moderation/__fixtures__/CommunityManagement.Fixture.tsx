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
import { UserFixture } from "@library/features/__fixtures__/User.fixture";
import { STORY_IPSUM_LONG, STORY_IPSUM_LONG2 } from "@library/storybook/storyData";
import { labelize, notEmpty } from "@vanilla/utils";
import random from "lodash/random";
import { STORY_IPSUM_SHORT } from "@library/storybook/storyData";
import { IUser, IUserFragment } from "@library/@types/api/users";
import { createMemoryHistory } from "history";
import { Router } from "react-router-dom";
import { configureStore, createReducer } from "@reduxjs/toolkit";
import { Provider } from "react-redux";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

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
            recordDateUpdated: new Date(2024, 3, 7).toISOString(),
            recordHtml: STORY_IPSUM_LONG + STORY_IPSUM_LONG2,
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
            visibility: "public",
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
            noteHtml: "<p>This post is spam. This user is re-posting the same content everywhere on the community</p>",
            reasons: reasons,
            ...report,
        };
    }

    public static getEscalation(escalation: Partial<IEscalation> = {}): IEscalation {
        const record = this.getRecord(escalation);
        const countReports = escalation.countReports ?? 3;
        const reports = Array.from({ length: countReports }, (_, index) => this.getReport({ reportID: index + 1 }));

        return {
            ...record,
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
                    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
                </Provider>
            </Router>
        );
    }
}
