/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IEscalation } from "@dashboard/moderation/CommunityManagementTypes";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { deletedUserFragment } from "@library/features/users/constants/userFragment";
import { MetaItem, MetaIcon, MetaLink } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import ProfileLink from "@library/navigation/ProfileLink";

interface IProps {
    escalation: IEscalation;
}

export function EscalationMetas(props: IProps) {
    const { escalation } = props;
    return (
        <>
            <MetaItem>
                <Translate
                    source="Escalated by <0/>"
                    c0={
                        <ProfileLink
                            userFragment={escalation.insertUser ?? deletedUserFragment()}
                            className={metasClasses().metaLink}
                        />
                    }
                />
            </MetaItem>
            <MetaIcon icon="meta-time" aria-label="Date Updated">
                <DateTime timestamp={escalation.dateInserted} />
            </MetaIcon>
            <MetaIcon icon="meta-comment" aria-label="Count Comments">
                {escalation.countComments}
            </MetaIcon>
            <MetaLink to={escalation.placeRecordUrl}>{escalation.placeRecordName}</MetaLink>
        </>
    );
}
