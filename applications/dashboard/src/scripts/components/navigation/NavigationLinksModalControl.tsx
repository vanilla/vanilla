/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Button from "@library/forms/Button";
import { t } from "@vanilla/i18n";
import { createLoadableComponent } from "@vanilla/react-utils";

export const NavigationLinksModalControl = createLoadableComponent({
    loadFunction: () => import("./NavigationLinksModalControl.loadable"),
    fallback: () => <Button disabled={true}>{t("Edit Links")}</Button>,
});
