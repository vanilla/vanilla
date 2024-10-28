/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IEscalation, IReport } from "@dashboard/moderation/CommunityManagementTypes";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useQuery } from "@tanstack/react-query";
import { ReactNode, createContext, useContext, useEffect, useMemo, useState } from "react";

interface IPostRevisionContext {
    revisions: IDiscussion[] | null;
    activeRevision: IDiscussion | null;
    setActiveRevision(reportID: IReport["reportID"]): void;
    mostRecentRevision?: IDiscussion | null;
    mostRecentReport?: IReport;
    reports?: IReport[] | null;
    activeReport?: IReport | null;
}

export const PostRevisionContext = createContext<IPostRevisionContext>({
    revisions: null,
    activeRevision: null,
    setActiveRevision: () => null,
    mostRecentRevision: null,
    activeReport: null,
    reports: null,
});

export function usePostRevision() {
    return useContext(PostRevisionContext);
}

export function PostRevisionProvider(props: {
    recordType?: IEscalation["recordType"];
    recordID?: string;
    children: ReactNode;
}) {
    const { children, recordType, recordID } = props;
    const [activeReport, setActiveReport] = useState<IReport | null>(null);
    const [activeRevision, _setActiveRevision] = useState<IDiscussion | null>(null);

    const post = useQuery<IDiscussion, IError, IDiscussion>({
        queryFn: async () => {
            const response = await apiv2.get(
                `/discussions/${recordID}?expand=users&expand=category&expand=attachments&expand=status.log`,
            );

            return response.data;
        },
        queryKey: ["post", recordID],
        enabled: !!recordID,
    });

    const reportsForID = useQuery<any, IError, IReport[]>({
        queryFn: async () => {
            const response = await apiv2.get(`/reports?recordID=${recordID}&recordType=${recordType}&expand=users`);
            return response.data;
        },
        queryKey: ["reportsForID", recordID, recordType],
        enabled: !!recordID && !!recordType,
    });

    const setActiveRevision = (reportID: IReport["reportID"]) => {
        if (reportsForID.data && reportsForID.data.length > 0) {
            if (reportID === -1) {
                _setActiveRevision(post.data!);
                setActiveReport(null);
            } else {
                const report = reportsForID.data.find((report) => report.reportID === reportID);
                if (report) {
                    _setActiveRevision(makeDiscussionRevision(report));
                    setActiveReport(report);
                }
            }
        }
    };

    const makeDiscussionRevision = (report: IReport): IDiscussion => {
        return {
            ...post.data!,
            ...(report.recordName && { name: report.recordName }),
            ...(report.recordHtml && { body: report.recordHtml }),
            ...(report.recordDateInserted && { dateInserted: report.recordDateInserted }),
        };
    };

    const revisions = useMemo(() => {
        if (reportsForID.data) {
            return reportsForID.data.map(makeDiscussionRevision);
        }
        return null;
    }, [post, reportsForID]);

    useEffect(() => {
        post.data && _setActiveRevision(post.data);
    }, [post.data, reportsForID.data]);

    return (
        <PostRevisionContext.Provider
            value={{
                revisions,
                activeRevision,
                setActiveRevision,
                mostRecentRevision: post.data,
                reports: reportsForID.data,
                activeReport,
            }}
        >
            {children}
        </PostRevisionContext.Provider>
    );
}
