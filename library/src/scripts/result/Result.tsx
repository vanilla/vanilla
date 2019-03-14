/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "../dom/appUtils";
import Paragraph from "../layout/Paragraph";
import AttachmentIcons from "../content/attachments/AttachmentIcons";
import { IKbCategoryFragment } from "../../../../plugins/knowledge/src/scripts/@types/api/kbCategory";
import { ICrumb } from "../navigation/Breadcrumbs";
import SmartLink from "../routing/links/SmartLink";
import TruncatedText from "../content/TruncatedText";
import { IAttachmentIcon } from "../content/attachments/AttachmentIcon";
import { searchResultClasses, searchResultsClasses } from "../features/search/searchResultsStyles";

export interface IResult {
    name: string;
    className?: string;
    meta: React.ReactNode;
    url: string;
    excerpt: string;
    image?: string;
    headingLevel?: 2 | 3;
    attachments?: IAttachmentIcon[];
    location: ICrumb[] | string[];
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default class Result extends React.Component<IResult> {
    public static defaultProps = {
        headingLevel: 3,
    };

    public render() {
        const hasAttachments = this.props.attachments && this.props.attachments.length > 0;
        const showImage = this.props.image && !hasAttachments;
        const hasMedia = hasAttachments || showImage;
        const classesSearchResults = searchResultsClasses();
        const classes = searchResultClasses();
        const image = showImage ? (
            <img
                src={this.props.image}
                className={classNames("searchResult-image", classes.image)}
                alt={t("Thumbnail for: " + this.props.name)}
                aria-hidden={true}
            />
        ) : null;

        let attachmentOutput;
        if (hasAttachments && this.props.attachments) {
            attachmentOutput = <AttachmentIcons attachments={this.props.attachments} />;
        }
        const HeadingTag = `h${this.props.headingLevel}`;

        const media = hasMedia ? (
            <div className={classNames("searchResult-media", classes.mediaElement, { hasImage: showImage })}>
                {showImage && image}
                {attachmentOutput}
            </div>
        ) : null;

        return (
            <li className={classNames("searchResults-item", classesSearchResults.item, this.props.className)}>
                <article className={classNames("searchResults-result", classesSearchResults.result)}>
                    <SmartLink to={this.props.url} className={classNames("searchResult", classes.root)}>
                        <div className={classNames("searchResult-main", classes.main, { hasMedia: !!media })}>
                            <HeadingTag className={classNames("searchResult-title", classes.title)}>
                                {this.props.name}
                            </HeadingTag>
                            {this.props.meta && (
                                <div className={classNames("searchResult-metas", "metas", classes.metas)}>
                                    {this.props.meta}
                                </div>
                            )}
                            {!!this.props.excerpt && (
                                <Paragraph className={classNames("searchResult-excerpt", classes.excerpt)}>
                                    <TruncatedText>{this.props.excerpt}</TruncatedText>
                                </Paragraph>
                            )}
                        </div>
                        {media}
                    </SmartLink>
                </article>
            </li>
        );
    }
}
