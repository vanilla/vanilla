/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardLabelType";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { AddInterest, EditInterest } from "@dashboard/interestsSettings/AddInterest";
import { DeleteInterest } from "@dashboard/interestsSettings/DeleteInterest";
import { interestsClasses } from "@dashboard/interestsSettings/Interests.styles";
import { InterestFilters, InterestQueryParams } from "@dashboard/interestsSettings/Interests.types";
import { InterestsFilters } from "@dashboard/interestsSettings/InterestsFilters";
import { useInterests, useToggleSuggestedContent } from "@dashboard/interestsSettings/InterestsSettings.hooks";
import StackableTable from "@dashboard/tables/StackableTable/StackableTable";
import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { TokenItem } from "@library/metas/TokenItem";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getMeta, t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import isEmpty from "lodash-es/isEmpty";
import sortBy from "lodash-es/sortBy";
import { useState } from "react";
import { MemoryRouter } from "react-router";
import { sprintf } from "sprintf-js";

function InterestsSettingsImpl() {
    const classes = interestsClasses();
    const [suggestedContentEnabled, setSuggestedContentEnabled] = useState<boolean>(
        getMeta("suggestedContentEnabled", false),
    );
    const { mutateAsync: toggleSuggestedContent } = useToggleSuggestedContent();
    const toast = useToast();
    const toastError = useToastErrorHandler();
    const [filters, setFilters] = useState<InterestFilters>({});
    const [queryParams, setQueryParams] = useState<InterestQueryParams>({});
    const {
        query: { data, status },
        invalidate: invalidateInterestsQuery,
    } = useInterests(queryParams);
    const isLoading = status === LoadStatus.LOADING;

    const device = useDevice();
    const isMobile = [Devices.MOBILE, Devices.XS].includes(device);
    const [openFilterModal, setOpenFilterModal] = useState<boolean>(false);
    const { interestsList = [] } = data ?? {};
    const [isFiltered, setIsFiltered] = useState<boolean>(false);

    const clearFilters = () => {
        setFilters({});
        void invalidateInterestsQuery();
    };

    const saveFilters = () => {
        setQueryParams({
            ...filters,
        });
        setOpenFilterModal(false);
        setIsFiltered(!isEmpty(filters));
    };

    return (
        <>
            <DashboardHeaderBlock
                title={t("Interests & Suggested Content")}
                actionButtons={suggestedContentEnabled ? <AddInterest onSuccess={invalidateInterestsQuery} /> : null}
            />
            <DashboardFormGroup
                label={t("Enable Suggested Content and Interest Mapping")}
                labelType={DashboardLabelType.WIDE}
                tag="div"
            >
                <DashboardToggle
                    checked={suggestedContentEnabled}
                    onChange={async (enabled) => {
                        try {
                            const isEnabled = await toggleSuggestedContent(enabled);
                            setSuggestedContentEnabled(isEnabled);
                            toast.addToast({
                                autoDismiss: true,
                                body: sprintf(
                                    "Suggested content has been %s",
                                    isEnabled ? t("enabled") : t("disabled"),
                                ),
                            });
                        } catch (err) {
                            toastError(
                                sprintf(
                                    "An error occurred %s suggested content. Please try again.",
                                    enabled ? t("enabling") : t("disabling"),
                                ),
                            );
                        }
                    }}
                />
            </DashboardFormGroup>
            {suggestedContentEnabled && (
                <>
                    <div className={classes.tablePaging}>
                        {isMobile && (
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                onClick={() => setOpenFilterModal(true)}
                                title={t("Filter")}
                            >
                                <Icon icon={isFiltered ? "filter-applied" : "filter"} />
                            </Button>
                        )}
                    </div>
                    <StackableTable
                        data={interestsList ?? []}
                        isLoading={isLoading}
                        className={classes.table}
                        updateQuery={() => null}
                        onHeaderClick={() => null}
                        CellRenderer={InterestTableCell}
                        WrappedCellRenderer={InterestTableCellWrapper}
                        ActionsCellRenderer={({ data: interest }) => (
                            <div className={classes.tableActions}>
                                <EditInterest interest={interest} onSuccess={invalidateInterestsQuery} />
                                <DeleteInterest interest={interest} onSuccess={invalidateInterestsQuery} />
                            </div>
                        )}
                        actionsColumnWidth={50}
                        hiddenHeaders={["actions"]}
                        columnsConfiguration={{
                            Interest: {
                                order: 1,
                                wrapped: true,
                                isHidden: false,
                            },
                            "Profile Fields": {
                                order: 2,
                                wrapped: false,
                                isHidden: false,
                                width: 275,
                            },
                            Tags: {
                                order: 3,
                                wrapped: false,
                                isHidden: false,
                                width: 200,
                            },
                            Categories: {
                                order: 4,
                                wrapped: false,
                                isHidden: false,
                            },
                        }}
                        headerWrappers={{
                            Interest: (props) => {
                                return <div className={classes.interestsHeader}>{props.children}</div>;
                            },
                        }}
                    />
                    {!isLoading && interestsList?.length === 0 && <p>{t("No Results.")}</p>}
                </>
            )}
            <DashboardHelpAsset>
                <h2>{t("About")}</h2>
                <p>
                    <Translate
                        source="Help users discover relevant content by mapping interests to profile fields, categories, and tags. Be sure to enable the Suggested Content widget on the layouts you want suggested content surfaced. <0/>"
                        c0={
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1630-interests-suggested-content">
                                {t("Learn more.")}
                            </SmartLink>
                        }
                    />
                </p>
                {suggestedContentEnabled && !isMobile && (
                    <>
                        <h2>{t("Filters")}</h2>
                        <div className={classes.filterAside}>
                            <InterestsFilters filters={filters} updateFilters={setFilters} />
                            <div className={classes.filterButtons}>
                                <Button buttonType={ButtonTypes.DASHBOARD_STANDARD} onClick={clearFilters}>
                                    {t("Clear All")}
                                </Button>
                                <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY} onClick={saveFilters}>
                                    {t("Filter")}
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </DashboardHelpAsset>
            <Modal isVisible={openFilterModal} size={ModalSizes.SMALL}>
                <form
                    onSubmit={(evt) => {
                        evt.preventDefault();
                        saveFilters();
                    }}
                >
                    <Frame
                        header={<FrameHeader closeFrame={() => setOpenFilterModal(false)} title={t("Filter")} />}
                        body={
                            <FrameBody>
                                <InterestsFilters filters={filters} updateFilters={setFilters} />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={clearFilters}
                                >
                                    {t("Clear All")}
                                </Button>
                                <Button
                                    submit
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {t("Filter")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}

function InterestTableCell(props) {
    const { columnName, data } = props;
    const classes = interestsClasses();

    switch (columnName) {
        case "Interest":
            return (
                <div className={classes.cellFlexBox}>
                    <div>
                        <p className={classes.interestName}>{data.name}</p>
                        <p className={classes.interestApiName}>{data.apiName}</p>
                    </div>
                </div>
            );

        case "Profile Fields":
            if (data.isDefault) {
                return (
                    <ToolTip label={t("Default Interests target all users regardless of profile fields.")}>
                        <span className={classes.defaultInterestTooltip}>
                            <Icon icon={"info"} />
                            <span>{t("Default Interest")}</span>
                        </span>
                    </ToolTip>
                );
            }
            if (data.profileFields?.length) {
                if (props.wrappedVersion) {
                    return (
                        <p className={classes.cellWrapped}>
                            <span className={classes.cellWrappedTitle}>{columnName}</span>
                            {data.profileFields.map(({ label, mappedValue }, idx) => {
                                return (
                                    <span key={idx} className={classes.cellWrappedProfileField}>
                                        {label}
                                        <em>({mappedValue.join(", ")})</em>
                                        {idx < data.profileFields.length - 1 && <>{"; "}</>}
                                    </span>
                                );
                            })}
                        </p>
                    );
                }
                return (
                    <>
                        {data.profileFields.map((profileField) => (
                            <div
                                key={profileField.apiName}
                                className={cx(classes.cellFlexBox, classes.interestProfileField)}
                            >
                                <span>{profileField.label}:</span>
                                <div className={cx(classes.cellFlexBox, classes.interestProfileFieldValues)}>
                                    {profileField.mappedValue.map((value) => (
                                        <TokenItem key={value}>{value.toString()}</TokenItem>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </>
                );
            }
            break;

        case "Tags":
            if (data.tags?.length) {
                const tagList = sortBy(data.tags, ["fullName"]);

                if (props.wrappedVersion) {
                    return (
                        <p className={classes.cellWrapped}>
                            <span className={classes.cellWrappedTitle}>{columnName}</span>
                            {data.tags.map(({ fullName }) => fullName).join(", ")}
                        </p>
                    );
                }

                return (
                    <div className={classes.cellFlexBox}>
                        <div className={classes.cellFlexBox}>
                            {tagList.map((tag) => (
                                <TokenItem key={tag.tagID}>{tag.fullName}</TokenItem>
                            ))}
                        </div>
                    </div>
                );
            }
            break;

        case "Categories":
            if (data.categories?.length) {
                if (props.wrappedVersion) {
                    return (
                        <p className={classes.cellWrapped}>
                            <span className={classes.cellWrappedTitle}>{columnName}</span>
                            {data.categories.map(({ name }) => name).join(", ")}
                        </p>
                    );
                }
                return (
                    <>
                        {data.categories.map((category) => (
                            <p key={category.categoryID} className={classes.interestCategory}>
                                {category.name}
                            </p>
                        ))}
                    </>
                );
            }
            break;
    }

    return <></>;
}

function InterestTableCellWrapper(props) {
    let result = <></>;
    if (props?.orderedColumns && props?.configuration && props?.data) {
        props.orderedColumns.forEach((columnName, index) => {
            const columnConfig = props.configuration[columnName];
            if (!columnConfig?.hidden && columnConfig?.wrapped) {
                result = (
                    <>
                        {index !== 0 && result}
                        <InterestTableCell
                            columnName={columnName}
                            data={props.data}
                            wrappedVersion={columnConfig.wrapped}
                        />
                    </>
                );
            }
        });
    }

    return result;
}

export function InterestsSettings() {
    return (
        <MemoryRouter>
            <ErrorPageBoundary>
                <InterestsSettingsImpl />
            </ErrorPageBoundary>
        </MemoryRouter>
    );
}
