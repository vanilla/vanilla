/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Translate from "@library/content/Translate";
import { MetaIcon, MetaItem, MetaLink } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import { t } from "@vanilla/i18n";
import React from "react";
import DateTime from "@library/content/DateTime";
import { metasClasses } from "@library/metas/Metas.styles";
import { ICategoryItem, ICategoryItemOptions } from "@library/categoriesWidget/CategoryItem";
import { IconType } from "@vanilla/icons";

export interface ICategoryItemMetaProps {
    category: ICategoryItem;
    inTile?: boolean;
    categoryOptions?: ICategoryItemOptions;
    className?: string;
}

export function CategoryItemMeta(props: ICategoryItemMetaProps) {
    const { category, categoryOptions } = props;

    const countIcon = (recordName: string) => {
        switch (recordName) {
            case "discussion":
                return "search-discussion";
            case "comment":
                return "meta-comment";
            case "post":
                return "search-post-count";
            case "follower":
                return "me-notifications-small";
        }
    };

    const display = categoryOptions?.metas?.display;
    const asIcons = categoryOptions?.metas?.asIcons;
    const includeSubcategoriesCount = categoryOptions?.metas?.includeSubcategoriesCount;

    const countsMeta =
        category.counts &&
        category.counts.map((countType, i) => {
            const countName = countType.labelCode ? countType.labelCode.slice(0, -1) : null;
            const isVisible = countName && display?.[`${countName}Count`];
            const countNumber = includeSubcategoriesCount?.includes(countType.labelCode)
                ? countType?.countAll
                : countType?.count;

            if (!isVisible || countNumber === 0) {
                return <React.Fragment key={i}></React.Fragment>;
            }

            return asIcons ? (
                <MetaIcon key={i} icon={countIcon(countName) as IconType} aria-label={t(countType.labelCode)}>
                    {countNumber}
                </MetaIcon>
            ) : (
                <MetaItem key={i}>
                    <Translate source={`<0/> ${countType.labelCode}`} c0={countNumber} />
                </MetaItem>
            );
        });

    const mostRecentMeta = category.lastPost && (display?.lastPostName || display?.lastPostAuthor) && (
        <MetaItem>
            {`${t("Most recent:")} `}
            {display?.lastPostName && category?.lastPost?.url && (
                <MetaLink to={category?.lastPost?.url}>{category.lastPost.name}</MetaLink>
            )}
            {display?.lastPostAuthor && category.lastPost.insertUser && (
                <>
                    {` ${t("by")} `}
                    <ProfileLink
                        userFragment={{
                            userID: category.lastPost.insertUser.userID,
                            name: category.lastPost.insertUser.name,
                        }}
                        className={metasClasses().metaLink}
                    />
                </>
            )}
        </MetaItem>
    );

    const lastPostDateMeta =
        display?.lastPostDate &&
        category.lastPost?.dateInserted &&
        (asIcons ? (
            <MetaIcon icon="meta-time" aria-label={t("Last comment")}>
                <DateTime timestamp={category.lastPost?.dateInserted} />
            </MetaIcon>
        ) : (
            <MetaItem>
                <DateTime timestamp={category.lastPost?.dateInserted} />
            </MetaItem>
        ));

    const categoryChildren = category.children as ICategoryItem[];
    const childrenToRender = categoryChildren
        ? categoryChildren.filter((category) => category.displayAs !== "heading")
        : [];
    const subcategoriesMetaContent = childrenToRender.map((category, i) => {
        return (
            <React.Fragment key={i}>
                <MetaLink to={category.to}>{category.name}</MetaLink>
                {i < childrenToRender.length - 1 && ", "}
            </React.Fragment>
        );
    });

    const subcategoriesMeta =
        display?.subcategories && childrenToRender.length ? (
            asIcons ? (
                <MetaIcon icon="search-subcategories" aria-label={t("Subcategories")}>
                    {subcategoriesMetaContent}
                </MetaIcon>
            ) : (
                <MetaItem>
                    {`${t("Subcategories")}:`} {subcategoriesMetaContent}
                </MetaItem>
            )
        ) : (
            <></>
        );

    return (
        <div className={props.className}>
            {countsMeta}
            {mostRecentMeta}
            {lastPostDateMeta}
            {subcategoriesMeta}
        </div>
    );
}
