/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import AttachmentLayoutClasses from "@library/features/discussions/integrations/components/AttachmentLayout.classes";
import { MetaItem, Metas } from "@library/metas/Metas";
import DateTime, { DateFormats } from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import Notice from "@library/metas/Notice";
import SmartLink from "@library/routing/links/SmartLink";
import { Icon } from "@vanilla/icons";
import { IUserFragment } from "@library/@types/api/users";
import { t } from "@vanilla/i18n";
import { IAttachment } from "@library/features/discussions/integrations/Integrations.types";
import { cx } from "@emotion/css";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { TokenItem } from "@library/metas/TokenItem";

export interface IAttachmentLayoutProps {
    icon?: React.ReactNode;
    title?: string;
    notice?: string;
    url?: string;
    id?: string;
    idLabel?: string;
    dateUpdated?: string;
    user?: IUserFragment;
    metadata: IAttachment["metadata"];
}

export default function AttachmentLayout(props: IAttachmentLayoutProps) {
    const { icon, title, notice, url, id, idLabel, dateUpdated, user, metadata } = props;
    const classes = AttachmentLayoutClasses();

    const noticeContent = notice ? (
        <MetaItem>
            <Notice className={classes.notice}>{notice}</Notice>
        </MetaItem>
    ) : null;

    const renderExternalLink = !!url && !!id && !!idLabel;
    const renderNoticeByTitle = !!noticeContent && renderExternalLink;
    const positionNoticeInCorner = !!noticeContent && !renderNoticeByTitle;

    return (
        <div className={classes.root}>
            <div className={classes.logoSection}>{!!icon && <div className={classes.logoWrapper}>{icon}</div>}</div>

            <div className={classes.textSection}>
                <div className={classes.header}>
                    <div
                        className={cx(classes.titleAndNoticeAndMetasWrapper, {
                            [classes.positionNoticeInCorner]: positionNoticeInCorner,
                        })}
                    >
                        {!!title && <h5 className={classes.title}>{title}</h5>}
                        {!!noticeContent && <Metas className={classes.inlineMetas}>{noticeContent}</Metas>}

                        <Metas className={classes.metasRow}>
                            {!!dateUpdated && !!user && (
                                <MetaItem>
                                    <Translate
                                        source="Last refreshed <0/> by <1/>."
                                        c0={<DateTime timestamp={dateUpdated} />}
                                        c1={<ProfileLink className={metasClasses().metaLink} userFragment={user} />}
                                    />
                                </MetaItem>
                            )}
                        </Metas>
                    </div>

                    {renderExternalLink && (
                        <div className={classes.externalLinkWrapper}>
                            <div className={classes.detailItem}>
                                <div className={classes.detailLabel}>{idLabel}</div>
                                <div className={classes.detailValue}>
                                    <SmartLink to={url} className={classes.externalLink}>
                                        <strong>{id}</strong>
                                        <Icon className={classes.externalIcon} icon="meta-external-compact" />
                                    </SmartLink>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
                <div className={classes.details}>
                    {(metadata ?? [])?.map((detail, index) => {
                        let valueContents: React.ReactNode = detail.value;

                        switch (detail.format) {
                            case "user":
                                valueContents = (
                                    <ProfileLink
                                        className={classes.userMetaValue}
                                        userFragment={detail.userFragment}
                                        isUserCard
                                    >
                                        <UserPhoto userInfo={detail.userFragment} size={UserPhotoSize.XSMALL} />
                                        <span>{detail.userFragment.name}</span>
                                    </ProfileLink>
                                );
                                break;
                            case "date-time":
                                valueContents = (
                                    <DateTime
                                        timestamp={detail.value.toString()}
                                        mode="fixed"
                                        type={DateFormats.EXTENDED}
                                    />
                                );
                                break;
                            default:
                                valueContents = detail.value;
                        }

                        if (detail.labelCode) {
                            if (detail.url) {
                                valueContents = (
                                    <SmartLink to={detail.url} className={classes.detailLink}>
                                        {valueContents}
                                        <Icon
                                            className={classes.externalIcon}
                                            icon="meta-external-compact"
                                            size="default"
                                        />
                                    </SmartLink>
                                );
                            }
                        }

                        if (Array.isArray(detail.value)) {
                            valueContents = (
                                <div className={classes.tokens}>
                                    {detail.value.map((listItem: string, index) => (
                                        <TokenItem key={`${listItem}${index}`}>{listItem}</TokenItem>
                                    ))}
                                </div>
                            );
                        }

                        return (
                            <div key={index} className={classes.detailItem}>
                                <div className={classes.detailLabel}>{t(detail.labelCode)}</div>
                                <div className={classes.detailValue}>{valueContents}</div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
