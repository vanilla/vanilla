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
import {ICrumb} from "@library/navigation/Breadcrumbs";
import {TypeQuestionIcon} from "@library/icons/searchIcons";


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
    icon?: JSX.Element;
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default function Result(props:IResult) {
    const {
        name,
        className,
        meta,
        url,
        excerpt,
        image,
        headingLevel = 2,
        attachments,
        afterExcerpt,
        icon,
    } = props;

    const hasAttachments = attachments && attachments.length > 0;
    const showImage = image && !hasAttachments;
    const hasMedia = hasAttachments || showImage;
    const classesSearchResults = searchResultsClasses();
    const classes = searchResultClasses();
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

    const media = hasMedia ? (
        <div className={classNames("searchResult-media", classes.mediaElement, { hasImage: showImage })}>
            {showImage && imageComponent}
            {attachmentOutput}
        </div>
    ) : null;

    return (
        <li className={classNames(classesSearchResults.item, className)}>
            <article className={classNames(classesSearchResults.result)}>
                {icon && <div className={classes.iconWrap}>{icon}</div>}
                <div className={classNames(classes.root, {"hasIcon": !!icon})}>
                    <div className={classNames(classes.main, { hasMedia: !!media })}>
                        <SmartLink to={url} tabIndex={0} className={classes.link}>
                            <HeadingTag className={classes.title}>
                                {name}
                            </HeadingTag>
                        </SmartLink>
                        {meta && <div className={classes.metas}>{meta}</div>}
                        {!!excerpt && (
                            <Paragraph className={classes.excerpt}>
                                <>
                                    <TruncatedText maxCharCount={160}>
                                        {excerpt}
                                    </TruncatedText>
                                    {afterExcerpt}
                                </>
                            </Paragraph>
                        )}
                    </div>
                    {media}
                </div>
            </article>
        </li>
    );

}
