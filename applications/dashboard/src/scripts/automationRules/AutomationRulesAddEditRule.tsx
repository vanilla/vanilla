/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useMemo, useRef, useState } from "react";
import { t } from "@vanilla/i18n";
import { useFormik } from "formik";
import { AutomationRulesProvider, useAutomationRules } from "@dashboard/automationRules/AutomationRules.context";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import SmartLink from "@library/routing/links/SmartLink";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import Button from "@library/forms/Button";
import { EditIcon, ErrorIcon, LeftChevronIcon } from "@library/icons/common";
import { RouteComponentProps, useHistory } from "react-router";
import { useAddRecipe, useRecipe, useUpdateRecipe } from "@dashboard/automationRules/AutomationRules.hooks";
import { AutomationRuleFormValues } from "@dashboard/automationRules/AutomationRules.types";
import AutomationRulesSummary from "@dashboard/automationRules/AutomationRulesSummary";
import { useToast } from "@library/features/toaster/ToastContext";
import ModalConfirm from "@library/modal/ModalConfirm";
import {
    getTriggerActionFormSchema,
    loadingPlaceholder,
    mapApiValuesToFormValues,
    mapFormValuesToApiValues,
    getTriggerAdditionalSettings,
} from "@dashboard/automationRules/AutomationRules.utils";
import { IFieldError, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { cx } from "@emotion/css";
import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { IError } from "@library/errorPages/CoreErrorMessages";
import Message from "@library/messages/Message";
import ErrorMessages from "@library/forms/ErrorMessages";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { autoWidthInputClasses } from "@library/forms/AutoWidthInput.classes";
import { AutoWidthInput } from "@library/forms/AutoWidthInput";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { AutomationRulesDeleteRule } from "@dashboard/automationRules/AutomationRulesDeleteRule";
import { AutomationRulesPreview } from "@dashboard/automationRules/preview/AutomationRulesPreview";
import { useLastValue } from "@vanilla/react-utils";
import isEqual from "lodash/isEqual";
import { Icon } from "@vanilla/icons";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { ToolTip } from "@library/toolTip/ToolTip";
import { iconClasses } from "@library/icons/iconStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import { AutomationRulesRunOnce } from "@dashboard/automationRules/AutomationRulesRunOnce";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { stableObjectHash } from "@vanilla/utils";

export function AutomationRulesAddEditImpl(props: { automationRuleID?: string }) {
    const { automationRuleID } = props;
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
                // TODO: we should do a generic function to handle all keys from additionalSettings
                if (key === "triggerTimeLookBackLimit") {
                    err.errors["triggerTimeLookBackLimit"] = err.errors[key].map((e) => {
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
                    history.push("/settings/automation-rules");
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

    // small adjustment for trigger/action cross dependency, applying default values for some fields, resetting some action/trigger values if type is changed
    useEffect(() => {
        // reset actionType if not in trigger's triggerActions
        const shouldResetActionType =
            automationRulesCatalog &&
            values.trigger?.triggerType &&
            values.action?.actionType &&
            !automationRulesCatalog?.triggers[values.trigger?.triggerType].triggerActions.includes(
                values.action?.actionType,
            );
        if (shouldResetActionType) {
            setFieldValue("action.actionType", "");
        }

        // apply the default value postType if no value is set for time based triggers with postType
        // if we have already the postType value, double check with default values as we might have disabled one of the question/poll/idea plugins, so we need to adjust postType form values accordingly
        if (
            values.trigger?.triggerType &&
            ["staleDiscussionTrigger", "lastActiveDiscussionTrigger"].includes(values.trigger?.triggerType ?? "")
        ) {
            const triggerPostTypeDefaultValue =
                automationRulesCatalog?.triggers?.[values.trigger?.triggerType].schema?.properties?.postType?.default;
            if (!values.trigger?.triggerValue.postType) {
                setFieldValue("trigger.triggerValue.postType", triggerPostTypeDefaultValue);
            } else if (
                triggerPostTypeDefaultValue &&
                values.trigger?.triggerValue.postType?.length &&
                values.trigger?.triggerValue.postType.some(
                    (postType) => !triggerPostTypeDefaultValue.includes(postType),
                )
            ) {
                setFieldValue(
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
                setFieldValue("additionalSettings", {
                    triggerValue: {
                        applyToNewContentOnly: false,
                        triggerTimeLookBackLimit: undefined,
                    },
                });
            }
        }

        // special case when action value is categoryID, for both categoryFollowAction and moveToCategoryAction it is the same, so we need to reset the value when action type is changed
        if (
            !isEqual(values, initialValues) &&
            lastActionType !== values.action?.actionType &&
            (values.action?.actionType === "categoryFollowAction" ||
                values.action?.actionType === "moveToCategoryAction")
        ) {
            setFieldValue(
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
    ]);

    const schema = useMemo(() => {
        return getTriggerActionFormSchema(values, profileFields, automationRulesCatalog);
    }, [values, profileFields, automationRulesCatalog]);

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

    return (
        <>
            <DashboardHeaderBlock
                title={""}
                actionButtons={
                    <div className={classes.addEditHeader}>
                        <div className={classes.addEditHeaderItem}>
                            <Button
                                onClick={() => {
                                    if (dirty) {
                                        setShowConfirmExit(true);
                                    } else {
                                        history.push("/settings/automation-rules");
                                    }
                                }}
                                buttonType={ButtonTypes.TEXT}
                                className={classes.flexContainer()}
                            >
                                <LeftChevronIcon />
                                {t("Back")}
                            </Button>
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
                                    <Button
                                        buttonType={ButtonTypes.ICON}
                                        onClick={focusAndSelectAll}
                                        disabled={isRuleRunning}
                                    >
                                        <EditIcon small />
                                    </Button>
                                </div>
                            </span>
                        </ConditionalWrap>
                        <div className={cx(classes.flexContainer(true), classes.addEditHeaderItem)}>
                            <DropDown
                                flyoutType={FlyoutType.LIST}
                                key={stableObjectHash({ ...recipe?.recentDispatch })}
                            >
                                <AutomationRulesPreview
                                    formValues={values}
                                    isRuleRunning={isRuleRunning}
                                    schema={schema}
                                />
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
                                        {isRuleRunning ? (
                                            disabledDropdownItem("Delete")
                                        ) : (
                                            <AutomationRulesDeleteRule {...recipe} />
                                        )}
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
                                        buttonType={ButtonTypes.DASHBOARD_PRIMARY}
                                        onClick={() => {
                                            submitForm();
                                        }}
                                        disabled={isRuleRunning}
                                        className={cx({ [classes.disabled]: isRuleRunning })}
                                    >
                                        {isSubmitting ? <ButtonLoader /> : t("Save")}
                                    </Button>
                                </span>
                            </ConditionalWrap>
                        </div>
                    </div>
                }
            />
            <ModalConfirm
                isVisible={showConfirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    setShowConfirmExit(false);
                }}
                onConfirm={() => {
                    setShowConfirmExit(false);
                    history.push("/settings/automation-rules");
                }}
                confirmTitle={t("Exit")}
            >
                {t(
                    "You are leaving the editor without saving your changes. Are you sure you want to exit without saving?",
                )}
            </ModalConfirm>
            {topLevelErrors && topLevelErrors.length > 0 && (
                <div className={classes.padded(true)}>
                    <Message
                        type="error"
                        stringContents={topLevelErrors[0].message}
                        icon={<ErrorIcon />}
                        contents={<ErrorMessages errors={topLevelErrors} />}
                    />
                </div>
            )}
            <section className={dashboardClasses().extendRow}>
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
                loadingPlaceholder("addEdit")
            ) : (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                    }}
                >
                    <JsonSchemaForm
                        fieldErrors={fieldErrors}
                        schema={schema}
                        instance={values}
                        FormControlGroup={DashboardFormControlGroup}
                        FormControl={DashboardFormControl}
                        FormGroupWrapper={(props) => {
                            return (
                                <>
                                    {props.header && (
                                        <section className={dashboardClasses().extendRow}>
                                            <div
                                                className={cx(classes.sectionHeader, {
                                                    [classes.noBorderTop]: props.header !== "Trigger",
                                                })}
                                            >
                                                {t(props.header)}
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
            <DashboardHelpAsset>
                <h3>{t("Create/Edit Automation Rule").toLocaleUpperCase()}</h3>
                <p>
                    {t(
                        "Create or edit a single automation rule using triggers and actions. Set rules to run once or continually.",
                    )}
                </p>
                <SmartLink to={"https://success.vanillaforums.com/kb/articles/1569-automation-rules"}>
                    {t("See documentation for more information.")}
                </SmartLink>
            </DashboardHelpAsset>
        </>
    );
}

export default function AutomationRulesAddEdit(
    props: RouteComponentProps<{
        automationRuleID: string;
    }>,
) {
    const automationRuleID = props.match.params.automationRuleID;

    return (
        <AutomationRulesProvider>
            <AutomationRulesAddEditImpl automationRuleID={automationRuleID} />
        </AutomationRulesProvider>
    );
}
