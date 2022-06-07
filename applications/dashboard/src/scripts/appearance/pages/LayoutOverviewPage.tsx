/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useCallback, useMemo, useState } from "react";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { t } from "@vanilla/i18n";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RouteComponentProps } from "react-router-dom";
import { useDeleteLayout, useLayout, usePutLayoutView } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import layoutOverviewPageClasses from "./LayoutOverviewPage.classes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { ErrorWrapper } from "@dashboard/appearance/pages/ErrorWrapper";
import { LayoutOverview } from "@dashboard/layout/overview/LayoutOverview";
import { MetaItem, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import { LayoutEditorRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import { getRelativeUrl } from "@library/utility/appUtils";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import { iconClasses } from "@library/icons/iconStyles";
import { useToast } from "@library/features/toaster/ToastContext";
import { css } from "@emotion/css";
import ModalConfirm from "@library/modal/ModalConfirm";

interface IDescriptionProps {
    layout: ILayoutDetails;
}

function LayoutOverviewPageMetasImpl(props: IDescriptionProps) {
    const classesMetas = metasClasses();
    const layoutViewNames = props.layout?.layoutViews
        ? props.layout?.layoutViews.map((layoutView) => layoutView.record.name)
        : [];

    const appliedGloballyOnly = layoutViewNames.length && !(layoutViewNames || []).some((value) => value !== "global");

    return (
        <Metas>
            <MetaItem>
                <Translate
                    source="Created <0/> by <1/>."
                    c0={<DateTime timestamp={props.layout?.dateInserted} />}
                    c1={
                        <ProfileLink
                            className={classesMetas.metaLink}
                            userFragment={{
                                userID: props.layout.insertUserID as number,
                                name: props.layout.insertUser!.name,
                            }}
                        />
                    }
                />
            </MetaItem>
            {props.layout.updateUser && props.layout.dateUpdated && (
                <MetaItem>
                    <Translate
                        source="Last updated <0/> by <1/>. "
                        c0={<DateTime timestamp={props.layout?.dateUpdated} />}
                        c1={<ProfileLink className={classesMetas.metaLink} userFragment={props.layout.updateUser} />}
                    />
                </MetaItem>
            )}
            <MetaItem>
                {!!layoutViewNames.length &&
                    t("Applied on ") + (appliedGloballyOnly ? "homepage" : layoutViewNames.join(", ")) + "."}
            </MetaItem>
        </Metas>
    );
}

export default function LayoutOverviewPage(
    props: RouteComponentProps<{
        layoutID: string;
        layoutViewType: LayoutViewType;
    }>,
) {
    const { history } = props;
    const layoutID = props.match.params.layoutID;
    const layoutViewType = props.match.params.layoutViewType;

    const toast = useToast();

    const classes = layoutOverviewPageClasses();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    const putLayoutView = usePutLayoutView(layoutID);

    const [deleteModalVisible, setDeleteModalVisible] = useState(false);

    function openDeleteModal() {
        setDeleteModalVisible(true);
    }

    function closeDeleteModal() {
        setDeleteModalVisible(false);
    }

    const deleteLayout = useDeleteLayout({
        layoutID,
        onSuccessBeforeDeletion: () => history.replace(getRelativeUrl(LegacyLayoutsRoute.url(layoutViewType))),
    });

    async function handleDeleteLayout() {
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
    }

    const layoutLoadable = useLayout(layoutID);
    const layout = layoutLoadable.data;

    const layoutStatusIsPending = [LoadStatus.PENDING, LoadStatus.LOADING].includes(layoutLoadable.status);
    const layoutStatusIsError = !layoutLoadable.data || layoutLoadable.error;

    //this should be dynamic in the future to be able to apply to different recordTypes, right now only global on homepage
    const globalLayoutView = {
        recordType: "global",
        recordID: -1,
    };
    const viewIsAlreadyApplied =
        layoutLoadable.status === LoadStatus.SUCCESS &&
        (layout?.layoutViews || []).some(
            (layoutView) =>
                layoutView.recordType === globalLayoutView.recordType &&
                layoutView.recordID === globalLayoutView.recordID,
        );

    const isDefault = layout?.isDefault ?? false;
    const canDelete = !viewIsAlreadyApplied && !isDefault;

    const errorContent = (errorLoadable) => (
        <ErrorWrapper message={errorLoadable.error.message}>
            <ErrorMessages errors={[errorLoadable.error].filter(notEmpty)} />
        </ErrorWrapper>
    );

    const descriptionContent = layoutStatusIsPending ? (
        <LoadingRectangle width={320} height={18} />
    ) : layoutStatusIsError ? (
        errorContent(layoutLoadable)
    ) : (
        <LayoutOverviewPageMetasImpl layout={layout as ILayoutDetails} />
    );

    const titleBarActionsContent = !layoutStatusIsError ? (
        <>
            <DropDown name={t("Layout Options")} flyoutType={FlyoutType.LIST} className={classes.layoutOptionsDropdown}>
                <DropDownItemButton
                    onClick={() => {
                        !viewIsAlreadyApplied && putLayoutView(globalLayoutView);
                    }}
                >
                    {t("Apply")}
                </DropDownItemButton>

                {/* <DropDownItemButton onClick={() => {}}>{t("Preview")}</DropDownItemButton> */}

                <DropDownItemButton onClick={openDeleteModal} disabled={!canDelete}>
                    <span className={classes.dropdownItemLabel}>{t("Delete")}</span>
                    {!canDelete && (
                        <ToolTip
                            label={
                                viewIsAlreadyApplied
                                    ? t("This layout cannot be deleted because it is currently applied.")
                                    : t("This is a default layout and cannot be deleted.") //fixme
                            }
                        >
                            <ToolTipIcon>
                                <Icon className={iconClasses().errorFgColor} icon={"status-warning"} size={"compact"} />
                            </ToolTipIcon>
                        </ToolTip>
                    )}
                    <ModalConfirm
                        isVisible={deleteModalVisible}
                        title={t("Delete Layout")}
                        onCancel={closeDeleteModal}
                        onConfirm={handleDeleteLayout}
                        confirmTitle={t("Delete")}
                        bodyClassName={css({ justifyContent: "start" })}
                    >
                        {t("Are you sure you want to delete?")}
                    </ModalConfirm>
                </DropDownItemButton>
            </DropDown>
            <LinkAsButton
                buttonType={ButtonTypes.OUTLINE}
                to={LayoutEditorRoute.url({
                    layoutID,
                    layoutViewType,
                })}
            >
                {t("Edit")}
            </LinkAsButton>
        </>
    ) : (
        <></>
    );

    return (
        <AdminLayout
            contentClassNames={classes.overviewContent}
            activeSectionID={"appearance"}
            title={layout?.name || ""}
            description={descriptionContent}
            titleBarActions={titleBarActionsContent}
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={<LayoutOverview layoutID={layoutID} />}
            titleLabel={viewIsAlreadyApplied ? <span className={classes.titleLabel}>{t("Applied")}</span> : undefined}
        />
    );
}
