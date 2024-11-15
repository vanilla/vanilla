import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { IInterest, InterestFormValues, InterestQueryParams } from "@dashboard/interestsSettings/Interests.types";
import { getInterestFormValues, useSaveInterest } from "@dashboard/interestsSettings/InterestsSettings.hooks";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { ProfileFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import {
    getDefaultNestedOptions,
    getFilteredNestedOptions,
} from "@library/forms/nestedSelect/presets/CategoryDropdown";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@library/utility/appUtils";
import { IFieldError, SchemaFormBuilder } from "@vanilla/json-schema-forms";
import { useEffect, useMemo, useState } from "react";

interface IProps {
    interest?: IInterest;
    isVisible?: boolean;
    onClose: () => void;
    queryParams?: InterestQueryParams;
}

export function AddInterest(props: IProps) {
    const { isVisible = false, onClose, interest, queryParams } = props;
    const saveInterest = useSaveInterest(queryParams);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>();
    const toast = useToast();

    const [values, setValues] = useState<InterestFormValues | null>(getInterestFormValues(interest));

    useEffect(() => {
        setValues(getInterestFormValues(interest));
    }, [interest]);

    const handleSubmit = async () => {
        function createPayload(values: InterestFormValues) {
            const profileFieldApiNames = values?.profileFields ?? [];
            const profileFieldMapping = profileFieldApiNames.reduce((acc, apiName) => {
                return { ...acc, [apiName]: [values?.[apiName]] };
            }, {});
            return { ...values, profileFieldMapping };
        }

        try {
            if (values) {
                await saveInterest.mutateAsync(createPayload(values));
                onClose();
                toast.addToast({
                    autoDismiss: true,
                    body: <Translate source="You have successfully saved interest: <0/>" c0={values.name} />,
                });
            }
        } catch (error) {
            setFieldErrors(error.errors);
        }
    };

    const handleCancel = () => {
        setValues(null);
        setFieldErrors(undefined);
        onClose();
    };

    const [profileFieldDataByApiName, setProfileFieldDataByApiName] = useState<Record<string, any>>({});

    const { data: profileFieldData } = useProfileFields({
        enabled: true,
        formType: [ProfileFieldFormType.DROPDOWN, ProfileFieldFormType.TOKENS, ProfileFieldFormType.CHECKBOX],
    });

    useEffect(() => {
        if (profileFieldData && Array.isArray(profileFieldData) && profileFieldData.length > 0) {
            setProfileFieldDataByApiName((prev) => {
                const opts = profileFieldData.reduce((acc, pfOption) => {
                    return { ...acc, [pfOption.apiName]: pfOption };
                }, {});
                return { ...prev, ...opts };
            });
        }
    }, [profileFieldData]);

    const memoizedSchema = useMemo(() => {
        let schema = SchemaFormBuilder.create()
            .textBox("name", "Name of Interest", "A unique display name.")
            .required()
            .textBox(
                "apiName",
                "API Name",
                "A unique label name that cannot be changed once saved.",
                Boolean(interest?.interestID),
            )
            .required()
            .subHeading("Target Users")
            .checkBoxRight(
                "isDefault",
                "Target All Users",
                "Set this interest as default to suggest following its mapped categories and tags to all users.",
            )
            .withDefault(false)
            .selectLookup(
                "profileFields",
                "Profile Fields",
                "Only fields that are single checkbox, single-select dropdown, multi-select dropdown, or numeric dropdown are available. Once fields are selected, they'll appear below so you may choose specific answers for each profile field.",
                {
                    searchUrl: "/profile-fields?enabled=true&formType=dropdown,tokens,checkbox",
                    singleUrl: "/profile-fields/%s",
                    labelKey: "label",
                    valueKey: "apiName",
                    processOptions: (options) => {
                        setProfileFieldDataByApiName((prev) => {
                            const opts = options.reduce((acc, option) => {
                                return { ...acc, [option.data.apiName]: option.data };
                            }, {});
                            return { ...prev, ...opts };
                        });
                        return options;
                    },
                },
                true,
            )
            .withCondition({ field: "isDefault", type: "boolean", const: false, default: false });

        let schemaWithProfileFields = schema;

        if (Object.keys(profileFieldDataByApiName).length) {
            Object.keys(profileFieldDataByApiName).forEach((fieldName) => {
                if (values?.profileFields?.includes(fieldName)) {
                    const fieldData = profileFieldDataByApiName[fieldName];
                    schemaWithProfileFields = schemaWithProfileFields.selectStatic(
                        fieldName,
                        fieldData.label,
                        fieldData.description,
                        fieldData.dataType === "boolean"
                            ? [
                                  { value: "true", label: "True" },
                                  { value: "false", label: "False" },
                              ]
                            : fieldData.dropdownOptions?.map((option) => ({ value: option, label: option })),
                        true,
                    );
                }
            });
        }

        let suggestSchema = schemaWithProfileFields
            .subHeading("Content to Suggest")
            .selectLookup(
                "categoryIDs",
                "Categories",
                "Select a category to associate with this interest.",
                {
                    searchUrl: "/categories/search?query=%s",
                    singleUrl: "/categories/%s",
                    defaultListUrl: "/categories?outputFormat=flat&limit=50",
                    labelKey: "name",
                    valueKey: "categoryID",
                    processOptions: setValues?.["categoryIDs"]?.length
                        ? getFilteredNestedOptions
                        : getDefaultNestedOptions,
                },
                true,
            )
            .selectLookup(
                "tagIDs",
                "Tags",
                "Associate tags with this interest. We recommend 3-5 tags per interest.",
                {
                    searchUrl: "/tags?type=User&query=%s",
                    singleUrl: "/tags/%s",
                    labelKey: "name",
                    valueKey: "tagID",
                },
                true,
            )
            .getSchema();

        return suggestSchema;
    }, [values, profileFieldDataByApiName, interest]);

    return (
        <Modal isVisible={isVisible} size={ModalSizes.LARGE}>
            <form
                onSubmit={async (event) => {
                    event.preventDefault();
                    await handleSubmit();
                }}
            >
                <Frame
                    scrollable
                    header={
                        <FrameHeader
                            closeFrame={handleCancel}
                            title={interest ? t("Edit Interest") : t("Add Interest")}
                        />
                    }
                    body={
                        <FrameBody>
                            <DashboardSchemaForm
                                instance={values}
                                schema={memoizedSchema}
                                onChange={setValues}
                                fieldErrors={fieldErrors}
                                onBlur={(fieldName) => {
                                    if (values?.name.length) {
                                        const tmpApiName = values.name.toLowerCase().replace(/\s/g, "-");
                                        if (
                                            (fieldName === "name" && !values.apiName) ||
                                            values.apiName !== tmpApiName
                                        ) {
                                            setValues({ ...values, apiName: tmpApiName });
                                        }
                                    }
                                }}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT}
                                onClick={handleCancel}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                disabled={saveInterest.isLoading}
                                submit
                                className={frameFooterClasses().actionButton}
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                            >
                                {saveInterest.isLoading ? <ButtonLoader /> : t("Save")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}
