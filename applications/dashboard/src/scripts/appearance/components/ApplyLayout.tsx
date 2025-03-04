/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
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
    LayoutViewType,
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
import { JsonSchema, PartialSchemaDefinition } from "@vanilla/json-schema-forms";
import { useCallback, useEffect, useState } from "react";
import {
    getLayoutFeatureFlagKey,
    getLayoutTypeGroupLabel,
    getLayoutTypeLabel,
    getLayoutTypeSettingsLabel,
    getLayoutTypeSettingsUrl,
} from "@dashboard/appearance/components/layoutViewUtils";

/**
 * Type for the form state.
 */
interface IDefaultFormValues {
    applyOption: LayoutRecordType | "unassigned";
    subcommunityIDs?: Array<LayoutViewFragment["recordID"]>;
    categoryIDs?: Array<LayoutViewFragment["recordID"]>;
}

type FormValues = IDefaultFormValues & (IExternalApplyOptionFormValue | {});

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

    // This bit of code is to include apply options registered from external sources (e.g plugins) in our form
    const [externalApplyOptions, setExternalApplyOptions] = useState<IExternalApplyOption[]>([]);
    useEffect(() => {
        if (ApplyLayout.externalApplyOptionGenerators.length) {
            const generatedExternalApplyOptions = ApplyLayout.externalApplyOptionGenerators.map(
                (generator: IExternalApplyOptionGenerator) => {
                    return generator(layout.layoutViewType);
                },
            );

            // External options that should not be used for the current layoutViewType will return null
            const filteredGeneratedExternalApplyOptions = generatedExternalApplyOptions.filter(
                (option) => option !== null,
            );

            if (filteredGeneratedExternalApplyOptions.length) {
                setExternalApplyOptions(filteredGeneratedExternalApplyOptions);
                setFormValues({
                    ...getInitialValues(),
                    ...getExternalApplyOptionInitialValues(layout, filteredGeneratedExternalApplyOptions),
                });
            }
        }
    }, [ApplyLayout.externalApplyOptionGenerators]);

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

        // Add external apply option labels
        ...(externalApplyOptions.length &&
            externalApplyOptions.reduce((acc, option) => {
                return { ...acc, ...{ [option.recordType]: option.applyOptionLabel } };
            }, {})),
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
            // Add external apply option schemas
            ...(externalApplyOptions.length &&
                externalApplyOptions.reduce((acc, option) => {
                    return { ...acc, ...option.schema };
                }, {})),
        },
    };

    const getInitialValues = useCallback((): FormValues => {
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

    const [formValues, setFormValues] = useState<FormValues>(getInitialValues());

    function resetForm() {
        setFormValues({ ...getInitialValues(), ...getExternalApplyOptionInitialValues(layout, externalApplyOptions) });
    }

    const submitFormMutation = useMutation({
        mutationFn: async (formValues: FormValues) => {
            const layoutViewFragments: LayoutViewFragment[] = ((): LayoutViewFragment[] => {
                if (externalApplyOptions.length) {
                    const matchingApplyOption = externalApplyOptions.find(
                        (applyOption) => applyOption.recordType === formValues.applyOption,
                    );

                    if (matchingApplyOption) {
                        return (
                            formValues?.[matchingApplyOption.key]?.map((recordID) => {
                                return {
                                    recordID,
                                    recordType: matchingApplyOption.recordType,
                                };
                            }) ?? []
                        );
                    }
                }

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
                                        icon={<Icon icon="status-alert" />}
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
                                        icon={<Icon icon="status-alert" />}
                                        className={css({ marginTop: 18 })}
                                        type="warning"
                                        title={t("Unable to re-assign default template")}
                                        stringContents={t(
                                            "This layout is a template and is assigned as the default for all pages of this type. To remove it as the default or to assign it to specific pages, you must assign a different layout as the default.",
                                        )}
                                    />
                                )}

                                <DashboardSchemaForm
                                    forceVerticalLabels={true}
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

interface IExternalApplyOptionFormValue {
    [key: string]: Array<LayoutViewFragment["recordID"]>;
}

interface IExternalApplyOption {
    key: string;
    recordType: LayoutRecordType;
    applyOptionLabel: string;
    schema: Record<string, PartialSchemaDefinition<any>>;
}

type IExternalApplyOptionGenerator = (viewType: LayoutViewType) => IExternalApplyOption | null;

/**
 * Get initial values for external apply option
 */
function getExternalApplyOptionInitialValues(
    layout: ILayoutDetails,
    externaldApplyOptions: IExternalApplyOption[],
): IExternalApplyOptionFormValue {
    return Object.fromEntries(
        externaldApplyOptions.map((option) => {
            return [
                option.key,
                layout.layoutViews
                    .filter((layoutView) => {
                        return layoutView.recordType === option.recordType;
                    })
                    .map((layoutView) => {
                        return layoutView.recordID;
                    }),
            ];
        }),
    );
}

/** Hold external (e.g from plugins) apply options generator functions. */
ApplyLayout.externalApplyOptionGenerators = [] as IExternalApplyOptionGenerator[];

/** Register external (e.g from plugins) apply options generator functions */
ApplyLayout.registerExternalApplyOptionGenerator = (generator: IExternalApplyOptionGenerator) => {
    ApplyLayout.externalApplyOptionGenerators.push(generator);
};
