/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { AppliedLayoutViewLocationDetails } from "@dashboard/appearance/components/AppliedLayoutViewLocationDetails";
import { ApplyLayout } from "@dashboard/appearance/components/ApplyLayout";
import { DeleteLayout } from "@dashboard/appearance/components/DeleteLayout";
import { AppearanceNav } from "@dashboard/appearance/nav/AppearanceNav";
import { ErrorWrapper } from "@dashboard/appearance/pages/ErrorWrapper";
import { LayoutEditorRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import AdminLayout from "@dashboard/components/AdminLayout";
import { useLayoutQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { ILayoutDetails, LayoutViewType } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LayoutOverview } from "@dashboard/layout/overview/LayoutOverview";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { MetaItem, Metas } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import ProfileLink from "@library/navigation/ProfileLink";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { notEmpty, stableObjectHash } from "@vanilla/utils";
import React from "react";
import { RouteComponentProps } from "react-router-dom";
import { layoutOverviewPageClasses } from "./LayoutOverviewPage.classes";

function LayoutOverviewPageMetasImpl(props: { layout: ILayoutDetails }) {
    const { layout } = props;

    const classesMetas = metasClasses();

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
            <AppliedLayoutViewLocationDetails layout={layout} mode="meta" />
        </Metas>
    );
}

function TitleBarActionsContent(props: { layout: ILayoutDetails }) {
    const { layout } = props;
    const { layoutID, layoutViewType } = layout;

    const classes = layoutOverviewPageClasses();

    return (
        <>
            <DropDown
                key={stableObjectHash(layout.layoutViews)}
                name={t("Layout Options")}
                flyoutType={FlyoutType.LIST}
                className={classes.layoutOptionsDropdown}
            >
                <ApplyLayout layout={layout} />
                <DeleteLayout layout={layout} />
                <DropDownItemLink
                    to={LayoutEditorRoute.url({
                        layoutID,
                        layoutViewType,
                        isCopy: true,
                    })}
                >
                    {t("Copy")}
                </DropDownItemLink>
            </DropDown>

            <LinkAsButton
                buttonType={ButtonTypes.OUTLINE}
                to={LayoutEditorRoute.url({
                    layoutID,
                    layoutViewType,
                    isCopy: layout.isDefault,
                })}
            >
                {layout.isDefault ? t("Copy") : t("Edit")}
            </LinkAsButton>
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

    const layoutQuery = useLayoutQuery(layoutID);

    const descriptionContent = layoutQuery.isLoading ? (
        <LoadingRectangle width={320} height={22} />
    ) : layoutQuery.isError ? (
        <ErrorWrapper message={layoutQuery.error.message}>
            <ErrorMessages errors={[layoutQuery.error].filter(notEmpty)} />
        </ErrorWrapper>
    ) : (
        <LayoutOverviewPageMetasImpl layout={layoutQuery.data} />
    );

    return (
        <AdminLayout
            contentClassNames={classes.overviewContent}
            activeSectionID={"appearance"}
            title={layoutQuery.data?.name || ""}
            description={descriptionContent}
            titleBarActions={layoutQuery.data ? <TitleBarActionsContent layout={layoutQuery.data} /> : <></>}
            adminBarHamburgerContent={<AppearanceNav asHamburger />}
            leftPanel={!isCompact && <AppearanceNav />}
            content={<LayoutOverview layoutID={layoutID} />}
            titleLabel={
                (layoutQuery.data?.layoutViews ?? []).length > 0 ? (
                    <span className={classes.titleLabel}>{t("Applied")}</span>
                ) : undefined
            }
        />
    );
}
