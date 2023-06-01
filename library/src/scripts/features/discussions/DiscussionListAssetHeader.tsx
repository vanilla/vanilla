/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useState } from "react";
import { cx } from "@emotion/css";
import { DiscussionListSortOptions, IDiscussion, IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import { DiscussionListSelectAll } from "@library/features/discussions/DiscussionListSelectAll";
import { getMeta } from "@library/utility/appUtils";
import DiscussionListAssetCategoryFilter, {
    DiscussionsCategoryFollowFilter,
} from "@library/features/discussions/DiscussionListAssetCategoryFilter";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { NumberedPager, INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import DiscussionListSort from "@library/features/discussions/DiscussionListSort";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";

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

    return (
        <div className={cx(classes.assetHeader)}>
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
                    />
                )}
                <DiscussionListSort
                    currentSort={apiParams.sort ?? DiscussionListSortOptions.RECENTLY_COMMENTED}
                    selectSort={(sort: DiscussionListSortOptions) => updateApiParams({ sort })}
                />
            </div>
            <div>{props.paginationProps && <NumberedPager {...props.paginationProps} rangeOnly />}</div>
        </div>
    );
}
