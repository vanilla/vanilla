/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { cx } from "@emotion/css";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { DiscussionListSelectAll } from "@library/features/discussions/DiscussionListSelectAll";
import { hasPermission } from "@library/features/users/Permission";
import { getMeta } from "@library/utility/appUtils";
import DiscussionListAssetCategoryFilter, {
    DiscussionsCategoryFollowFilter,
} from "@library/features/discussions/DiscussionListAssetCategoryFilter";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { NumberedPager, INumberedPagerProps } from "@library/features/numberedPager/NumberedPager";

interface IProps {
    discussionIDs?: Array<IDiscussion["discussionID"]>;
    noCheckboxes?: boolean;
    categoryFollowEnabled?: boolean;
    onCategoryFollowFilterChange: (newFilter: boolean) => void;
    categoryFollowFilter: DiscussionsCategoryFollowFilter;
    paginationProps?: INumberedPagerProps;
}

/**
 * Header component for discussion list asset.
 */
export function DiscussionListAssetHeader(props: IProps) {
    const classes = discussionListClasses();
    const canUseCheckboxes =
        !props.noCheckboxes && getMeta("ui.useAdminCheckboxes", false) && hasPermission("discussions.manage");

    return (
        <div className={cx(classes.assetHeader, props.categoryFollowEnabled ? "alignJustified" : "alignRight")}>
            <div>
                {canUseCheckboxes && (
                    <DiscussionListSelectAll
                        className={classes.selectAllCheckBox}
                        discussionIDs={props.discussionIDs ?? []}
                    />
                )}
                {props.categoryFollowEnabled && (
                    <DiscussionListAssetCategoryFilter
                        filter={props.categoryFollowFilter}
                        onFilterChange={props.onCategoryFollowFilterChange}
                    />
                )}
            </div>
            <div>{props.paginationProps && <NumberedPager {...props.paginationProps} rangeOnly />}</div>
        </div>
    );
}
