/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { AdminNav } from "@dashboard/components/navigation/AdminNav";
import { t } from "@vanilla/i18n";

export function StaffNav(props: Omit<React.ComponentProps<typeof AdminNav>, "title" | "sectionID">) {
    return <AdminNav {...props} sectionID="vanillastaff" title={t("Vanilla Staff")} />;
}
