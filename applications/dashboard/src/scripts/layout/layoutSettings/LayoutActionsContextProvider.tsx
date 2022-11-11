/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode, useContext, useState } from "react";
import { useHistory } from "react-router-dom";
import { ILayoutDetails, LayoutViewFragment } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { getRelativeUrl, t } from "@library/utility/appUtils";
import { LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import ModalConfirm from "@library/modal/ModalConfirm";
import { css } from "@emotion/css";
import { useToast } from "@library/features/toaster/ToastContext";
import { useDeleteLayout, usePutLayoutViews } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import layoutOverviewPageClasses from "@dashboard/appearance/pages/LayoutOverviewPage.classes";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { iconClasses } from "@library/icons/iconStyles";
import { Icon } from "@vanilla/icons";

interface ILayoutActionsContext {
    ApplyLayout: React.ComponentType<{ layout: ILayoutDetails }>;
    DeleteLayout: React.ComponentType<{ layout: ILayoutDetails }>;
}

const LayoutActionsContext = React.createContext<ILayoutActionsContext>({
    ApplyLayout: (_props) => null,
    DeleteLayout: (_props) => null,
});

export function useLayoutActionsContext() {
    return useContext(LayoutActionsContext);
}

//this should be dynamic in the future to be able to apply to different recordTypes, right now only global on homepage
export const GLOBAL_LAYOUT_VIEW: LayoutViewFragment = {
    recordType: "global",
    recordID: -1,
};

export const ROOT_LAYOUT_VIEW: LayoutViewFragment = {
    recordType: "root",
    recordID: -2,
};

function ApplyLayoutImpl(props: { layout: ILayoutDetails }) {
    const { layout } = props;
    const putLayoutViews = usePutLayoutViews(layout);
    const toast = useToast();

    const viewIsAlreadyApplied = (layout?.layoutViews || []).length > 0;

    return (
        <DropDownItemButton
            onClick={async () => {
                if (!viewIsAlreadyApplied) {
                    try {
                        await putLayoutViews([GLOBAL_LAYOUT_VIEW]);
                        toast.addToast({
                            autoDismiss: true,
                            body: <>{t("Layout applied.")}</>,
                        });
                    } catch (e) {
                        toast.addToast({
                            autoDismiss: false,
                            dismissible: true,
                            body: <>{e.description}</>,
                        });
                    }
                }
            }}
        >
            {t("Apply")}
        </DropDownItemButton>
    );
}

let _ApplyLayoutComponent: React.ComponentType<{ layout: ILayoutDetails }> = ApplyLayoutImpl;

LayoutActionsContextProvider.setApplyLayoutComponent = (
    ApplyLayoutComponent: React.ComponentType<{ layout: ILayoutDetails }>,
) => {
    _ApplyLayoutComponent = ApplyLayoutComponent;
};

function DeleteLayoutImpl(props: { layout: ILayoutDetails }) {
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
                            : t("This is a default layout and cannot be deleted.")
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

let _DeleteLayoutComponent: React.ComponentType<{ layout: ILayoutDetails }> = DeleteLayoutImpl;

LayoutActionsContextProvider.setDeleteLayoutComponent = (
    DeleteLayoutComponent: React.ComponentType<{ layout: ILayoutDetails }>,
) => {
    _DeleteLayoutComponent = DeleteLayoutComponent;
};

export function LayoutActionsContextProvider(props: { children: ReactNode }) {
    const { children } = props;

    return (
        <LayoutActionsContext.Provider
            value={{
                DeleteLayout: _DeleteLayoutComponent,
                ApplyLayout: _ApplyLayoutComponent,
            }}
        >
            {children}
        </LayoutActionsContext.Provider>
    );
}
