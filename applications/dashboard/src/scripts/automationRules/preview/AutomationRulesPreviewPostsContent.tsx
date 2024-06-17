/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useMemo, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import Translate from "@library/content/Translate";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { DISCUSSIONS_MAX_PAGE_COUNT, useDiscussionList } from "@library/features/discussions/discussionHooks";
import { LoadStatus } from "@library/@types/api/core";
import { MetaItem } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import { ErrorIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { AutomationRulesPreviewContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewContent";

interface IProps extends Omit<React.ComponentProps<typeof AutomationRulesPreviewContent>, "formValues"> {
    query: IGetDiscussionListParams;
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

    const message = useMemo(() => {
        if (totalResults) {
            return (
                <>
                    <div className={classes.bold}>
                        <Translate
                            source={"Posts Matching Criteria Now: <0 />"}
                            c0={
                                totalResults >= DISCUSSIONS_MAX_PAGE_COUNT
                                    ? `${humanReadableNumber(totalResults)}+`
                                    : totalResults
                            }
                        />
                    </div>
                    <div>
                        {props.fromStatusToggle
                            ? t(
                                  "The action will apply to them when the rule is enabled. In future, other posts who meet the trigger criteria will have the action applied to them as well.",
                              )
                            : t("The action will be applied to only them if you proceed.")}
                    </div>
                    <div className={classes.italic}>
                        {t("Note: Actions will not affect posts that already have the associated action applied.")}
                    </div>
                </>
            );
        } else if (status === LoadStatus.SUCCESS && !discussionsData?.discussionList.length) {
            return (
                <>
                    {t(
                        "This will not affect any posts right now. It will affect those that meet the criteria in future.",
                    )}
                </>
            );
        }
    }, [discussionsData]);

    return (
        <>
            {status === LoadStatus.ERROR && (
                <div className={classes.padded()}>
                    <Message
                        type="error"
                        stringContents={t(
                            "Failed to load the preview data. Please check your trigger and action values.",
                        )}
                        icon={<ErrorIcon />}
                    />
                </div>
            )}
            <div>{message}</div>
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
                        isMobile={false}
                    />
                </div>
            )}
            <ul>
                {status === LoadStatus.LOADING && (
                    <div className={classes.padded(true)} style={{ marginTop: 16 }}>
                        {Array.from({ length: 12 }, (_, index) => (
                            <div key={index} className={classes.flexContainer()} style={{ marginBottom: 16 }}>
                                <LoadingRectangle
                                    style={{ width: 25, height: 25, marginRight: 10, borderRadius: "50%" }}
                                />
                                <LoadingRectangle style={{ width: "95%", height: 25 }} />
                            </div>
                        ))}
                    </div>
                )}
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
        </>
    );
}
