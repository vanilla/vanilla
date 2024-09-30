/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { followedContentClasses } from "@library/followedContent/FollowedContent.classes";
import Heading from "@library/layout/Heading";
import { t } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import { useFollowedContent } from "@library/followedContent/FollowedContentContext";
import { DiscussionListLoader } from "@library/features/discussions/DiscussionListLoader";
import ResultList from "@library/result/ResultList";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem, MetaLink } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import DateTime from "@library/content/DateTime";
import CategoryFollowDropDown from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { getMeta } from "@library/utility/appUtils";
import { FollowedContentHeader, FollowedContentSortOption } from "@library/followedContent/FollowedContentHeader";
import { useMemo, useState } from "react";
import { useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { MAX_PAGE_COUNT } from "@library/navigation/SimplePagerModel";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";

interface IFollowedContentRow extends ICategory {
    userID: number;
}

function FollowedCategoryRow(props: IFollowedContentRow) {
    const { userID, url, iconUrl, dateFollowed, countDiscussions, name, lastPost, categoryID, preferences } = props;
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
                        {new Date(dateFollowed ?? "") > april18 ? (
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
                        isCompact
                    />
                </div>
            }
        />
    );
}

export function FollowedCategoriesContent(props: { withTitle?: boolean }) {
    const classes = followedContentClasses();
    const { userID } = useFollowedContent();
    const [sortBy, setSortBy] = useState(FollowedContentSortOption.RECENTLY_FOLLOWED);
    const [page, setPage] = useState(1);

    const query = useMemo(() => {
        return { followed: true, sort: sortBy, expand: "lastPost,preferences", limit: 30, page: page };
    }, [sortBy, page]);

    const { data, error, isLoading } = useCategoryList(query, true);
    const followedCategories = data?.result;
    const pagination = data?.pagination;

    return (
        <>
            {props.withTitle && (
                <Heading depth={2} className={classes.subtitle}>
                    {t("Manage Categories")}
                </Heading>
            )}
            <FollowedContentHeader
                sortBy={sortBy}
                setSortBy={setSortBy}
                pager={
                    data?.pagination && (
                        <NumberedPager
                            totalResults={pagination?.total}
                            currentPage={pagination?.currentPage ?? 1}
                            pageLimit={30}
                            hasMorePages={pagination?.total ? pagination?.total >= MAX_PAGE_COUNT : false}
                            showNextButton={false}
                            onChange={(page: number) => setPage(page)}
                            isMobile={false}
                            className={classes.pager}
                        />
                    )
                }
            />
            {error && <Message type="error" stringContents={error.message} icon={<ErrorIcon />} />}
            {isLoading && <DiscussionListLoader count={3} actionIcon="me-notifications" />}
            {followedCategories &&
                (followedCategories.length ? (
                    <List>
                        {followedCategories.map((category) => (
                            <FollowedCategoryRow key={category.categoryID} userID={userID} {...category} />
                        ))}
                    </List>
                ) : (
                    <ResultList results={[]} emptyMessage={t("No categories followed")} />
                ))}
        </>
    );
}
