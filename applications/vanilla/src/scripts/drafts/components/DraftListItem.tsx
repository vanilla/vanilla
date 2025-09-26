/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { draftsClasses } from "@vanilla/addon-vanilla/drafts/Drafts.classes";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { ListItem } from "@library/lists/ListItem";
import { MetaItem } from "@library/metas/Metas";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { Icon } from "@vanilla/icons";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { cx } from "@emotion/css";
import { DraftListItemOptionsMenu } from "@vanilla/addon-vanilla/drafts/components/DraftListItemOptionsMenu";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps {
    draft: IDraft;
    isSchedule: boolean;
    lastItemInDateGroup?: boolean;
}

export function DraftListItem(props: IProps) {
    const { draft, lastItemInDateGroup, isSchedule } = props;
    const classes = draftsClasses();

    const description = (
        <div>
            {draft.excerpt && <div>{draft.excerpt}</div>}
            {draft.draftStatus === "error" && (
                <Message
                    type="error"
                    icon={<ErrorIcon />}
                    linkText={t("Edit")}
                    linkURL={draft.editUrl}
                    error={{
                        message: "Unable to post. Edit to complete missing mandatory fields.",
                    }}
                />
            )}
        </div>
    );

    return (
        <>
            <ListItem
                className={cx({ [classes.draftListLastItemInGroup]: lastItemInDateGroup })}
                icon={
                    <Icon
                        icon={
                            draft.recordType === "article"
                                ? "meta-article"
                                : draft.recordType === "event"
                                ? "meta-events"
                                : draft.recordType === "comment"
                                ? "meta-comments"
                                : "meta-discussions"
                        }
                    />
                }
                name={
                    <SmartLink to={draft.editUrl} className={classes.draftListItemTitle}>
                        {draft.name && draft.name !== "" ? draft.name : t("(Untitled)")}
                    </SmartLink>
                }
                description={description}
                metas={
                    <>
                        {draft.breadCrumbs && (
                            <MetaItem className={classes.draftItemBreadCrumbs}>
                                <Breadcrumbs>{draft.breadCrumbs}</Breadcrumbs>
                            </MetaItem>
                        )}
                        {draft.dateUpdated && (
                            <MetaItem>
                                <Translate
                                    source="Last Updated: <0/>"
                                    c0={<DateTime timestamp={draft.dateUpdated} />}
                                />
                            </MetaItem>
                        )}
                    </>
                }
                actions={<DraftListItemOptionsMenu isSchedule={isSchedule} draft={draft} />}
            />
        </>
    );
}
