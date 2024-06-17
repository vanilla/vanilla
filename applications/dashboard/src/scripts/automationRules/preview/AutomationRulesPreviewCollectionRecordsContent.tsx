/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getCurrentLocale, t } from "@vanilla/i18n";
import { useEffect, useMemo, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import Translate from "@library/content/Translate";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { DISCUSSIONS_MAX_PAGE_COUNT } from "@library/features/discussions/discussionHooks";
import { MetaItem } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import { ErrorIcon } from "@library/icons/common";
import Message from "@library/messages/Message";
import { AutomationRulesPreviewContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewContent";
import { IGetCollectionResourcesParams, useCollectionContents } from "@library/featuredCollections/collectionsHooks";

interface IProps extends Omit<React.ComponentProps<typeof AutomationRulesPreviewContent>, "formValues"> {
    query: IGetCollectionResourcesParams;
}

export function AutomationRulesPreviewCollectionRecordsContent(props: IProps) {
    const classes = automationRulesClasses();
    const [query, setQuery] = useState<IGetCollectionResourcesParams>(props.query);

    const {
        resources: collectionResources,
        isLoading,
        error,
        totalCount,
        currentPage,
    } = useCollectionContents(query, getCurrentLocale());

    const hasData = collectionResources && Boolean(collectionResources?.length);
    const totalResults = totalCount ? parseInt(totalCount) : 0;

    useEffect(() => {
        if (collectionResources && collectionResources?.length === 0) {
            props.onPreviewContentLoad?.(true);
        }
    }, [collectionResources]);

    const message = useMemo(() => {
        if (totalResults > 0) {
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
        } else if (!collectionResources?.length) {
            return (
                <>
                    {t(
                        "This will not affect any posts right now. It will affect those that meet the criteria in future.",
                    )}
                </>
            );
        }
    }, [collectionResources]);

    return (
        <>
            {error && (
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
                            currentPage: parseInt(currentPage) ?? 1,
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
                {isLoading && (
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
                {hasData &&
                    collectionResources.map((resourceItem, index) => {
                        return (
                            <li
                                key={index}
                                className={cx(
                                    classes.previewDiscussionItem,
                                    classes.verticalGap,
                                    classes.previewDiscussionBorder,
                                )}
                            >
                                <div>{resourceItem.record?.name}</div>
                                <span className={classes.previewDiscussionMeta}>{`${t("Added to collection")} `}</span>
                                <span className={cx(classes.smallFont, classes.bold)}>
                                    {resourceItem.collection?.name}
                                </span>
                                <MetaItem>
                                    <DateTime timestamp={resourceItem.dateAddedToCollection} />
                                </MetaItem>
                            </li>
                        );
                    })}
            </ul>
        </>
    );
}
