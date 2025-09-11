/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import InputBlock from "@library/forms/InputBlock";
import LazyDateRange from "@library/forms/LazyDateRange";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { cx } from "@emotion/css";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Frame from "@library/layout/frame/Frame";
import { useState } from "react";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { dateRangeToString, dateStringInUrlToDateRange } from "@library/search/SearchUtils";
import { NestedSelect } from "@library/forms/nestedSelect";
import { IDraft } from "@vanilla/addon-vanilla/drafts/types";
import { Icon } from "@vanilla/icons";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { draftsClasses } from "../Drafts.classes";
import { Row } from "@library/layout/Row";

interface IDraftsFilterFormValues {
    dateUpdated?: {
        start?: string;
        end?: string;
    };
    dateScheduled?: {
        start?: string;
        end?: string;
    };
    recordType?: IDraft["recordType"];
}

function DraftsFilterForm(props: IDraftsFilterProps) {
    const { isSchedule, inModal, onFilter, initialFilters } = props;

    const classes = draftsClasses();

    const [filterFormValues, setFilterFormValues] = useState<IDraftsFilterFormValues>(initialFilters ?? {});

    const handleSubmit = () => {
        onFilter({
            dateUpdated: dateRangeToString(filterFormValues.dateUpdated ?? {}),
            dateScheduled: dateRangeToString(filterFormValues.dateScheduled ?? {}),
            recordType: filterFormValues.recordType,
        });
    };

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                handleSubmit();
            }}
        >
            <Frame
                header={<FrameHeader title={t("Filter Results")} closeFrame={props.onClose} borderless={!inModal} />}
                body={
                    <FrameBody>
                        <div className={cx(frameBodyClasses().contents)}>
                            <InputBlock legend={t("Date Updated")}>
                                <LazyDateRange
                                    onStartChange={(date: string) => {
                                        setFilterFormValues({
                                            ...filterFormValues,
                                            dateUpdated: {
                                                ...filterFormValues.dateUpdated,
                                                start: date,
                                            },
                                        });
                                    }}
                                    onEndChange={(date: string) => {
                                        setFilterFormValues({
                                            ...filterFormValues,
                                            dateUpdated: { ...filterFormValues.dateUpdated, end: date },
                                        });
                                    }}
                                    start={filterFormValues?.dateUpdated?.start}
                                    end={filterFormValues?.dateUpdated?.end}
                                />
                            </InputBlock>
                            {isSchedule && (
                                <InputBlock legend={t("Scheduled Date")}>
                                    <LazyDateRange
                                        onStartChange={(date: string) => {
                                            setFilterFormValues({
                                                ...filterFormValues,
                                                dateScheduled: {
                                                    ...filterFormValues.dateScheduled,
                                                    start: date,
                                                },
                                            });
                                        }}
                                        onEndChange={(date: string) => {
                                            setFilterFormValues({
                                                ...filterFormValues,
                                                dateScheduled: { ...filterFormValues.dateScheduled, end: date },
                                            });
                                        }}
                                        start={filterFormValues?.dateScheduled?.start}
                                        end={filterFormValues?.dateScheduled?.end}
                                    />
                                </InputBlock>
                            )}
                            <InputBlock legend={t("Content Type")}>
                                <NestedSelect
                                    placeholder={t("Select...")}
                                    value={filterFormValues.recordType}
                                    options={[
                                        { value: "discussion", label: t("Post") },
                                        { value: "article", label: t("Article") },
                                        { value: "event", label: t("Event") },
                                    ]}
                                    onChange={(recordType: IDraft["recordType"]) => {
                                        setFilterFormValues({ recordType });
                                    }}
                                />
                            </InputBlock>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight className={cx({ [classes.filterFormFooter]: !inModal })}>
                        <Button
                            buttonType={inModal ? ButtonTypes.TEXT : ButtonTypes.STANDARD}
                            onClick={() => {
                                setFilterFormValues({});
                                onFilter({
                                    dateUpdated: undefined,
                                    dateScheduled: undefined,
                                    recordType: undefined,
                                });
                            }}
                        >
                            {t("Clear All")}
                        </Button>
                        <Button buttonType={inModal ? ButtonTypes.TEXT : ButtonTypes.STANDARD} submit>
                            {t("Filter")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}

interface IDraftsFilterProps {
    draftsQuery: DraftsApi.GetParams;
    isSchedule: boolean;
    onFilter: (filterValues: DraftsApi.GetParams) => void;
    inModal?: boolean;
    onClose?: () => void;
    initialFilters?: IDraftsFilterFormValues;
}

export function DraftsFilter(props: IDraftsFilterProps) {
    const { draftsQuery } = props;

    const [isFilterModalVisible, setIsFilterModalVisible] = useState(false);

    const initialFilters = {
        dateUpdated: {
            start: dateStringInUrlToDateRange(draftsQuery.dateUpdated ?? "").start,
            end: dateStringInUrlToDateRange(draftsQuery.dateUpdated ?? "").end,
        },
        dateScheduled: {
            start: dateStringInUrlToDateRange(draftsQuery.dateScheduled ?? "").start,
            end: dateStringInUrlToDateRange(draftsQuery.dateScheduled ?? "").end,
        },
        recordType: draftsQuery.recordType,
    };
    const hasFilters =
        initialFilters.recordType ||
        Object.values(initialFilters.dateUpdated).some((val) => val) ||
        Object.values(initialFilters.dateScheduled).some((val) => val);

    if (props.inModal) {
        return (
            <>
                <Button buttonType={ButtonTypes.TEXT} onClick={() => setIsFilterModalVisible(true)}>
                    <Row gap={8} align="center">
                        {t("Filters")}
                        <Icon icon={`filter-compact${hasFilters ? "-applied" : ""}`} />
                    </Row>
                </Button>
                <Modal
                    isVisible={isFilterModalVisible}
                    exitHandler={() => setIsFilterModalVisible(false)}
                    size={ModalSizes.SMALL}
                    titleID={"schedule-draft-modal"}
                >
                    <DraftsFilterForm
                        {...props}
                        initialFilters={initialFilters}
                        onFilter={(values) => {
                            props.onFilter(values);
                            setIsFilterModalVisible(false);
                        }}
                        onClose={() => setIsFilterModalVisible(false)}
                    />
                </Modal>
            </>
        );
    }
    return <DraftsFilterForm {...props} initialFilters={initialFilters} />;
}
