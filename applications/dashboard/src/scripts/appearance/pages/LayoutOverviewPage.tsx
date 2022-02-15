/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import AdminLayout from "@dashboard/components/AdminLayout";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { useCollisionDetector } from "@vanilla/react-utils";
import { t } from "@vanilla/i18n";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { RouteComponentProps } from "react-router";
import { useLayout, usePutLayoutView } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LoadStatus } from "@library/@types/api/core";
import Loader from "@library/loaders/Loader";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import { useUser } from "@library/features/users/userHooks";
import { ILayout } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import layoutOverviewPageClasses from "./LayoutOverviewPage.classes";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { ErrorWrapper } from "@dashboard/appearance/pages/ErrorWrapper";

interface IDescriptionProps {
    layout: ILayout;
}

function LayoutOverviewPageDescriptionImpl(props: IDescriptionProps) {
    const insertUser = useUser({ userID: props.layout.insertUserID });
    const layoutViewNames =
        props.layout?.layoutViews && props.layout?.layoutViews.map((layoutView) => layoutView.record.name);
    const appliedGloballyOnly = layoutViewNames.length && !layoutViewNames.some((value) => value !== "global");

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(insertUser.status)) {
        return <LoadingRectangle width={320} height={18} />;
    }

    if (!insertUser.data || insertUser.error) {
        return <ErrorMessages errors={[insertUser.error].filter(notEmpty)} />;
    }

    return (
        <>
            <Translate
                source="Created <0/> by <1/>. "
                c0={<DateTime timestamp={props.layout?.dateInserted} />}
                c1={insertUser.data?.name}
            />
            {!!layoutViewNames.length &&
                (appliedGloballyOnly ? t("Applied globally") : t("Applied on ") + layoutViewNames.join(", ")) + "."}
        </>
    );
}

export default function LayoutOverviewPage(
    props: RouteComponentProps<{
        layoutID: string;
    }>,
) {
    const layoutID = props.match.params.layoutID;
    const layoutLoadable = useLayout(layoutID);
    const layout = layoutLoadable.data;

    const classes = layoutOverviewPageClasses();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const putLayoutView = usePutLayoutView(layoutID);

    const layoutStatusIsPending = [LoadStatus.PENDING, LoadStatus.LOADING].includes(layoutLoadable.status);
    const layoutStatusIsError = !layoutLoadable.data || layoutLoadable.error;

    //this should be dynamic in the future to be able to apply to different recordTypes, right now only global on homepage
    const globalLayoutView = {
        recordType: "global",
        recordID: -1,
    };
    const viewIsAlreadyApplied =
        layoutLoadable.status === LoadStatus.SUCCESS &&
        layout?.layoutViews.some(
            (layoutView) =>
                layoutView.recordType === globalLayoutView.recordType &&
                layoutView.recordID === globalLayoutView.recordID,
        );

    const errorContent = (errorLoadable) => (
        <ErrorWrapper message={errorLoadable.error.message}>
            <ErrorMessages errors={[errorLoadable.error].filter(notEmpty)} />
        </ErrorWrapper>
    );

    const content = layoutStatusIsPending ? (
        <Loader />
    ) : layoutStatusIsError ? (
        errorContent(layoutLoadable)
    ) : (
        <div>Preview content is here</div>
    );

    const descriptionContent = layoutStatusIsPending ? (
        <LoadingRectangle width={320} height={18} />
    ) : layoutStatusIsError ? (
        errorContent(layoutLoadable)
    ) : (
        <LayoutOverviewPageDescriptionImpl layout={layout as ILayout} />
    );

    return (
        <AdminLayout
            activeSectionID={"appearance"}
            title={layout?.name || ""}
            description={descriptionContent}
            titleBarActions={
                <>
                    <DropDown
                        name={t("Layout Options")}
                        flyoutType={FlyoutType.LIST}
                        className={classes.layoutOptionsDropdown}
                    >
                        <DropDownItemButton
                            onClick={() => {
                                !viewIsAlreadyApplied && putLayoutView(globalLayoutView);
                            }}
                        >
                            {t("Apply")}
                        </DropDownItemButton>

                        <DropDownItemButton onClick={() => {}}>{t("Preview")}</DropDownItemButton>
                        <DropDownItemButton onClick={() => {}}>{t("Delete")}</DropDownItemButton>
                    </DropDown>
                    <LinkAsButton buttonType={ButtonTypes.OUTLINE} to="http://editPath">
                        {t("Edit")}
                    </LinkAsButton>
                </>
            }
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={content}
        />
    );
}
