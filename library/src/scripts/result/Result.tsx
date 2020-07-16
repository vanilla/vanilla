/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import classNames from "classnames";
import AttachmentIcons from "@library/content/attachments/AttachmentIcons";
import { t } from "@library/utility/appUtils";
import TruncatedText from "@library/content/TruncatedText";
import SmartLink from "@library/routing/links/SmartLink";
import { searchResultClasses, searchResultsClasses } from "@library/features/search/searchResultsStyles";
import { IAttachmentIcon } from "@library/content/attachments/AttachmentIcon";
import Paragraph from "@library/layout/Paragraph";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { useLayout } from "@library/layout/LayoutContext";

export interface IResult {
    name: string;
    className?: string;
    meta?: React.ReactNode;
    url: string;
    excerpt?: string;
    image?: string;
    headingLevel?: 2 | 3;
    attachments?: IAttachmentIcon[];
    location: ICrumb[] | string[];
    afterExcerpt?: React.ReactNode; // Likely SearchLink
    icon?: React.ReactNode;
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default function Result(props: IResult) {
    const { name, className, meta, url, excerpt, image, headingLevel = 2, attachments, afterExcerpt, icon } = props;

    const hasAttachments = attachments && attachments.length > 0;
    const showImage = image && !hasAttachments;
    const hasMedia = hasAttachments || showImage;
    const layoutContext = useLayout();
    const classesSearchResults = searchResultsClasses(layoutContext.mediaQueries);
    const classes = searchResultClasses(layoutContext.mediaQueries, !!icon);
    const imageComponent = showImage ? (
        <img
            src={image}
            className={classNames("searchResult-image", classes.image)}
            alt={t("Thumbnail for: " + name)}
            aria-hidden={true}
        />
    ) : null;

    let attachmentOutput;
    if (hasAttachments && attachments) {
        attachmentOutput = <AttachmentIcons attachments={attachments} />;
    }
    const HeadingTag = `h${headingLevel}` as "h1";

    const { isCompact } = useLayout();

    const media = hasMedia ? (
        <div
            className={classNames(classes.mediaElement, {
                hasImage: showImage,
                [classes.compactMediaElement]: isCompact,
            })}
        >
            {showImage && imageComponent}
            {attachmentOutput}
        </div>
    ) : null;

    const excerptElement =
        excerpt && excerpt.length > 0 ? (
            <Paragraph className={classNames(classes.excerpt, { [classes.compactExcerpt]: isCompact })}>
                <>
                    <TruncatedText maxCharCount={160}>{excerpt}</TruncatedText>
                    {afterExcerpt}
                </>
            </Paragraph>
        ) : null;

    return (
        <li className={classNames(classesSearchResults.item, className)}>
            <article className={classesSearchResults.result}>
                <div className={classNames(classes.root)}>
                    <div className={classes.content}>
                        {icon && <div className={classes.iconWrap}>{icon}</div>}
                        <div className={classNames(classes.main, { hasMedia: !!media, hasIcon: !!icon })}>
                            <SmartLink to={url} tabIndex={0} className={classes.link}>
                                <HeadingTag className={classes.title}>{name}</HeadingTag>
                            </SmartLink>
                            {meta && <div className={classes.metas}>{meta}</div>}
                            {isCompact && media}
                            {!isCompact && excerptElement}
                        </div>
                        {!isCompact && media}
                        {isCompact && excerptElement}
                    </div>
                </div>
            </article>
        </li>
    );
}
