import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { IInterest, InterestFormValues } from "@dashboard/interestsSettings/Interests.types";
import { useSaveInterest } from "@dashboard/interestsSettings/InterestsSettings.hooks";
import { useProfileFields } from "@dashboard/userProfiles/state/UserProfiles.hooks";
import { CreatableFieldFormType } from "@dashboard/userProfiles/types/UserProfiles.types";
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
import { Icon } from "@vanilla/icons";
import { slugify } from "@vanilla/utils";

interface IProps {
    title: string;
    initialValues?: InterestFormValues;
    onSubmit: (values: InterestFormValues) => Promise<void>;
    onClose: () => void;
    onSuccess?: () => Promise<void>;
}

const INITIAL_FORM_VALUES: InterestFormValues = {
    apiName: "",
    name: "",
    profileFields: [],
    categoryIDs: [],
    tagIDs: [],
    isDefault: false,
};

function getInterestFormValues(interest: IInterest): InterestFormValues {
    const profileFieldMapping = Object.fromEntries(
        (interest.profileFields ?? []).map((field) => [field.apiName, field.mappedValue]),
    );

    return {
        interestID: interest.interestID,
        apiName: interest.apiName,
        name: interest.name,
        isDefault: interest.isDefault ?? false,
        profileFields: Object.keys(interest.profileFieldMapping ?? {}),
        categoryIDs: interest.categoryIDs ?? [],
        tagIDs: interest.tagIDs ?? [],
        ...profileFieldMapping,
    };
}

function InterestForm(props: IProps) {
    const { title, initialValues = INITIAL_FORM_VALUES, onClose } = props;
    const isEditing = Boolean(initialValues?.interestID);

    const [shouldAssignApiName, setShouldAssignApiName] = useState(!isEditing);
    const [values, setValues] = useState<InterestFormValues>(initialValues);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]> | undefined>(undefined);

    async function submitForm() {
        setIsSubmitting(true);
        setFieldErrors(undefined);
        try {
            await props.onSubmit(values);
        } catch (e) {
            if (e.errors) {
                setFieldErrors(e.errors);
            }
        }
        setIsSubmitting(false);
    }

    const [profileFieldDataByApiName, setProfileFieldDataByApiName] = useState<Record<string, any>>({});

    const { data: profileFieldData } = useProfileFields({
        enabled: true,
        formType: [CreatableFieldFormType.DROPDOWN, CreatableFieldFormType.TOKENS, CreatableFieldFormType.CHECKBOX],
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
            .textBox("apiName", "API Name", "A unique label name that cannot be changed once saved.", isEditing)
            .required()
            .subHeading("Target Users")
            .checkBoxRight(
                "isDefault",
                "Default Interest - Target All Users",
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
            .staticText("Content that matches any selected category OR tag will be recommended.")
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
    }, [isEditing, values, profileFieldDataByApiName]);

    return (
        <form
            role="form"
            onSubmit={async (event) => {
                event.preventDefault();
                await submitForm();
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={onClose} title={title} />}
                body={
                    <FrameBody>
                        <DashboardSchemaForm
                            instance={values}
                            schema={memoizedSchema}
                            onChange={(values) => {
                                let newValues: InterestFormValues = values();
                                if (values()["name"] && shouldAssignApiName) {
                                    newValues = { ...newValues, apiName: slugify(values()["name"]) };
                                }
                                if (values()["apiName"]) {
                                    setShouldAssignApiName(false);
                                }
                                setValues((oldValues) => ({
                                    ...oldValues,
                                    ...newValues,
                                }));
                            }}
                            fieldErrors={fieldErrors}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            className={frameFooterClasses().actionButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={onClose}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            disabled={isSubmitting}
                            submit
                            className={frameFooterClasses().actionButton}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("Save")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}

export function AddInterest(props: { onSuccess?: () => Promise<void>; forceVisible?: boolean }) {
    const { onSuccess } = props;
    const [isVisible, setIsVisible] = useState(props?.forceVisible ?? false);
    const toast = useToast();

    function closeModal() {
        setIsVisible(false);
    }

    const { mutateAsync: saveInterest } = useSaveInterest();

    async function handleSubmit(values: InterestFormValues) {
        await saveInterest(createPayload(values));
        await onSuccess?.();
        closeModal();
        toast.addToast({
            autoDismiss: true,
            body: <Translate source="You have successfully saved interest: <0/>" c0={values.name} />,
        });
    }

    return (
        <>
            <Button buttonType={ButtonTypes.DASHBOARD_PRIMARY} onClick={() => setIsVisible(true)}>
                {t("Add Interest")}
            </Button>
            <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={closeModal}>
                {<InterestForm title={t("Add Interest")} onSubmit={handleSubmit} onClose={closeModal} />}
            </Modal>
        </>
    );
}

export function EditInterest(props: { interest: IInterest; onSuccess?: () => Promise<void> }) {
    const { interest, onSuccess } = props;
    const [isVisible, setIsVisible] = useState(false);

    const toast = useToast();

    function closeModal() {
        setIsVisible(false);
    }

    const { mutateAsync: saveInterest } = useSaveInterest();

    async function handleSubmit(values: InterestFormValues) {
        await saveInterest(createPayload(values));
        await onSuccess?.();
        closeModal();
        toast.addToast({
            autoDismiss: true,
            body: <Translate source="You have successfully saved interest: <0/>" c0={values.name} />,
        });
    }

    return (
        <>
            <Button
                buttonType={ButtonTypes.ICON_COMPACT}
                onClick={() => {
                    setIsVisible(true);
                }}
            >
                <Icon icon="edit" />
            </Button>
            <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={closeModal}>
                <InterestForm
                    title={t("Edit Interest")}
                    initialValues={getInterestFormValues(interest)}
                    onSubmit={handleSubmit}
                    onClose={closeModal}
                />
            </Modal>
        </>
    );
}

function createPayload(values: InterestFormValues) {
    const profileFieldApiNames = values?.profileFields ?? [];
    const profileFieldMapping = profileFieldApiNames.reduce((acc, apiName) => {
        return { ...acc, [apiName]: values?.[apiName] ?? [] };
    }, {});
    return { ...values, profileFieldMapping };
}
