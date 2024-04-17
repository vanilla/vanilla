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

    return (
        <div className={classes.root}>
            <div className={classes.logoSection}>{!!icon && <div className={classes.logoWrapper}>{icon}</div>}</div>

            <div className={classes.textSection}>
                <div className={classes.header}>
                    <div className={classes.titleAndNoticeAndMetasWrapper}>
                        {!!title && <h5 className={classes.title}>{title}</h5>}
                        <Metas className={classes.inlineMetas}>
                            {!!notice && (
                                <MetaItem>
                                    <Notice className={classes.notice}>{notice}</Notice>
                                </MetaItem>
                            )}
                        </Metas>

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

                    {!!url && !!id && !!idLabel && (
                        <div>
                            <div className={classes.externalLinkWrapper}>
                                <div className={classes.detailItem}>
                                    <div className={classes.detailLabel}>{idLabel}</div>
                                    <div className={classes.detailValue}>
                                        <SmartLink to={url} className={classes.externalLink}>
                                            <strong>{id}</strong>
                                            <Icon
                                                className={classes.externalIcon}
                                                icon="meta-external"
                                                size="default"
                                            />
                                        </SmartLink>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
                <div className={classes.details}>
                    {(metadata ?? [])?.map((detail, index) => {
                        let valueContents: React.ReactNode = detail.value;

                        if (detail.format === "date-time") {
                            valueContents = (
                                <DateTime
                                    timestamp={detail.value.toString()}
                                    mode="fixed"
                                    type={DateFormats.EXTENDED}
                                />
                            );
                        }

                        if (detail.labelCode)
                            if (detail.url) {
                                valueContents = (
                                    <SmartLink to={detail.url} className={classes.detailLink}>
                                        {valueContents}
                                        <Icon className={classes.externalIcon} icon="meta-external" size="default" />
                                    </SmartLink>
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
