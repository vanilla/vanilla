/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getCurrentLocale, t } from "@vanilla/i18n";
import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { DISCUSSIONS_MAX_PAGE_COUNT } from "@library/features/discussions/discussionHooks";
import { MetaItem } from "@library/metas/Metas";
import { cx } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import { IGetCollectionResourcesParams, useCollectionContents } from "@library/featuredCollections/collectionsHooks";
import { loadingPlaceholder } from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesPreviewContentHeader } from "@dashboard/automationRules/preview/AutomationRulesPreviewContentHeader";

interface IProps {
    query: IGetCollectionResourcesParams;
    fromStatusToggle?: boolean;
    onPreviewContentLoad?: (emptyResult: boolean) => void;
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

    return (
        <>
            <AutomationRulesPreviewContentHeader
                contentType="Posts"
                totalResults={totalResults}
                emptyResults={Boolean(!collectionResources?.length)}
                fromStatusToggle={props.fromStatusToggle}
                hasError={Boolean(error)}
            />
            {totalResults > 0 && currentPage && (
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
                {isLoading && loadingPlaceholder("preview")}
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
