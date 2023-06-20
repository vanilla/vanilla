/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DiscussionListSortOptions, IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import DiscussionListAssetCategoryFilter from "@library/features/discussions/DiscussionListAssetCategoryFilter";
import { DiscussionListSelectAll } from "@library/features/discussions/DiscussionListSelectAll";
import DiscussionListSort from "@library/features/discussions/DiscussionListSort";
import { DiscussionListFilter } from "@library/features/discussions/filters/DiscussionListFilter";
import { INumberedPagerProps, NumberedPager } from "@library/features/numberedPager/NumberedPager";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { getMeta } from "@library/utility/appUtils";
import React, { useRef } from "react";
import { useMeasure } from "@vanilla/react-utils";

interface IProps {
    discussionIDs?: Array<IDiscussion["discussionID"]>;
    noCheckboxes?: boolean;
    categoryFollowEnabled?: boolean;
    paginationProps?: INumberedPagerProps;
    apiParams: IGetDiscussionListParams;
    updateApiParams: (newParams: Partial<IGetDiscussionListParams>) => void;
}

/**
 * Header component for discussion list asset.
 */
export function DiscussionListAssetHeader(props: IProps) {
    const { updateApiParams, apiParams } = props;
    const classes = discussionListClasses();
    const { hasPermission } = usePermissionsContext();
    const canUseCheckboxes =
        !props.noCheckboxes && getMeta("ui.useAdminCheckboxes", false) && hasPermission("discussions.manage");
    const selfRef = useRef<HTMLDivElement>(null);
    const measure = useMeasure(selfRef);
    const isMobile = measure.width < 600;

    return (
        <div className={cx(classes.assetHeader)} ref={selfRef}>
            <div>
                {canUseCheckboxes && (
                    <DiscussionListSelectAll
                        className={classes.selectAllCheckBox}
                        discussionIDs={props.discussionIDs ?? []}
                    />
                )}
                {props.categoryFollowEnabled && (
                    <DiscussionListAssetCategoryFilter
                        filter={apiParams.followed ? "followed" : "all"}
                        onFilterChange={(followed: boolean) => updateApiParams({ followed })}
                        isMobile={isMobile}
                    />
                )}
                <DiscussionListSort
                    currentSort={apiParams.sort ?? DiscussionListSortOptions.RECENTLY_COMMENTED}
                    selectSort={(sort: DiscussionListSortOptions) => updateApiParams({ sort })}
                    isMobile={isMobile}
                />
                <DiscussionListFilter apiParams={apiParams} updateApiParams={updateApiParams} />
            </div>
            <div>{props.paginationProps && <NumberedPager {...props.paginationProps} rangeOnly />}</div>
        </div>
    );
}
