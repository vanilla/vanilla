/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { AdminNav } from "@dashboard/components/navigation/AdminNav";
import { t } from "@vanilla/i18n";

export function ModerationNav(props: Omit<React.ComponentProps<typeof AdminNav>, "title" | "sectionID">) {
    return <AdminNav {...props} sectionID="moderation" title={t("Moderation")} />;
}
