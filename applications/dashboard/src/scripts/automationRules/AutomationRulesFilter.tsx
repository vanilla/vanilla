/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState } from "react";
import { t } from "@vanilla/i18n";
import { ToolTip } from "@library/toolTip/ToolTip";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import Button from "@library/forms/Button";
import { Icon } from "@vanilla/icons";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ClearIcon } from "@vanilla/ui/src/forms/shared/ClearIcon";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { cx } from "@emotion/css";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { IAutomationRulesFilterValues } from "@dashboard/automationRules/AutomationRules.types";
import { compare } from "@vanilla/utils";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";

export function AutomationRulesFilter(props: {
    onFilter: (newFilters: IAutomationRulesFilterValues) => void;
    filters: IAutomationRulesFilterValues;
}) {
    const { filters, onFilter } = props;
    const [isVisible, setIsVisible] = useState(false);
    const [localFilters, setLocalFilters] = useState<IAutomationRulesFilterValues>(filters ?? {});

    const classes = automationRulesClasses();

    const { automationRulesCatalog } = useAutomationRules();

    const filtersSchema: JsonSchema<IAutomationRulesFilterValues> = {
        type: "object",
        description: "Trigger Schema",
        properties: {
            trigger: {
                type: "string",
                enum: Object.keys(automationRulesCatalog?.triggers ?? {}),
                "x-control": {
                    label: t("Trigger"),
                    inputType: "dropDown",
                    choices: {
                        staticOptions: Object.fromEntries(
                            Object.keys(automationRulesCatalog?.triggers ?? {})
                                .map((trigger) => [trigger, automationRulesCatalog?.triggers[trigger]?.name])
                                .sort((a, b) => compare(a[1], b[1])),
                        ),
                    },
                    labelType: DashboardLabelType.VERTICAL,
                },
            },
            action: {
                type: "string",
                enum: Object.keys(automationRulesCatalog?.actions ?? {}),
                "x-control": {
                    label: t("Action"),
                    inputType: "dropDown",
                    choices: {
                        staticOptions: Object.fromEntries(
                            Object.keys(automationRulesCatalog?.actions ?? {})
                                .map((action) => [action, automationRulesCatalog?.actions[action].name])
                                .sort((a, b) => compare(a[1], b[1])),
                        ),
                    },
                    labelType: DashboardLabelType.VERTICAL,
                },
            },
            status: {
                type: "string",
                description: "hey enabled description",
                enum: ["active", "inactive"],
                "x-control": {
                    label: "Enabled/Disabled",
                    inputType: "dropDown",
                    choices: {
                        staticOptions: { active: "Yes", inactive: "No" },
                    },
                    labelType: DashboardLabelType.VERTICAL,
                },
            },
        },
    };

    const isFilterApplied = Object.keys(filters ?? {}).some((filter) => filters[filter]);

    return (
        <div className={userManagementClasses().filterButtonsContainer}>
            <ToolTip label={t("Filter")} customWidth={60}>
                <span>
                    <Button
                        buttonType={ButtonTypes.ICON}
                        onClick={() => setIsVisible(!isVisible)}
                        className={userManagementClasses().actionButton}
                        aria-label="Filter"
                    >
                        <Icon icon={`search-filter${isFilterApplied ? "-applied" : ""}`} />
                    </Button>
                </span>
            </ToolTip>
            <Modal
                isVisible={isVisible}
                exitHandler={() => {
                    setIsVisible(false);
                    setLocalFilters(filters);
                }}
                size={ModalSizes.SMALL}
                titleID={"automation-rules-filter-modal"}
                noFocusOnExit
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        onFilter(localFilters);
                        setIsVisible(false);
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"automation-rules-filter-modal"}
                                closeFrame={() => {
                                    setIsVisible(false);
                                    setLocalFilters(filters);
                                }}
                                title={t("Filter Automation Rules")}
                            />
                        }
                        body={
                            <FrameBody>
                                <div className={cx(frameBodyClasses().contents)}>
                                    <DashboardSchemaForm
                                        schema={filtersSchema}
                                        instance={localFilters ?? {}}
                                        onChange={setLocalFilters}
                                    />
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter className={classes.spaceBetween}>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        setLocalFilters({});
                                    }}
                                >
                                    {t("Clear All")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {t("Filter")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
            {isFilterApplied && (
                <ToolTip label={t("Clear all filters")}>
                    <span>
                        <Button
                            buttonType={ButtonTypes.ICON_COMPACT}
                            onClick={() => {
                                setLocalFilters({});
                                onFilter({});
                            }}
                            className={userManagementClasses().clearFilterButton}
                        >
                            <ClearIcon />
                        </Button>
                    </span>
                </ToolTip>
            )}
        </div>
    );
}
