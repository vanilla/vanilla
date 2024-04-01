/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import AttachmentLayoutClasses from "@library/features/discussions/integrations/components/AttachmentLayout.classes";
import { MetaItem, Metas } from "@library/metas/Metas";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import Notice from "@library/metas/Notice";
import SmartLink from "@library/routing/links/SmartLink";
import { Icon } from "@vanilla/icons";
import { CollapsableContent } from "@library/content/CollapsableContent";
import { IUserFragment } from "@library/@types/api/users";

interface IAttachmentLayoutProps {
    icon?: React.ReactNode;
    title?: string;
    notice?: string;
    url?: string;
    id?: string;
    idLabel?: string;
    dateUpdated?: string;
    user?: IUserFragment;
    details?: Array<{ label: string; value: string }>;
}

export default function AttachmentLayout(props: IAttachmentLayoutProps) {
    const { icon, title, notice, url, id, idLabel, dateUpdated, user, details } = props;
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
                                        source="Last updated <0/> by <1/>."
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
                                            {id}
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

                <CollapsableContent>
                    <div className={classes.details}>
                        {(details ?? [])?.map((detail, index) => (
                            <div key={index} className={classes.detailItem}>
                                <div className={classes.detailLabel}>{detail.label}</div>
                                <div className={classes.detailValue}>{detail.value}</div>
                            </div>
                        ))}
                    </div>
                </CollapsableContent>
            </div>
        </div>
    );
}
