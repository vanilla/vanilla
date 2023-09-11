/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState, useEffect, useMemo } from "react";
import { IGetDiscussionListParams } from "@dashboard/@types/api/discussion";
import Frame from "@library/layout/frame/Frame";
import { t } from "@vanilla/i18n";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { ClearIcon } from "@library/icons/common";
import ModalSizes from "@library/modal/ModalSizes";
import Modal from "@library/modal/Modal";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { useFormik } from "formik";
import { ToolTip } from "@library/toolTip/ToolTip";
import { SelectTags } from "@library/features/discussions/filters/SelectTags";
import { SelectStatuses } from "@library/features/discussions/filters/SelectStatuses";
import { SelectTypes } from "@library/features/discussions/filters/SelectTypes";
import { SelectInternalStatus } from "./SelectInternalStatus";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

interface IProps {
    apiParams: IGetDiscussionListParams;
    updateApiParams: (newParams: Partial<IGetDiscussionListParams>) => void;
    forceOpen?: boolean;
}

interface IFilterFormValues {
    type: string[];
    tagID: string;
    statusID: number[];
    internalStatusID: number[];
}

const DEFAULT_FILTER_VALUES: IFilterFormValues = {
    type: [],
    tagID: "",
    internalStatusID: [],
    statusID: [],
};

export function DiscussionListFilter(props: IProps) {
    const { apiParams, updateApiParams, forceOpen } = props;
    const [isOpen, setIsOpen] = useState<boolean>(forceOpen ?? false);
    const classes = discussionListClasses();
    const { hasPermission } = usePermissionsContext();
    // this holds string value of selected types to limit available status options
    const [filterStatus, setFilterStatus] = useState<string[]>([]);

    const isCommunityManager = hasPermission("community.manage");

    const { values, submitForm, setValues, isSubmitting, resetForm, dirty } = useFormik<IFilterFormValues>({
        initialValues: DEFAULT_FILTER_VALUES,
        onSubmit: (newValues, { setSubmitting }) => {
            updateApiParams(newValues);
            setSubmitting(false);
            close();
        },
    });

    const close = () => {
        setIsOpen(false);
    };

    const clearAllFilters = () => {
        updateApiParams(DEFAULT_FILTER_VALUES);
        resetForm();
    };

    useEffect(() => {
        setValues({
            type: apiParams.type ?? DEFAULT_FILTER_VALUES.type,
            tagID: apiParams.tagID ?? DEFAULT_FILTER_VALUES.tagID,
            internalStatusID: apiParams.internalStatusID ?? DEFAULT_FILTER_VALUES.internalStatusID,
            statusID: apiParams.statusID ?? DEFAULT_FILTER_VALUES.statusID,
        });
    }, [apiParams, setValues]);

    return (
        <>
            <div className={classes.filterContainer}>
                <Button
                    buttonType={ButtonTypes.TEXT_PRIMARY}
                    onClick={() => setIsOpen(true)}
                    className={classes.filterAndSortingButton}
                >
                    {t("Filters")}
                    <Icon icon={`search-filter-small${dirty ? "-applied" : ""}`} />
                </Button>
                {dirty && (
                    <ToolTip label={t("Clear all filters")}>
                        <span>
                            <Button
                                buttonType={ButtonTypes.ICON_COMPACT}
                                onClick={clearAllFilters}
                                className={classes.filterAndSortingButton}
                            >
                                <ClearIcon />
                            </Button>
                        </span>
                    </ToolTip>
                )}
            </div>
            <Modal isVisible={isOpen} size={ModalSizes.SMALL} exitHandler={close} id="discussionListFilter">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        submitForm();
                    }}
                >
                    <Frame
                        header={<FrameHeader title={t("Filter Posts")} closeFrame={close} />}
                        body={
                            <FrameBody className={classes.filterBody}>
                                <SelectTypes
                                    value={values.type}
                                    onChange={(type) => {
                                        setValues({ ...values, type });
                                        setFilterStatus(type);
                                    }}
                                    label={t("Post Type")}
                                    inModal
                                />
                                <SelectStatuses
                                    value={values.statusID}
                                    onChange={(statusID) => setValues({ ...values, statusID })}
                                    label={t("Post Status")}
                                    inModal
                                    types={filterStatus}
                                />
                                {isCommunityManager && (
                                    <SelectInternalStatus
                                        value={values.internalStatusID}
                                        onChange={(internalStatusID) => setValues({ ...values, internalStatusID })}
                                        label={t("Resolution Status")}
                                    />
                                )}
                                <SelectTags
                                    value={values.tagID}
                                    onChange={(tagID) => setValues({ ...values, tagID })}
                                    label={t("Tags")}
                                    inModal
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter>
                                <Button buttonType={ButtonTypes.TEXT} onClick={() => resetForm()}>
                                    {t("Clear All")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY} disabled={isSubmitting}>
                                    {t("Apply")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}
