/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { cx } from "@emotion/css";
import { PermissionMode, type IPermissionOptions } from "@library/features/users/Permission";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { MetaButton } from "@library/metas/Metas";
import type { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";

interface IProps {
    scrapeUrl: string;
    categoryID: ICategory["categoryID"];
    isClosed: boolean;
}

export function ContentItemQuoteButton(props: IProps) {
    const { scrapeUrl, isClosed, categoryID } = props;

    const { hasPermission } = usePermissionsContext();

    const permissionOptions: IPermissionOptions = {
        mode: PermissionMode.RESOURCE_IF_JUNCTION,
        resourceType: "category",
        resourceID: categoryID,
    };

    let canComment = hasPermission("comments.add", permissionOptions);

    if (isClosed) {
        const canClose = hasPermission("discussions.close", permissionOptions);
        canComment = canClose;
    }

    if (canComment) {
        return (
            <MetaButton
                icon={"quote-content"}
                buttonClassName={cx("js-quoteButton")}
                title={t("Quote")}
                aria-label={t("Quote")}
                data-scrape-url={scrapeUrl} //An event listener is attached to this attribute in the vanilla-editor.
                onClick={() => null}
            />
        );
    }

    return null;
}
