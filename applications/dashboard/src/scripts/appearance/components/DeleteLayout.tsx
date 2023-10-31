/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { layoutOverviewPageClasses } from "@dashboard/appearance/pages/LayoutOverviewPage.classes";
import { LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { useDeleteLayout } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutDetails } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { css } from "@emotion/css";
import { useToast } from "@library/features/toaster/ToastContext";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { iconClasses } from "@library/icons/iconStyles";
import ModalConfirm from "@library/modal/ModalConfirm";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { getRelativeUrl } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useState } from "react";
import { useHistory } from "react-router";

export function DeleteLayout(props: { layout: ILayoutDetails }) {
    const { layout } = props;
    const classes = layoutOverviewPageClasses();

    const viewIsAlreadyApplied = (layout?.layoutViews || []).length > 0;

    const isDefault = layout?.isDefault ?? false;
    const canDelete = !viewIsAlreadyApplied && !isDefault;

    const [deleteModalOpen, setDeleteModalOpen] = useState(false);

    const toast = useToast();
    const history = useHistory();

    const deleteLayout = useDeleteLayout({
        layoutID: layout.layoutID,
        onSuccessBeforeDeletion: () => {
            history.replace(getRelativeUrl(LegacyLayoutsRoute.url(layout.layoutViewType)));
        },
    });

    return (
        <DropDownItemButton onClick={() => setDeleteModalOpen(true)} disabled={!canDelete}>
            <span className={classes.dropdownItemLabel}>{t("Delete")}</span>
            {!canDelete && (
                <ToolTip
                    label={
                        viewIsAlreadyApplied
                            ? t("This layout cannot be deleted because it is currently applied.")
                            : t("This layout cannot be deleted because it is a layout template.")
                    }
                >
                    <ToolTipIcon>
                        <Icon className={iconClasses().errorFgColor} icon={"status-warning"} size={"compact"} />
                    </ToolTipIcon>
                </ToolTip>
            )}

            <ModalConfirm
                isVisible={deleteModalOpen}
                title={t("Delete Layout")}
                onCancel={() => setDeleteModalOpen(false)}
                onConfirm={async () => {
                    try {
                        await deleteLayout();
                        toast.addToast({
                            autoDismiss: true,
                            body: <>{t("Layout successfully deleted.")}</>,
                        });
                    } catch (e) {
                        toast.addToast({
                            autoDismiss: true,
                            body: <>{e.description}</>,
                        });
                    }
                }}
                confirmTitle={t("Delete")}
                bodyClassName={css({ justifyContent: "start" })}
            >
                {t("Are you sure you want to delete?")}
            </ModalConfirm>
        </DropDownItemButton>
    );
}
