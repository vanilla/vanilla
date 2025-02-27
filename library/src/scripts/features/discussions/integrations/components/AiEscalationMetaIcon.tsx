/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MetaIcon } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import AttachmentLayoutClasses from "@library/features/discussions/integrations/components/AttachmentLayout.classes";
import type { ContentItemAttachmentMetaItem } from "@vanilla/addon-vanilla/contentItem/ContentItemAttachments.service";

const AiEscalationMetaIcon: ContentItemAttachmentMetaItem["component"] = function (props) {
    const classes = AttachmentLayoutClasses();

    return (
        <MetaIcon icon="ai-sparkle-monocolor" size="compact" className={classes.aiEscalationMetaIcon}>
            {t("AI Escalation")}
        </MetaIcon>
    );
};

export default AiEscalationMetaIcon;
