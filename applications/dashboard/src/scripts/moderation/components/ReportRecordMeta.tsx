/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ICommunityManagementRecord } from "@dashboard/moderation/CommunityManagementTypes";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { MetaItem, MetaIcon } from "@library/metas/Metas";
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
            <MetaItem>
                <Translate
                    source="Posted by <0/> in <1/>"
                    c0={
                        <SmartLink to={`${record.recordUrl}`} className={metasClasses().metaLink}>
                            {record?.recordUser?.name}
                        </SmartLink>
                    }
                    c1={
                        <SmartLink to={`${record.placeRecordUrl}`} className={metasClasses().metaLink}>
                            {record.placeRecordName}
                        </SmartLink>
                    }
                />
            </MetaItem>

            {/* TODO: This is not exposed on the API (or stored in the reports table) just yet, it might be meta we can drop altogether */}
            {/* <MetaItem>
                <MetaIcon icon="meta-time" />
                <DateTime timestamp={record.recordDateInserted}></DateTime>
            </MetaItem> */}

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
