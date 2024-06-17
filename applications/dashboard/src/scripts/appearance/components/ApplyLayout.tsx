/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardFormControl, DashboardFormControlGroup } from "@dashboard/forms/DashboardFormControl";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import WidgetSettingsFormGroupWrapper from "@dashboard/layout/editor/widgetSettings/WidgetSettingsFormGroupWrapper";
import { GLOBAL_LAYOUT_VIEW } from "@dashboard/layout/layoutSettings/LayoutSettings.constants";
import {
    getAllowedRecordTypesForLayout,
    useLayoutViewMutation,
} from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import {
    ILayoutDetails,
    LayoutRecordType,
    LayoutViewFragment,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { css } from "@emotion/css";
import { useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { useToast } from "@library/features/toaster/ToastContext";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import SmartLink from "@library/routing/links/SmartLink";
import { hasMultipleSiteSections, t } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { Icon } from "@vanilla/icons";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import React, { useCallback, useState } from "react";
import {
    getLayoutFeatureFlagKey,
    getLayoutTypeGroupLabel,
    getLayoutTypeLabel,
    getLayoutTypeSettingsLabel,
    getLayoutTypeSettingsUrl,
} from "./layoutViewUtils";

/**
 * Type for the form state.
 */
interface IFormValues {
    applyOption: LayoutRecordType | "unassigned";
    subcommunityIDs?: Array<LayoutViewFragment["recordID"]>;
    categoryIDs?: Array<LayoutViewFragment["recordID"]>;
}

interface IProps {
    /**
     * The layout to apply.
     */
    layout: ILayoutDetails;
    /**
     * Used for testing to force the modal open.
     */
    forceModalOpen?: boolean;
}

/**
 * Renders button that opens a modal with a form for applying a layout.
 */
export function ApplyLayout(props: IProps) {
    const { layout, forceModalOpen } = props;

    const featureConfigKey = getLayoutFeatureFlagKey(layout.layoutViewType);
    const configs = useConfigsByKeys([featureConfigKey]);
    const isLegacyLayoutEnabled = !(configs.data?.[featureConfigKey] ?? true);

    const layoutViewMutation = useLayoutViewMutation(layout);
    const toast = useToast();
    const [modalOpen, setModalOpen] = useState(forceModalOpen ?? false);

    function closeModal() {
        setModalOpen(false);
    }

    const layoutTypeLabel = getLayoutTypeLabel(layout.layoutViewType);

    // These layout types don't have "default" layouts per subcommunity
    // Because there is only one of them per subcommunity.
    const noSubDefaults = ["discussionList", "categoryList", "subcommunityHome"].includes(layout.layoutViewType);

    const choiceOptions = {
        unassigned: t("Unassigned"),
        [LayoutRecordType.GLOBAL]:
            layout.layoutViewType === "home" ? (
                t("Apply Layout to Site Home Page")
            ) : (
                <Translate source="Set as default layout for all <0 />." c0={<strong>{layoutTypeLabel}</strong>} />
            ),
        [LayoutRecordType.SUBCOMMUNITY]: noSubDefaults
            ? t("Apply to specific subcommunities.")
            : t("Set as default layout for specific subcommunities."),
        [LayoutRecordType.CATEGORY]: t("Apply to specific categories."),
    };

    const allowedRecordTypes = getAllowedRecordTypesForLayout(layout);

    const allowedChoices = Object.fromEntries(
        Object.entries(choiceOptions).filter(([key, value]) => {
            return allowedRecordTypes.includes(key as LayoutRecordType) || key === "unassigned";
        }),
    );

    const hasSubcommunities = hasMultipleSiteSections();

    const isDefaultAppliedTemplate =
        layout.isDefault &&
        [LayoutRecordType.GLOBAL, LayoutRecordType.ROOT].includes(layout.layoutViews[0]?.recordType);

    const schema: JsonSchema = {
        type: "object",
        properties: {
            applyOption: {
                type: "string",
                enum: Object.keys(choiceOptions),
                disabled: isDefaultAppliedTemplate,
                default: "unassigned",
                "x-control": {
                    inputType: "radio",
                    choices: {
                        staticOptions: allowedChoices,
                    },
                },
            },
            categoryIDs: {
                type: "array",
                items: {
                    type: "number",
                },
                "x-control": {
                    label: t("Categories"),
                    inputType: "dropDown",
                    multiple: true,
                    choices: {
                        api: {
                            searchUrl: getCategorySearchUrl(layout),
                            singleUrl: `/categories/%s`,
                            labelKey: "name",
                            valueKey: "categoryID",
                        },
                    },
                    conditions: [{ field: "applyOption", type: "string", const: LayoutRecordType.CATEGORY }],
                },
            },

            ...(hasSubcommunities && {
                subcommunityIDs: {
                    type: "array",
                    items: {
                        type: "number",
                    },
                    "x-control": {
                        label: t("Subcommunities"),
                        inputType: "dropDown",
                        multiple: true,
                        choices: {
                            api: {
                                searchUrl: `/subcommunities?name=%s`,
                                singleUrl: `/subcommunities/%s`,
                                labelKey: "name",
                                valueKey: "subcommunityID",
                                extraLabelKey: "locale",
                            },
                        },
                        conditions: [{ field: "applyOption", type: "string", const: LayoutRecordType.SUBCOMMUNITY }],
                    },
                },
            }),
        },
    };

    const getInitialValues = useCallback((): IFormValues => {
        let applyOption = layout.layoutViews?.[0]?.recordType ?? "unassigned";
        // Map the legacy option over.
        applyOption = applyOption === LayoutRecordType.ROOT ? LayoutRecordType.GLOBAL : applyOption;
        return {
            categoryIDs: layout.layoutViews
                .filter((layoutView) => layoutView.recordType === "category")
                .map((layoutView) => layoutView.recordID),
            subcommunityIDs: layout.layoutViews
                .filter((layoutView) => layoutView.recordType === LayoutRecordType.SUBCOMMUNITY)
                .map((layoutView) => layoutView.recordID),
            applyOption,
        };
    }, [layout]);

    const [formValues, setFormValues] = useState<IFormValues>(getInitialValues());

    function resetForm() {
        setFormValues(getInitialValues());
    }

    const submitFormMutation = useMutation({
        mutationFn: async (formValues: IFormValues) => {
            const layoutViewFragments: LayoutViewFragment[] = ((): LayoutViewFragment[] => {
                switch (formValues.applyOption) {
                    case LayoutRecordType.ROOT:
                    case LayoutRecordType.GLOBAL:
                        return [GLOBAL_LAYOUT_VIEW];
                    case LayoutRecordType.SUBCOMMUNITY:
                        return (
                            formValues.subcommunityIDs?.map((recordID) => {
                                return {
                                    recordID,
                                    recordType: LayoutRecordType.SUBCOMMUNITY,
                                };
                            }) ?? []
                        );
                    case LayoutRecordType.CATEGORY:
                        return (
                            formValues.categoryIDs?.map((recordID) => ({
                                recordID,
                                recordType: LayoutRecordType.CATEGORY,
                            })) ?? []
                        );
                    default:
                        return [];
                }
            })();

            await layoutViewMutation.mutateAsync(layoutViewFragments);
        },
        onSuccess: () => {
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Layout settings applied.")}</>,
            });

            resetForm();
        },
        onError: (e: IError) => {
            toast.addToast({
                autoDismiss: false,
                dismissible: true,
                body: <>{e.description}</>,
            });
        },
        onSettled: () => {
            setModalOpen(false);
        },
    });

    return (
        <>
            <DropDownItemButton
                onClick={() => {
                    setModalOpen(true);
                }}
            >
                {t("Apply")}
            </DropDownItemButton>
            <Modal isVisible={modalOpen} size={ModalSizes.MEDIUM} exitHandler={closeModal}>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submitFormMutation.mutate(formValues);
                    }}
                >
                    <Frame
                        header={<FrameHeader closeFrame={closeModal} title={t("Apply Layout")} />}
                        body={
                            <FrameBody>
                                {isLegacyLayoutEnabled && (
                                    <Message
                                        className={css({ marginTop: 18 })}
                                        type="warning"
                                        icon={<Icon icon="notification-alert" />}
                                        title={t("Legacy Layouts enabled")}
                                        stringContents={t(
                                            "Note this layout change will not be visible until you switch to custom layouts.",
                                        )}
                                        contents={
                                            <>
                                                <Translate
                                                    source="This layout change will not be visible until you switch your community to custom <0/> in the <1/> page."
                                                    c0={getLayoutTypeGroupLabel(layout.layoutViewType)}
                                                    c1={
                                                        <SmartLink to={getLayoutTypeSettingsUrl(layout.layoutViewType)}>
                                                            {getLayoutTypeSettingsLabel(layout.layoutViewType)}
                                                        </SmartLink>
                                                    }
                                                />{" "}
                                                <Translate
                                                    source={"To learn more, <0>see the documentation</0>."}
                                                    c0={(content) => (
                                                        <SmartLink
                                                            to={"https://success.vanillaforums.com/kb/articles/430"}
                                                        >
                                                            {content}
                                                        </SmartLink>
                                                    )}
                                                />
                                            </>
                                        }
                                    />
                                )}
                                {isDefaultAppliedTemplate && (
                                    <Message
                                        icon={<Icon icon="notification-alert" />}
                                        className={css({ marginTop: 18 })}
                                        type="warning"
                                        title={t("Unable to re-assign default template")}
                                        stringContents={t(
                                            "This layout is a template and is assigned as the default for all pages of this type. To remove it as the default or to assign it to specific pages, you must assign a different layout as the default.",
                                        )}
                                    />
                                )}
                                <JsonSchemaForm
                                    FormControl={DashboardFormControl}
                                    FormControlGroup={(props) => (
                                        <DashboardFormControlGroup {...props} labelType={DashboardLabelType.VERTICAL} />
                                    )}
                                    FormGroupWrapper={WidgetSettingsFormGroupWrapper}
                                    instance={formValues}
                                    onChange={setFormValues}
                                    schema={schema}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={closeModal}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    disabled={submitFormMutation.isLoading || isDefaultAppliedTemplate}
                                    submit
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {submitFormMutation.isLoading ? <ButtonLoader /> : t("Apply")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}

function getCategorySearchUrl(layout: ILayoutDetails) {
    switch (layout.layoutViewType) {
        case "discussionCategoryPage":
        case "nestedCategoryList":
            return `/categories/search?layoutViewType=${layout.layoutViewType}&query=%s`;

        default:
            return `/categories/search?query=%s`;
    }
}
