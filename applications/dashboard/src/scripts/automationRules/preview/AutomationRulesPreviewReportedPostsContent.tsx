/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import NumberedPager, { INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { MetaItem } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import { AutomationRulesPreviewContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewContent";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useQuery } from "@tanstack/react-query";
import { IReportsData } from "@dashboard/moderation/CommunityManagementTypes";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { loadingPlaceholder } from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesPreviewContentHeader } from "@dashboard/automationRules/preview/AutomationRulesPreviewContentHeader";

export interface IGetReportsForAutomationRulesParams {
    page?: number;
    limit?: number;
    countReports?: number;
    reportReasonID?: string[];
    placeRecordID?: number;
    placeRecordType?: string;
    includeSubcategories?: boolean;
}

interface IProps extends Omit<React.ComponentProps<typeof AutomationRulesPreviewContent>, "formValues"> {
    query: IGetReportsForAutomationRulesParams;
}

export function AutomationRulesPreviewReportedPostsContent(props: IProps) {
    const classes = automationRulesClasses();
    const [query, setQuery] = useState<IGetReportsForAutomationRulesParams>(props.query);

    const { data, isLoading, error } = useQuery<any, IApiError, IReportsData>({
        queryFn: async () => {
            const response = await apiv2.get(`/reports/automation`, {
                params: { ...query },
            });

            const pagination = SimplePagerModel.parseHeaders(response.headers);

            return {
                results: response.data,
                pagination,
            };
        },
        keepPreviousData: true,
        queryKey: ["get_reportedPosts", query],
    });

    const paginationProps: INumberedPagerProps = {
        totalResults: data?.pagination?.total,
        currentPage: data?.pagination?.currentPage,
        pageLimit: data?.pagination?.limit,
        hasMorePages: data?.pagination?.total ? data?.pagination?.total >= 10000 : false,
    };

    const hasData = data && Boolean(data?.results?.length);
    const totalResults = data?.pagination?.total ?? 0;

    useEffect(() => {
        if (data?.results && data?.results.length === 0) {
            props.onPreviewContentLoad?.(true);
        }
    }, [data?.results]);

    return (
        <>
            <AutomationRulesPreviewContentHeader
                contentType="Posts"
                totalResults={totalResults}
                emptyResults={Boolean(!data?.results?.length)}
                fromStatusToggle={props.fromStatusToggle}
                hasError={Boolean(error)}
            />
            {hasData && (
                <div>
                    <NumberedPager
                        {...{
                            ...paginationProps,
                            className: automationRulesClasses().previewPager,
                            showNextButton: false,
                        }}
                        onChange={(page: number) => setQuery({ ...query, page: page })}
                        isMobile={false}
                    />
                </div>
            )}
            <ul>
                {isLoading && loadingPlaceholder("preview")}
                {hasData &&
                    data.results.map((reportItem, index) => {
                        return (
                            <li
                                key={index}
                                className={cx(
                                    classes.previewDiscussionItem,
                                    classes.verticalGap,
                                    classes.previewDiscussionBorder,
                                )}
                            >
                                <div>{reportItem.recordName}</div>
                                <span className={classes.previewDiscussionMeta}>{`${t("Last reported")} `}</span>
                                <MetaItem>
                                    <DateTime timestamp={reportItem.dateInserted} />
                                </MetaItem>
                            </li>
                        );
                    })}
            </ul>
        </>
    );
}
