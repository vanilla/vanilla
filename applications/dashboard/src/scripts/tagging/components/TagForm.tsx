/*
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { ScopeType, TagFormValues } from "@dashboard/tagging/taggingSettings.types";
import { TagScopeService } from "@dashboard/tagging/TagScopeService";
import { IApiError } from "@library/@types/api/core";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ErrorMessages from "@library/forms/ErrorMessages";
import { ErrorIcon } from "@library/icons/common";
import { IFieldError, IJsonSchemaFormHandle, JSONSchemaType } from "@library/json-schema-forms";
import { SchemaFormBuilder } from "@library/json-schema-forms/SchemaBuilder";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { Message } from "@library/messages/Message";
import { t } from "@library/utility/appUtils";
import { extractSchemaDefaults, mapValidationErrorsToFormikErrors } from "@vanilla/json-schema-forms/src/utils";
import { slugify } from "@vanilla/utils";
import { useFormik } from "formik";
import { useEffect, useMemo, useRef, useState } from "react";

interface IProps {
    title: string;
    onClose: () => void;
    onSubmit: (values: TagFormValues) => Promise<any>;
    initialValues?: TagFormValues;
    scopeEnabled?: boolean;
}

export default function TagForm(props: IProps) {
    const { onClose, title, onSubmit, initialValues: initialValuesProp, scopeEnabled = false } = props;

    const subcommunityEnabled = scopeEnabled && Object.keys(TagScopeService.scopes).includes("siteSectionIDs");

    const isEditing = !!initialValuesProp;

    const classFrameFooter = frameFooterClasses();

    const schema = useMemo<JSONSchemaType<TagFormValues>>(() => {
        const builder = new SchemaFormBuilder();

        const nameInput = builder
            .textBox("name", t("Tag Name"), t("The visible name of the tag."))
            .withDefault("")
            .withMinLength(1)
            .required();

        if (isEditing) {
            nameInput.withTooltip(
                <Translate
                    source="<0>Renaming a tag</0> will update the tag name everywhere it's already been used — including on existing posts."
                    c0={(text) => <b>{text}</b>}
                />,
            );
        }

        builder
            .textBox("urlcode", t("URL Slug"), t("A unique identifier for the tag."), isEditing)
            .withPattern("^[a-z0-9-]+$")
            .withDefault("")
            .withMinLength(1)
            .required();

        const schema = builder.getSchema();

        if (scopeEnabled) {
            schema.properties!.scopeType = {
                type: "string",
                enum: [ScopeType.GLOBAL, ScopeType.SCOPED],
                default: ScopeType.GLOBAL,
            };

            let scopeDescription = (
                <p>
                    <Translate
                        source={
                            subcommunityEnabled
                                ? "If you don't <0>assign a subcommunity or category</0>, the tag will be available everywhere."
                                : "If you don't <0>assign a category</0>, the tag will be available everywhere."
                        }
                        c0={(text) => <b>{text}</b>}
                    />
                </p>
            );

            if (isEditing) {
                scopeDescription = (
                    <>
                        {scopeDescription}
                        <p>
                            <Translate
                                source={
                                    "Changing a tag's scope does <0>not</0> remove it from posts it's already been applied to — those posts will still retain the tag unless removed manually."
                                }
                                c0={(text) => <b>{text}</b>}
                            />
                        </p>
                    </>
                );
            }

            let scopeSchema = new SchemaFormBuilder().subHeading(t("Scope")).staticText(scopeDescription).getSchema();

            // Build scope properties object dynamically
            const scopeProperties: Record<string, any> = {};

            Object.entries(TagScopeService.scopes).forEach(([apiName, scope]) => {
                const label = t(scope.plural);
                const description = t(scope.description);
                const placeholder = t(scope.placeholder);

                const scopeBuilder = new SchemaFormBuilder();
                scopeBuilder.selectLookup(apiName, label, description, scope.filterLookupApi, true, placeholder);

                scopeProperties[apiName] = scopeBuilder.getSchema().properties![apiName];
            });

            scopeSchema = {
                ...scopeSchema,
                properties: {
                    ...scopeSchema.properties,
                    ...scopeProperties,
                },
                default: Object.fromEntries(Object.keys(TagScopeService.scopes).map((apiName) => [apiName, []])),
            };

            schema.properties!.scope = scopeSchema;
        }

        return schema;
    }, []);

    const schemaFormRef = useRef<IJsonSchemaFormHandle | null>(null);
    const [serverErrors, setServerErrors] = useState<IApiError | null>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, IFieldError[]>>({});

    // AIDEV-NOTE: Type guard to safely check if error is an API error
    function isApiError(error: unknown): error is IApiError {
        return (
            error !== null &&
            typeof error === "object" &&
            "response" in error &&
            error.response !== null &&
            typeof error.response === "object"
        );
    }

    const { values, setValues, setFieldValue, isSubmitting, dirty, submitForm, setFieldTouched, touched } =
        useFormik<TagFormValues>({
            initialValues: {
                ...extractSchemaDefaults(schema),
                ...initialValuesProp,
            },
            onSubmit: async (values) => {
                try {
                    await onSubmit(values);
                    setServerErrors(null);
                    setFieldErrors({});
                } catch (error) {
                    if (isApiError(error)) {
                        setServerErrors(error);
                        if (error.response?.data?.errors) {
                            setFieldErrors(error.response.data.errors);
                        }
                    } else {
                        setServerErrors({
                            message: error instanceof Error ? error.message : t("An unexpected error occurred"),
                        } as IApiError);
                    }
                }
            },
            validate: () => {
                const result = schemaFormRef?.current?.validate();
                return mapValidationErrorsToFormikErrors(result?.errors ?? []);
            },
            validateOnChange: false,
            enableReinitialize: true,
            onReset: () => {
                setServerErrors(null);
                setFieldErrors({});
            },
        });

    useEffect(() => {
        if (!scopeEnabled) {
            return;
        }
        const scopeType =
            Object.values(values?.scope ?? {}).filter((val) => val.length > 0).length > 0
                ? ScopeType.SCOPED
                : ScopeType.GLOBAL;
        void setFieldValue("scopeType", scopeType);
    }, [values]);

    useEffect(() => {
        if (isEditing || touched.urlcode) {
            return;
        }
        if (values.name) {
            void setFieldValue("urlcode", slugify(values.name));
        }
    }, [values.name]);

    return (
        <form
            role="form"
            onSubmit={async (e) => {
                e.preventDefault();
                await submitForm();
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={onClose} title={title} />}
                body={
                    <FrameBody hasVerticalPadding>
                        {serverErrors && (
                            <Message
                                type="error"
                                stringContents={serverErrors.message ?? t("Validation Error")}
                                icon={<ErrorIcon />}
                                contents={<ErrorMessages errors={[serverErrors]} />}
                            />
                        )}
                        <DashboardSchemaForm
                            onBlur={(fieldName) => {
                                void setFieldTouched(fieldName, true);
                            }}
                            fieldErrors={fieldErrors}
                            schema={schema}
                            instance={values}
                            onChange={setValues}
                        />
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight={true}>
                        <Button
                            className={classFrameFooter.actionButton}
                            buttonType={ButtonTypes.TEXT}
                            onClick={() => {
                                onClose();
                            }}
                            disabled={isSubmitting}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            disabled={!dirty || isSubmitting}
                            className={classFrameFooter.actionButton}
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
