/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { followedContentClasses } from "@library/followedContent/FollowedContent.classes";
import { t } from "@vanilla/i18n";
import { SortAndPaginationInfo } from "@library/search/SortAndPaginationInfo";

export enum FollowedContentSortOption {
    RECENTLY_FOLLOWED = "-dateFollowed",
    OLDEST_FOLLOWED = "dateFollowed",
    ALPHABETICAL = "name",
}

interface IProps {
    sortBy?: string;
    setSortBy?: (value) => void;
    pager?: React.ReactNode;
}

export function FollowedContentHeader(props: IProps) {
    const { sortBy, setSortBy, pager } = props;
    const hasSort = sortBy && setSortBy;
    const classes = followedContentClasses(!hasSort);

    return (
        <div className={classes.sortByAndPager}>
            {hasSort && (
                <SortAndPaginationInfo
                    sortValue={sortBy}
                    onSortChange={(newSort) => setSortBy(newSort)}
                    sortOptions={[
                        {
                            value: FollowedContentSortOption.RECENTLY_FOLLOWED,
                            name: t("Most Recently Followed"),
                        },
                        {
                            value: FollowedContentSortOption.OLDEST_FOLLOWED,
                            name: t("Oldest Followed"),
                        },
                        {
                            value: FollowedContentSortOption.ALPHABETICAL,
                            name: t("Alphabetical"),
                        },
                    ]}
                />
            )}
            {pager}
        </div>
    );
}
