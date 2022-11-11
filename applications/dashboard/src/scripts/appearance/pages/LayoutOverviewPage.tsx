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
import { RouteComponentProps } from "react-router-dom";
import { useLayout } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { LoadStatus } from "@library/@types/api/core";
import ErrorMessages from "@library/forms/ErrorMessages";
import { notEmpty } from "@vanilla/utils";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import layoutOverviewPageClasses from "./LayoutOverviewPage.classes";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { ErrorWrapper } from "@dashboard/appearance/pages/ErrorWrapper";
import { LayoutOverview } from "@dashboard/layout/overview/LayoutOverview";
import { MetaItem, Metas } from "@library/metas/Metas";
import ProfileLink from "@library/navigation/ProfileLink";
import { metasClasses } from "@library/metas/Metas.styles";
import { LayoutEditorRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import {
    LayoutActionsContextProvider,
    useLayoutActionsContext,
} from "@dashboard/layout/layoutSettings/LayoutActionsContextProvider";
import { useConfigsByKeys } from "@library/config/configHooks";
import { LAYOUT_EDITOR_CONFIG_KEY } from "@dashboard/appearance/nav/AppearanceNav.hooks";
import { ToolTip } from "@library/toolTip/ToolTip";

function LayoutOverviewPageMetasImpl(props: { layout: ILayoutDetails }) {
    const { layout } = props;

    const classesMetas = metasClasses();
    const layoutViewNames = layout.layoutViews ? layout.layoutViews.map((layoutView) => layoutView.record.name) : [];

    const appliedGloballyOnly = layoutViewNames.length && !(layoutViewNames || []).some((value) => value !== "global");

    return (
        <Metas>
            <MetaItem>
                <Translate
                    source="Created <0/> by <1/>."
                    c0={<DateTime timestamp={layout.dateInserted} />}
                    c1={
                        <ProfileLink
                            className={classesMetas.metaLink}
                            userFragment={{
                                userID: layout.insertUserID as number,
                                name: layout.insertUser!.name,
                            }}
                        />
                    }
                />
            </MetaItem>
            {layout.updateUser && layout.dateUpdated && (
                <MetaItem>
                    <Translate
                        source="Last updated <0/> by <1/>. "
                        c0={<DateTime timestamp={layout.dateUpdated} />}
                        c1={<ProfileLink className={classesMetas.metaLink} userFragment={layout.updateUser} />}
                    />
                </MetaItem>
            )}
            <MetaItem>
                {!!layoutViewNames.length &&
                    t("Applied on ") +
                        (appliedGloballyOnly
                            ? layout.layoutViewType === "discussionList"
                                ? t("recent discussions page")
                                : t("homepage")
                            : layoutViewNames.join(", ")) +
                        "."}
            </MetaItem>
        </Metas>
    );
}

function TitleBarActionsContent(props: { layout: ILayoutDetails }) {
    const { layout } = props;
    const { layoutID, layoutViewType } = layout;

    const { DeleteLayout, ApplyLayout } = useLayoutActionsContext();

    const classes = layoutOverviewPageClasses();

    const canEdit = !layout.isDefault;
    let editButton = (
        <LinkAsButton
            buttonType={ButtonTypes.OUTLINE}
            to={LayoutEditorRoute.url({
                layoutID,
                layoutViewType,
            })}
            disabled={!canEdit}
        >
            {t("Edit")}
        </LinkAsButton>
    );

    if (!canEdit) {
        editButton = (
            <ToolTip label={t("This is a default layout and cannot be edited.")}>
                <span>{editButton}</span>
            </ToolTip>
        );
    }

    return (
        <>
            <DropDown name={t("Layout Options")} flyoutType={FlyoutType.LIST} className={classes.layoutOptionsDropdown}>
                {/* <DropDownItemButton onClick={() => {}}>{t("Preview")}</DropDownItemButton> */}
                <ApplyLayout layout={layout} />
                <DeleteLayout layout={layout} />
            </DropDown>

            {editButton}
        </>
    );
}

export default function LayoutOverviewPage(
    props: RouteComponentProps<{
        layoutID: string;
        layoutViewType: LayoutViewType;
    }>,
) {
    const layoutID = props.match.params.layoutID;

    const classes = layoutOverviewPageClasses();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    const config = useConfigsByKeys([LAYOUT_EDITOR_CONFIG_KEY]);
    const isCustomLayoutsEnabled = !!config?.data?.[LAYOUT_EDITOR_CONFIG_KEY];

    const layoutLoadable = useLayout(layoutID);
    const layout = layoutLoadable.data;

    const layoutStatusIsPending = [LoadStatus.PENDING, LoadStatus.LOADING].includes(layoutLoadable.status);
    const layoutStatusIsError = !layoutLoadable.data || layoutLoadable.error;

    const errorContent = (errorLoadable) => (
        <ErrorWrapper message={errorLoadable.error.message}>
            <ErrorMessages errors={[errorLoadable.error].filter(notEmpty)} />
        </ErrorWrapper>
    );

    const descriptionContent = layoutStatusIsPending ? (
        <LoadingRectangle width={320} height={22} />
    ) : layoutStatusIsError ? (
        errorContent(layoutLoadable)
    ) : (
        isCustomLayoutsEnabled && <LayoutOverviewPageMetasImpl layout={layout!} />
    );

    return (
        <AdminLayout
            contentClassNames={classes.overviewContent}
            activeSectionID={"appearance"}
            title={isCustomLayoutsEnabled ? layout?.name || "" : ""}
            description={descriptionContent}
            titleBarActions={
                !layoutStatusIsError && isCustomLayoutsEnabled ? (
                    <LayoutActionsContextProvider>
                        <TitleBarActionsContent layout={layout!} />
                    </LayoutActionsContextProvider>
                ) : (
                    <></>
                )
            }
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={
                layoutID && isCustomLayoutsEnabled ? (
                    <LayoutOverview layoutID={layoutID} />
                ) : (
                    config.status === LoadStatus.SUCCESS && <h1 style={{ padding: 24 }}>{t("Page Not Found")}</h1>
                )
            }
            titleLabel={
                (layout?.layoutViews ?? []).length > 0 && isCustomLayoutsEnabled ? (
                    <span className={classes.titleLabel}>{t("Applied")}</span>
                ) : undefined
            }
        />
    );
}
