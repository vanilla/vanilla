/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ICommunityManagementRecord } from "@dashboard/moderation/CommunityManagementTypes";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { MetaIcon, MetaItem, MetaProfile } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";

interface IProps {
    record: ICommunityManagementRecord;
}

export function ReportRecordMeta(props: IProps) {
    const { record } = props;
    return (
        <>
            <MetaItem flex>
                <Translate
                    source="Posted by <0/> in <1/>"
                    c0={<MetaProfile user={record.recordUser ?? deletedUserFragment()} />}
                    c1={
                        <SmartLink to={`${record.placeRecordUrl}`} asMeta>
                            {record.placeRecordName}
                        </SmartLink>
                    }
                />
            </MetaItem>

            {record.recordDateInserted && (
                <MetaItem>
                    <MetaIcon icon="meta-time" />
                    <DateTime timestamp={record.recordDateInserted}></DateTime>
                </MetaItem>
            )}

            {record.recordWasEdited && (
                <MetaItem>
                    <Tag preset={TagPreset.COLORED}>{t("Edited")}</Tag>
                </MetaItem>
            )}
            {record.recordIsLive && (
                <MetaItem>
                    <Tag preset={TagPreset.COLORED}>{t("Visible")}</Tag>
                </MetaItem>
            )}
        </>
    );
}
