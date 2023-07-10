/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useMemo } from "react";
import { followedContentClasses } from "@library/followedContent/FollowedContent.classes";
import Heading from "@library/layout/Heading";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import {
    FollowedContentProvider,
    useFollowedContent,
    IFollowedContent,
} from "@library/followedContent/FollowedContentContext";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import { DiscussionListLoader } from "@library/features/discussions/DiscussionListLoader";
import ResultList from "@library/result/ResultList";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem, MetaLink } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import DateTime from "@library/content/DateTime";
import { CategoryFollowDropDown } from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { SortAndPaginationInfo } from "@library/search/SortAndPaginationInfo";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { CategorySortOption } from "@dashboard/@types/api/category";
import { getMeta } from "@library/utility/appUtils";

interface IFollowedContentRow extends IFollowedContent {
    userID: number;
}

function FollowedCategoryRow(props: IFollowedContentRow) {
    const { userID, url, iconUrl, dateFollowed, countDiscussions, name, lastPost, categoryID, preferences } = props;
    const emailEnabled = getMeta("emails.enabled");
    const emailDigestEnabled = getMeta("emails.digest", false);
    const classes = followedContentClasses();
    const april18 = new Date(2023, 3, 18);

    return (
        <ListItem
            url={url}
            icon={
                iconUrl ? (
                    <div className={classes.photoWrap}>
                        <img src={iconUrl} className="CategoryPhoto" height="200" width="200" />
                    </div>
                ) : (
                    <ScreenReaderContent>{t("Expand for more options.")}</ScreenReaderContent>
                )
            }
            iconWrapperClass={classes.iconWrap}
            name={name}
            nameClassName={classes.name}
            metas={
                <>
                    <MetaItem>
                        {new Date(dateFollowed) > april18 ? (
                            <Translate source="Following since <0/>" c0={<DateTime timestamp={dateFollowed} />} />
                        ) : (
                            <Translate source="Followed before <0/>" c0={t("May 2023")} />
                        )}
                    </MetaItem>
                    <MetaItem>
                        <Translate source="<0/> discussions" c0={countDiscussions} />
                    </MetaItem>
                    {lastPost && (
                        <MetaItem>
                            <Translate
                                source="Most recent: <0/> by <1/>."
                                c0={<MetaLink to={lastPost.url}>{lastPost.name}</MetaLink>}
                                c1={
                                    lastPost.insertUser ? (
                                        <ProfileLink
                                            userFragment={{
                                                userID: lastPost.insertUser.userID,
                                                name: lastPost.insertUser.name,
                                            }}
                                            className={metasClasses().metaLink}
                                        />
                                    ) : null
                                }
                            />
                        </MetaItem>
                    )}
                </>
            }
            actions={
                <div style={{ marginTop: 28 }}>
                    <CategoryFollowDropDown
                        userID={userID}
                        categoryID={categoryID}
                        categoryName={name}
                        notificationPreferences={preferences}
                        emailDigestEnabled={emailDigestEnabled}
                        emailEnabled={emailEnabled}
                    />
                </div>
            }
        />
    );
}

export function FollowedContentImpl() {
    const classes = followedContentClasses();
    const { userID, followedCategories, sortBy, setSortBy, error } = useFollowedContent();

    const sortAndPaginationContent = useMemo(() => {
        const sortOptions: ISelectBoxItem[] = [
            {
                value: CategorySortOption.RECENTLY_FOLLOWED,
                name: t("Most Recently Followed"),
            },
            {
                value: CategorySortOption.OLDEST_FOLLOWED,
                name: t("Oldest Followed"),
            },
            {
                value: CategorySortOption.ALPHABETICAL,
                name: t("Alphabetical"),
            },
        ];

        return (
            <div className={classes.sortBy}>
                <SortAndPaginationInfo
                    sortValue={sortBy}
                    onSortChange={(newSort) => setSortBy(newSort)}
                    sortOptions={sortOptions}
                />
            </div>
        );
    }, [sortBy, setSortBy, classes.sortBy]);

    return error ? (
        <ErrorPage error={{ message: error.message }} />
    ) : (
        <section className={classes.section}>
            <Heading depth={1} renderAsDepth={1}>
                {t("Followed Content")}
            </Heading>
            <Heading depth={2} className={classes.subtitle}>
                {t("Manage Followed Categories")}
            </Heading>

            {sortAndPaginationContent}

            {followedCategories ? (
                followedCategories.length ? (
                    <List>
                        {followedCategories.map((category) => (
                            <FollowedCategoryRow key={category.categoryID} userID={userID} {...category} />
                        ))}
                    </List>
                ) : (
                    <ResultList results={[]} emptyMessage={t("No categories followed")} />
                )
            ) : (
                <DiscussionListLoader count={3} actionIcon="me-notifications" />
            )}
        </section>
    );
}

interface IProps {
    userID: number;
}

export function FollowedContent(props: IProps) {
    return (
        <div>
            <FollowedContentProvider userID={props.userID}>
                <ErrorPageBoundary>
                    <FollowedContentImpl />
                </ErrorPageBoundary>
            </FollowedContentProvider>
        </div>
    );
}

export default FollowedContent;
