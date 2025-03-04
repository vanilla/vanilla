/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import {
    IAutomationRulesHistoryFilter,
    IGetAutomationRuleDispatchesParams,
} from "@dashboard/automationRules/AutomationRules.types";
import Button from "@library/forms/Button";
import InputBlock from "@library/forms/InputBlock";
import DateRange from "@library/forms/DateRange";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { Icon } from "@vanilla/icons";
import { css, cx } from "@emotion/css";
import { useStackingContext } from "@vanilla/react-utils";
import { automationRulesHistoryClasses } from "@dashboard/automationRules/history/AutomationRulesHistory.classes";
import { dateRangeToString } from "@library/search/SearchUtils";

interface IProps {
    filter: IAutomationRulesHistoryFilter;
    updateQuery: (newFilter: IGetAutomationRuleDispatchesParams) => void;
    title: string;
}

export default function AutomationRulesHistoryDateFilter(props: IProps) {
    const { filter, updateQuery, title } = props;
    const [isVisible, setIsVisible] = useState(false);
    const [dateRange, setDateRange] = useState<{ start: string | undefined; end: string | undefined }>({
        start: filter.dateUpdated?.start,
        end: filter.dateUpdated?.end,
    });

    const classes = automationRulesHistoryClasses();

    const { zIndex } = useStackingContext();

    const filterValue = title === "Updated" ? filter.dateUpdated : filter.dateFinished;

    return (
        <div className={cx(automationRulesClasses().flexContainer(), automationRulesClasses().rightGap())}>
            <span className={automationRulesClasses().noWrap}>
                {`${t(title)}: `}
                {filterValue?.start && `${t("From")} ${filterValue?.start} `}
                {filterValue?.end && `${t("to")} ${filterValue?.end} `}
            </span>
            <Button
                buttonType={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    setDateRange({ start: filterValue?.start, end: filterValue?.end });
                    setIsVisible(true);
                }}
                className={classes.dateIcon}
            >
                <Icon icon="meta-events" />
            </Button>
            <Modal
                isVisible={isVisible}
                exitHandler={() => setIsVisible(false)}
                size={ModalSizes.SMALL}
                noFocusOnExit
                titleID="automationRulesHistory_FilterDateModal"
            >
                <form
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        updateQuery(
                            title === "Updated"
                                ? { dateUpdated: dateRangeToString(dateRange) }
                                : { dateFinished: dateRangeToString(dateRange) },
                        );
                        setIsVisible(false);
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"automationRulesHistory_FilterDateModal"}
                                closeFrame={() => setIsVisible(false)}
                                title={title}
                            />
                        }
                        body={
                            <FrameBody>
                                <div>
                                    <InputBlock className={automationRulesClasses().padded(true)}>
                                        <DateRange
                                            onStartChange={(value) => {
                                                setDateRange({ ...dateRange, start: value });
                                            }}
                                            onEndChange={(value) => {
                                                setDateRange({ ...dateRange, end: value });
                                            }}
                                            start={dateRange.start}
                                            end={dateRange.end}
                                            datePickerDropdownClassName={css({
                                                zIndex: zIndex + 1,
                                            })}
                                        />
                                    </InputBlock>
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        setDateRange({ start: undefined, end: undefined });
                                    }}
                                >
                                    {t("Clear All")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT}>
                                    {t("Filter")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </div>
    );
}
