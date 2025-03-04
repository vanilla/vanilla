/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { AutomationRulesProvider, useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import {
    useAddRecipe,
    useGetActionDynamicSchema,
    useRecipe,
    useUpdateRecipe,
} from "@dashboard/automationRules/AutomationRules.hooks";
import { AutomationRuleFormValues } from "@dashboard/automationRules/AutomationRules.types";
import {
    getActionDynamicSchemaParams,
    getTriggerActionFormSchema,
    getTriggerAdditionalSettings,
    hasPostType,
    loadingPlaceholder,
    mapApiValuesToFormValues,
    mapFormValuesToApiValues,
} from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesDeleteRule } from "@dashboard/automationRules/AutomationRulesDeleteRule";
import { AutomationRulesRunOnce } from "@dashboard/automationRules/AutomationRulesRunOnce";
import AutomationRulesSummary from "@dashboard/automationRules/AutomationRulesSummary";
import { AutomationRulesPreviewModal } from "@dashboard/automationRules/preview/AutomationRulesPreviewModal";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { EditIcon, ErrorIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import ModalConfirm from "@library/modal/ModalConfirm";
import BackLink from "@library/routing/links/BackLink";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { IFieldError } from "@vanilla/json-schema-forms";
import { useLastValue } from "@vanilla/react-utils";
import { stableObjectHash } from "@vanilla/utils";
import { useFormik } from "formik";
import isEqual from "lodash/isEqual";
import { useEffect, useMemo, useRef, useState } from "react";
import { RouteComponentProps, useHistory } from "react-router";

export function AutomationRulesAddEdit(props: { automationRuleID?: string; isEscalationRulesMode?: boolean }) {
    const { automationRuleID, isEscalationRulesMode } = props;
    const [showConfirmExit, setShowConfirmExit] = useState(false);
    const [ruleName, setRuleName] = useState(automationRuleID ? "" : t("Rule Name"));
    const classes = automationRulesClasses();
    const toast = useToast();
    const history = useHistory();
    const editableRef = useRef<HTMLInputElement | null>();

    const [topLevelErrors, setTopLevelErrors] = useState<IError[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    const isEditing = !!automationRuleID;

    const focusAndSelectAll = (event?: any) => {
        if (editableRef.current && editableRef.current !== document.activeElement) {
            if (event) event.preventDefault();
            editableRef.current.focus();
            editableRef.current.select();
        }
    };

    const handleErrors = (err) => {
        // some adjustments for top level errors, if its not a per field error, or its the error for "name" field, find and show more specific error on top
        const excludedErrorFields = ["", "name"];
        const hasPerFieldErrors =
            err.errors && Object.keys(err.errors).find((key) => key && !excludedErrorFields.includes(key));
        const fieldNameForTopLevelError =
            !hasPerFieldErrors && err.errors && excludedErrorFields.find((field) => err.errors[field]);
        const topLevelErrorMessage =
            fieldNameForTopLevelError && err.errors[fieldNameForTopLevelError][0]
                ? err.errors[fieldNameForTopLevelError][0].message
                : err.message;

        setTopLevelErrors(
            topLevelErrorMessage
                ? [
                      {
                          message: topLevelErrorMessage,
                      },
                  ]
                : [],
        );

        // some adjustments for profile field and time threshold errors
        if (err.errors) {
            Object.keys(err.errors).forEach((key) => {
                if (key === values.trigger?.triggerValue.profileField) {
                    err.errors[key] = err.errors[key].map((e) => {
                        return {
                            ...e,
                            code: "missingField",
                        };
                    });
                }

                // let's adjust the error path if the field is in additionalSettings
                if (["triggerTimeLookBackLimit", "applyToNewContentOnly"].includes(key)) {
                    err.errors[key] = err.errors[key].map((e) => {
                        return {
                            ...e,
                            path: "additionalSettings.triggerValue",
                        };
                    });
                }
            });
        }
        setFieldErrors(err.errors ?? []);
    };
    const { profileFields, automationRulesCatalog } = useAutomationRules();

    const { recipe, isLoading } = useRecipe(parseInt(automationRuleID ?? ""), isEditing);
    const { mutateAsync: addRecipe } = useAddRecipe();
    const { mutateAsync: updateRecipe } = useUpdateRecipe(parseInt(automationRuleID ?? ""));

    const { values, submitForm, setValues, isSubmitting, dirty, setFieldValue, initialValues } =
        useFormik<AutomationRuleFormValues>({
            initialValues: mapApiValuesToFormValues(recipe, automationRulesCatalog, profileFields),
            onSubmit: async function (values) {
                try {
                    const addUpdateRecipeParams = { ...mapFormValuesToApiValues(values), name: ruleName };
                    automationRuleID
                        ? await updateRecipe(addUpdateRecipeParams)
                        : await addRecipe(addUpdateRecipeParams);
                    toast.addToast({
                        autoDismiss: true,
                        body: (
                            <>
                                {automationRuleID
                                    ? t("Automation rule successfully updated.")
                                    : t("Automation rule successfully created.")}
                            </>
                        ),
                    });
                    history.goBack();
                } catch (error) {
                    handleErrors(error);
                }
            },
            enableReinitialize: true,
        });

    useEffect(() => {
        if (recipe) {
            setRuleName(recipe.name);
        }
    }, [recipe]);

    const isRuleRunning =
        recipe?.recentDispatch?.dispatchStatus === "queued" || recipe?.recentDispatch?.dispatchStatus === "running";

    const lastActionType = useLastValue(values.action?.actionType);

    // dynamic schema for action
    const dynamicSchemaParams = getActionDynamicSchemaParams(values, automationRulesCatalog);
    const dynamicSchema = useGetActionDynamicSchema(dynamicSchemaParams);

    // small adjustment for trigger/action cross dependency, applying default values for some fields, resetting some action/trigger values if type is changed
    useEffect(() => {
        // reset actionType if not in trigger's triggerActions
        const shouldResetActionType =
            automationRulesCatalog &&
            values.trigger?.triggerType &&
            values.action?.actionType &&
            !automationRulesCatalog?.triggers[values.trigger?.triggerType]?.triggerActions.includes(
                values.action?.actionType,
            );
        if (shouldResetActionType) {
            void setFieldValue("action.actionType", "");
        }

        // Apply the default value postType if no value is set for time based triggers with postType
        // if we have already the postType value, double check with default values as we might have disabled one of the question/poll/idea plugins, so we need to adjust postType form values accordingly
        if (values.trigger?.triggerType && hasPostType(values.trigger?.triggerType, automationRulesCatalog)) {
            const triggerPostTypeDefaultValue =
                automationRulesCatalog?.triggers?.[values.trigger?.triggerType].schema?.properties?.postType?.default;
            if (!values.trigger?.triggerValue.postType) {
                void setFieldValue("trigger.triggerValue.postType", triggerPostTypeDefaultValue);
            } else if (
                triggerPostTypeDefaultValue &&
                values.trigger?.triggerValue.postType?.length &&
                values.trigger?.triggerValue.postType.some(
                    (postType) => !triggerPostTypeDefaultValue.includes(postType),
                )
            ) {
                void setFieldValue(
                    "trigger.triggerValue.postType",
                    values.trigger?.triggerValue.postType.filter((postType) =>
                        triggerPostTypeDefaultValue.includes(postType),
                    ),
                );
            }
        }

        // apply the default value for trigger additional settings for time based triggers
        const triggerAdditionalSettings = getTriggerAdditionalSettings(
            values.trigger?.triggerType,
            automationRulesCatalog,
        );
        if (triggerAdditionalSettings?.length) {
            const shouldSetDefaultAdditionalSettings =
                !values.additionalSettings &&
                (!isEditing ||
                    (recipe &&
                        !triggerAdditionalSettings?.some(
                            (key) => typeof recipe.trigger.triggerValue[key] !== "undefined",
                        )));

            if (shouldSetDefaultAdditionalSettings) {
                void setFieldValue("additionalSettings", {
                    triggerValue: {
                        applyToNewContentOnly: false,
                        triggerTimeLookBackLimit: undefined,
                    },
                });
            }
        }

        // action dynamic schema defaults
        if (!isEditing && dynamicSchemaParams && dynamicSchema.data) {
            const dynamicSchemaProperties = Object.keys(dynamicSchema.data.dynamicSchema?.properties ?? {});
            dynamicSchemaProperties.forEach((key) => {
                if (values.action?.actionValue[key] === undefined) {
                    void setFieldValue(
                        `action.actionValue.${key}`,
                        dynamicSchema.data?.dynamicSchema?.properties[key]?.default,
                    );
                }
            });
        }

        // special case when action value is categoryID, for both categoryFollowAction and moveToCategoryAction it is the same, so we need to reset the value when action type is changed
        if (
            !isEqual(values, initialValues) &&
            lastActionType !== values.action?.actionType &&
            (values.action?.actionType === "categoryFollowAction" ||
                values.action?.actionType === "moveToCategoryAction")
        ) {
            void setFieldValue(
                "action.actionValue.categoryID",
                values.action?.actionType === "categoryFollowAction" ? [] : "",
            );
        }
    }, [
        values.additionalSettings,
        values.trigger?.triggerType,
        values.action?.actionType,
        automationRulesCatalog,
        initialValues,
        recipe,
        isEditing,
        dynamicSchema.data,
        dynamicSchemaParams,
    ]);

    const schema = useMemo(() => {
        return getTriggerActionFormSchema(
            values,
            profileFields,
            automationRulesCatalog,
            dynamicSchemaParams ? dynamicSchema : undefined,
        );
    }, [values, profileFields, automationRulesCatalog, dynamicSchemaParams, dynamicSchema.data]);

    const disabledDropdownItem = (name: string) => (
        <ToolTip
            label={
                name == "Delete"
                    ? t("Rule may not be deleted while it is running")
                    : t("Rule may not be edited while it is running")
            }
        >
            <span>
                <DropDownItemButton disabled onClick={() => {}}>
                    {t(name)}
                </DropDownItemButton>
            </span>
        </ToolTip>
    );

    const rightPanelContent = (
        <>
            {isEscalationRulesMode ? (
                <h3>{t("Create/Edit Escalation Rule").toLocaleUpperCase()}</h3>
            ) : (
                <h3>{t("Create/Edit Automation Rule").toLocaleUpperCase()}</h3>
            )}
            <p>
                {t(
                    "Create or edit a single automation rule using triggers and actions. Set rules to run once or continually.",
                )}
            </p>
            <SmartLink to={"https://success.vanillaforums.com/kb/articles/1569-automation-rules"}>
                {t("See documentation for more information.")}
            </SmartLink>
        </>
    );

    const addEditHeaderActionButtons = (
        <div className={classes.addEditHeader}>
            <div className={classes.addEditHeaderItem}>
                <BackLink
                    visibleLabel={true}
                    onClick={dirty ? () => setShowConfirmExit(true) : undefined}
                    className={classes.leftGap(isEscalationRulesMode ? -20 : -4)}
                />
            </div>
            <ConditionalWrap
                component={ToolTip}
                condition={isRuleRunning}
                componentProps={{ label: t("Rule may not be edited while it is running") }}
            >
                <span>
                    <div className={cx(classes.flexContainer(), classes.addEditHeaderItem)}>
                        <AutoWidthInput
                            onChange={(event) => setRuleName(event.target.value)}
                            className={autoWidthInputClasses().themeInput}
                            ref={(ref) => (editableRef.current = ref)}
                            value={ruleName}
                            maxLength={100}
                            onKeyDown={(event) => {
                                if (event.key === "Enter") {
                                    event.preventDefault();
                                    (event.target as HTMLElement).blur();
                                }
                            }}
                            onMouseDown={focusAndSelectAll}
                            disabled={isRuleRunning}
                        />
                        <Button buttonType={ButtonTypes.ICON} onClick={focusAndSelectAll} disabled={isRuleRunning}>
                            <EditIcon small />
                        </Button>
                    </div>
                </span>
            </ConditionalWrap>
            <div className={cx(classes.flexContainer(true), classes.addEditHeaderItem)}>
                <DropDown flyoutType={FlyoutType.LIST} key={stableObjectHash({ ...recipe?.recentDispatch })}>
                    <AutomationRulesPreviewModal formValues={values} isRuleRunning={isRuleRunning} schema={schema} />
                    {isEditing && recipe && recipe.status === "inactive" && (
                        <AutomationRulesRunOnce
                            automationRuleID={recipe.automationRuleID}
                            formFieldsChanged={dirty}
                            isRunning={isRuleRunning}
                            formValues={values}
                            onConfirmSaveChanges={async () =>
                                await updateRecipe({ ...mapFormValuesToApiValues(values), name: ruleName })
                            }
                            onError={handleErrors}
                            schema={schema}
                        />
                    )}
                    {isEditing && (
                        <DropDownItemLink
                            to={`/settings/automation-rules/history?automationRuleID=${props.automationRuleID}`}
                        >
                            {t("History")}
                        </DropDownItemLink>
                    )}
                    <DropDownItemSeparator />
                    {isEditing && recipe && (
                        <>
                            {isRuleRunning ? disabledDropdownItem("Delete") : <AutomationRulesDeleteRule {...recipe} />}
                        </>
                    )}
                </DropDown>
                <ConditionalWrap
                    component={ToolTip}
                    condition={isRuleRunning}
                    componentProps={{ label: t("Rule may not be edited while it is running") }}
                >
                    <span>
                        <Button
                            buttonType={isEscalationRulesMode ? ButtonTypes.OUTLINE : ButtonTypes.DASHBOARD_PRIMARY}
                            onClick={() => {
                                void submitForm();
                            }}
                            disabled={isRuleRunning || (Boolean(dynamicSchemaParams) && dynamicSchema.isFetching)}
                            className={cx({
                                [classes.disabled]: isRuleRunning || (dynamicSchemaParams && dynamicSchema.isFetching),
                            })}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </span>
                </ConditionalWrap>
            </div>
        </div>
    );

    const addEditContent = (
        <>
            <ModalConfirm
                isVisible={showConfirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    setShowConfirmExit(false);
                }}
                onConfirm={() => {
                    setShowConfirmExit(false);
                    history.goBack();
                }}
                confirmTitle={t("Exit")}
            >
                {t(
                    "You are leaving the editor without saving your changes. Are you sure you want to exit without saving?",
                )}
            </ModalConfirm>
            {topLevelErrors && topLevelErrors.length > 0 && (
                <div className={classes.padded(!isEscalationRulesMode)}>
                    <Message
                        type="error"
                        stringContents={topLevelErrors[0].message}
                        icon={<ErrorIcon />}
                        contents={<ErrorMessages errors={topLevelErrors} />}
                    />
                </div>
            )}
            <section className={cx({ [dashboardClasses().extendRow]: !isEscalationRulesMode })}>
                <div className={cx(classes.sectionHeader, classes.noBorderTop)}>
                    {t("Summary")}
                    {recipe && isRuleRunning && (
                        <span className={cx(classes.flexContainer(), classes.runningStatusWrapper)}>
                            <Icon
                                icon="status-running"
                                size={"compact"}
                                className={cx(classes.runningStatusIcon, iconClasses().successFgColor)}
                            />
                            {t("Running")}
                        </span>
                    )}
                </div>
                <div className={classes.padded()}>
                    <AutomationRulesSummary
                        formValues={values}
                        isLoading={automationRuleID ? isLoading : false}
                        isEditing={isEditing}
                        isRuleRunning={isRuleRunning}
                    />
                </div>
            </section>
            {isLoading && isEditing ? (
                loadingPlaceholder("addEdit", isEscalationRulesMode)
            ) : (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                    }}
                    className={cx({ [classes.escalationRuleAddEditForm]: isEscalationRulesMode })}
                >
                    <DashboardSchemaForm
                        fieldErrors={fieldErrors}
                        schema={schema}
                        instance={values}
                        FormGroupWrapper={(props) => {
                            return (
                                <>
                                    {props.header && (
                                        <section
                                            className={cx({ [dashboardClasses().extendRow]: !isEscalationRulesMode })}
                                        >
                                            <div
                                                className={cx(classes.sectionHeader, {
                                                    [classes.noBorderTop]: props.header !== "Trigger",
                                                })}
                                            >
                                                {typeof props.header === "string" ? t(props.header) : props.header}
                                            </div>
                                        </section>
                                    )}
                                    {props.children}
                                </>
                            );
                        }}
                        disabled={isRuleRunning}
                        onChange={setValues}
                    />
                </form>
            )}
        </>
    );

    return isEscalationRulesMode ? (
        <ModerationAdminLayout
            titleBarContainerClassName={classes.escalationRuleAddEditTitleBar}
            actionsWrapperClassName={classes.escalationRuleAddEditTitleBarActionsWrapper}
            title={""}
            rightPanel={rightPanelContent}
            content={addEditContent}
            titleBarActions={addEditHeaderActionButtons}
        />
    ) : (
        <>
            <DashboardHeaderBlock title={""} actionButtons={addEditHeaderActionButtons} />
            {addEditContent}
            <DashboardHelpAsset>{rightPanelContent}</DashboardHelpAsset>
        </>
    );
}

export default function AutomationRulesAddEditPage(
    props: RouteComponentProps<{
        automationRuleID: string;
    }>,
) {
    const automationRuleID = props.match.params.automationRuleID;
    const isEscalationRulesMode = props.location.pathname.includes("dashboard/content/escalation-rules");

    return (
        <AutomationRulesProvider isEscalationRulesMode={isEscalationRulesMode}>
            <AutomationRulesAddEdit automationRuleID={automationRuleID} isEscalationRulesMode={isEscalationRulesMode} />
        </AutomationRulesProvider>
    );
}
