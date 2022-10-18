/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import AttachmentIcons from "@library/content/attachments/AttachmentIcons";
import { t } from "@library/utility/appUtils";
import TruncatedText from "@library/content/TruncatedText";
import { searchResultClasses } from "@library/features/search/searchResultsStyles";
import { IAttachmentIcon } from "@library/content/attachments/AttachmentIcon";
import { ICrumb } from "@library/navigation/Breadcrumbs";
import { ListItem } from "@library/lists/ListItem";
import { ListItemMedia } from "@library/lists/ListItemMedia";
export interface IResult {
    name: string;
    className?: string;
    meta?: React.ReactNode;
    url: string;
    excerpt?: string;
    highlight?: string;
    image?: string;
    imageSet?: string;
    attachments?: IAttachmentIcon[];
    icon?: React.ReactNode;
    rel?: string;
}

export default function Result(props: IResult) {
    const { name, className, meta, url, excerpt, image, imageSet, attachments, icon, highlight } = props;
    const hasAttachments = !!(attachments && attachments.length > 0);
    const showImage = (!!image || !!imageSet) && !hasAttachments;
    const hasMedia = hasAttachments || showImage;
    const classes = searchResultClasses();

    const media = hasMedia ? (
        showImage ? (
            <ListItemMedia src={image!} srcSet={imageSet} alt={t("Thumbnail for: " + name)} />
        ) : hasAttachments ? (
            <AttachmentIcons attachments={attachments!} />
        ) : null
    ) : null;

    const highlightElement = highlight ? (
        <TruncatedText className={classes.highlight} maxCharCount={160} lines={2}>
            <span dangerouslySetInnerHTML={{ __html: highlight }}></span>
        </TruncatedText>
    ) : null;

    const excerptElement = excerpt && excerpt?.length > 0 ? excerpt : null;

    return (
        <ListItem
            className={className}
            url={url}
            name={name}
            icon={icon}
            iconWrapperClass={classes.iconWrap}
            description={highlightElement ?? excerptElement}
            truncateDescription={!highlightElement}
            descriptionMaxCharCount={160}
            metas={meta}
            mediaItem={media}
        />
    );
}
