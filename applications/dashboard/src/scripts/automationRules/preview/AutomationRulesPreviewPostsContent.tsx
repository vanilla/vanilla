/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { DISCUSSIONS_MAX_PAGE_COUNT, useDiscussionList } from "@library/features/discussions/discussionHooks";
import { LoadStatus } from "@library/@types/api/core";
import { MetaItem } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import { loadingPlaceholder } from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesPreviewContentHeader } from "@dashboard/automationRules/preview/AutomationRulesPreviewContentHeader";

interface IProps {
    query: IGetDiscussionListParams;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
    className?: string;
    contentTypeAsDiscussions?: boolean;
}

export function AutomationRulesPreviewPostsContent(props: IProps) {
    const classes = automationRulesClasses();
    const [query, setQuery] = useState<IGetDiscussionListParams>(props.query);
    const [totalResults, setTotalResults] = useState<number>();
    const [currentPage, setCurrentPage] = useState<number>();

    const { data: discussionsData, status } = useDiscussionList(query);

    const hasDiscussions = Boolean(discussionsData?.discussionList?.length);

    useEffect(() => {
        if (status === LoadStatus.SUCCESS && discussionsData?.discussionList.length === 0) {
            props.onPreviewContentLoad?.(true);
        }
    }, [discussionsData, status]);

    useEffect(() => {
        if (
            discussionsData?.pagination?.currentPage &&
            (!currentPage || currentPage !== discussionsData?.pagination?.currentPage)
        ) {
            setCurrentPage(discussionsData?.pagination?.currentPage);
        }
        if (
            discussionsData?.pagination?.total &&
            (!totalResults || totalResults !== discussionsData?.pagination?.total)
        ) {
            setTotalResults(discussionsData?.pagination?.total);
        }
    }, [discussionsData?.pagination?.currentPage, discussionsData?.pagination?.total]);

    return (
        <div className={props.className}>
            <AutomationRulesPreviewContentHeader
                contentType={props.contentTypeAsDiscussions ? "Discussions" : "Posts"}
                totalResults={totalResults}
                emptyResults={status === LoadStatus.SUCCESS && !discussionsData?.discussionList.length}
                fromStatusToggle={props.fromStatusToggle}
                hasError={status === LoadStatus.ERROR}
            />
            {totalResults && currentPage && (
                <div>
                    <NumberedPager
                        {...{
                            totalResults: totalResults,
                            currentPage: currentPage ?? 1,
                            pageLimit: 30,
                            hasMorePages: totalResults ? totalResults >= DISCUSSIONS_MAX_PAGE_COUNT : false,
                            className: automationRulesClasses().previewPager,
                            showNextButton: false,
                        }}
                        onChange={(page: number) => setQuery({ ...query, page: page })}
                    />
                </div>
            )}
            <ul>
                {status === LoadStatus.LOADING && loadingPlaceholder("preview")}
                {hasDiscussions &&
                    discussionsData?.discussionList.map((discussion, index) => (
                        <li
                            key={index}
                            className={cx(
                                classes.previewDiscussionItem,
                                classes.verticalGap,
                                classes.previewDiscussionBorder,
                            )}
                        >
                            <div>{discussion.name}</div>
                            {discussion.insertUser && (
                                <div>
                                    <span className={classes.previewDiscussionMeta}>{`${t("Started by")} `}</span>
                                    <span className={cx(classes.smallFont, classes.bold)}>
                                        {discussion.insertUser.name}
                                    </span>
                                    <MetaItem>
                                        <DateTime timestamp={discussion.dateInserted} />
                                    </MetaItem>
                                </div>
                            )}
                        </li>
                    ))}
            </ul>
        </div>
    );
}
