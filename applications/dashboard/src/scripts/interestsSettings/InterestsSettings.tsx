/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { AddInterest } from "@dashboard/interestsSettings/AddInterest";
import { interestsClasses } from "@dashboard/interestsSettings/Interests.styles";
import { IInterest, InterestFilters, InterestQueryParams } from "@dashboard/interestsSettings/Interests.types";
import { InterestsFilters } from "@dashboard/interestsSettings/InterestsFilters";
import {
    useDeleteInterest,
    useInterests,
    useToggleSuggestedContent,
} from "@dashboard/interestsSettings/InterestsSettings.hooks";
import StackableTable from "@dashboard/tables/StackableTable/StackableTable";
import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { TokenItem } from "@library/metas/TokenItem";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
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
    const [openModal, setOpenModal] = useState<boolean>(false);
    const [selectedInterest, setSelectedInterest] = useState<IInterest>();
    const [filters, setFilters] = useState<InterestFilters>({});
    const [queryParams, setQueryParams] = useState<InterestQueryParams>({ page: 1 });
    const { data, isLoading, refetch } = useInterests(queryParams);
    const { mutateAsync: deleteInterest } = useDeleteInterest(queryParams);
    const device = useDevice();
    const isMobile = [Devices.MOBILE, Devices.XS].includes(device);
    const [openFilterModal, setOpenFilterModal] = useState<boolean>(false);
    const { interestsList = [], pagination } = data ?? {};
    const [isFiltered, setIsFiltered] = useState<boolean>(false);
    const [openDeleteConfirm, setOpenDeleteConfirm] = useState<boolean>(false);

    const openAddEditModal = (interest?: IInterest) => {
        setSelectedInterest(interest);
        setOpenModal(true);
    };

    const closeAddEditModal = () => {
        setSelectedInterest(undefined);
        setOpenModal(false);
        refetch();
    };

    const clearFilters = () => setFilters({});

    const saveFilters = () => {
        setQueryParams({
            page: queryParams.page,
            ...filters,
        });
        setOpenFilterModal(false);
        setIsFiltered(!isEmpty(filters));
    };

    const updatePage = (page: number) => {
        setQueryParams({
            ...filters,
            page,
        });
    };

    const handleDeleteInterest = async () => {
        if (selectedInterest) {
            try {
                await deleteInterest(selectedInterest.interestID);
                setOpenDeleteConfirm(false);
                toast.addToast({
                    autoDismiss: true,
                    body: (
                        <Translate source="You have successfully deleted interest: <0/>" c0={selectedInterest.name} />
                    ),
                });
                setSelectedInterest(undefined);
            } catch (err) {
                toastError(err);
            }
        }
    };

    return (
        <>
            <DashboardHeaderBlock
                title={t("Interests & Suggested Content")}
                actionButtons={
                    suggestedContentEnabled ? (
                        <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY} onClick={() => openAddEditModal()}>
                            {t("Add Interest")}
                        </Button>
                    ) : null
                }
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
                                <Icon icon={isFiltered ? "search-filter-applied" : "search-filter"} />
                            </Button>
                        )}
                        <NumberedPager
                            showNextButton={false}
                            isMobile={isMobile}
                            totalResults={pagination?.total}
                            currentPage={pagination?.currentPage ?? 1}
                            pageLimit={pagination?.limit}
                            onChange={updatePage}
                            className={classes.pager}
                        />
                    </div>
                    <StackableTable
                        data={interestsList ?? []}
                        isLoading={isLoading}
                        className={classes.table}
                        updateQuery={() => null}
                        onHeaderClick={() => null}
                        CellRenderer={InterestTableCell}
                        WrappedCellRenderer={InterestTableCellWrapper}
                        ActionsCellRenderer={(props) => (
                            <div className={classes.tableActions}>
                                <Button
                                    buttonType={ButtonTypes.ICON_COMPACT}
                                    onClick={() => {
                                        setSelectedInterest(props.data);
                                        setOpenModal(true);
                                    }}
                                >
                                    <Icon icon="data-pencil" />
                                </Button>
                                <Button
                                    buttonType={ButtonTypes.ICON_COMPACT}
                                    onClick={() => {
                                        setSelectedInterest(props.data);
                                        setOpenDeleteConfirm(true);
                                    }}
                                >
                                    <Icon icon="data-trash" />
                                </Button>
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
                                return (
                                    <div className={classes.interestsHeader}>
                                        {!props.firstColumnWrapped && (
                                            <ToolTip label={t("Default Interest")}>
                                                <span className={classes.defaultInterestIcon}>
                                                    <Icon
                                                        icon="event-interested-filled"
                                                        size="compact"
                                                        className={classes.defaultInterestIconHeader}
                                                    />
                                                </span>
                                            </ToolTip>
                                        )}
                                        {props.children}
                                    </div>
                                );
                            },
                        }}
                    />
                    {!isLoading && interestsList?.length === 0 && <p>{t("No Results.")}</p>}
                </>
            )}
            <AddInterest interest={selectedInterest} isVisible={openModal} onClose={closeAddEditModal} />
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
                        scrollable
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
            <ModalConfirm
                isVisible={openDeleteConfirm}
                title={t("Delete Interest")}
                onCancel={() => {
                    setSelectedInterest(undefined);
                    setOpenDeleteConfirm(false);
                }}
                onConfirm={handleDeleteInterest}
            >
                <Translate source="Do you wish to delete interest: <0/>" c0={selectedInterest?.name} />
            </ModalConfirm>
        </>
    );
}

function InterestTableCell(props) {
    const { columnName, data } = props;
    const classes = interestsClasses();

    switch (columnName) {
        case "Interest":
            return (
                <div
                    className={cx(classes.cellFlexBox, {
                        [classes.interestWrapped]: props.wrappedVersion,
                    })}
                >
                    <div className={classes.defaultInterestIcon}>
                        {data.isDefault && (
                            <>
                                <ConditionalWrap
                                    condition={!props.wrappedVersion}
                                    component={ToolTip}
                                    componentProps={{ label: t("Default Interest") }}
                                >
                                    <span>
                                        <Icon icon="event-interested-filled" size="compact" />
                                    </span>
                                </ConditionalWrap>
                                {props.wrappedVersion && <span>{t("Default Interest")}</span>}
                            </>
                        )}
                    </div>
                    <div>
                        <p className={classes.interestName}>{data.name}</p>
                        <p className={classes.interestApiName}>{data.apiName}</p>
                    </div>
                </div>
            );

        case "Profile Fields":
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
