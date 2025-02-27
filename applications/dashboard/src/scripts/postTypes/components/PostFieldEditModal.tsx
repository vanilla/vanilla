import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { postFieldEditModalClasses } from "@dashboard/postTypes/components/postFieldEditModal.classes";
import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { PostField, PostType } from "@dashboard/postTypes/postType.types";
import { usePostTypeEdit } from "@dashboard/postTypes/PostTypeEditContext";
import { fieldVisibilityAsOptions, formTypeAsOptions, mapFormTypeToDataType } from "@dashboard/postTypes/utils";
import { css, cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { IPickerOption, SchemaFormBuilder } from "@library/json-schema-forms";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { slugify } from "@vanilla/utils";
import { useEffect, useMemo, useState } from "react";

interface IProps {
    postTypeID: PostType["postTypeID"] | null;
    postField: PostField | null;
    isVisible: boolean;
    onConfirm: (newField: Partial<PostField>) => void;
    onCancel: () => void;
}

export function PostFieldEditModal(props: IProps) {
    const { postTypeID, postField, isVisible, onConfirm, onCancel } = props;
    const classes = postFieldEditModalClasses();
    const { allPostFields } = usePostTypeEdit();
    const allPostTypesQuery = usePostTypeQuery();
    const postFieldsAsOptions: IPickerOption[] = allPostFields
        .filter((field) => {
            return !field.postTypeIDs?.includes(postTypeID ?? "");
        })
        .map((field) => {
            const option: IPickerOption = {
                label: field.label,
                value: field.postFieldID,
            };
            return option;
        });

    const [values, setValues] = useState<Partial<PostField> | null>(postField);
    const [shouldCreateApiLabel, setShouldCreateApiLabel] = useState(!props.postField);
    const [showConfirmExit, setShowConfirmExit] = useState(false);
    const [isDirty, setIsDirty] = useState(false);
    const [fieldSelectorValue, setFieldSelectorValue] = useState({
        creationType: postField ? "linked" : "new",
        linkedPostField: postField?.postFieldID,
    });

    const isGlobalDisabled = fieldSelectorValue.creationType === "linked" && !fieldSelectorValue.linkedPostField;

    const showBanner = useMemo(() => {
        const isLinked = fieldSelectorValue.creationType === "linked";
        const hasLinkedField = fieldSelectorValue.linkedPostField;
        const linkedField = allPostFields.find((field) => field.postFieldID === fieldSelectorValue.linkedPostField);
        const pf = postField ?? linkedField;
        const hasMultiplePostTypeAssociations = pf && (pf?.postTypeIDs ?? []).length > 0;
        return isLinked && hasLinkedField && hasMultiplePostTypeAssociations;
    }, [allPostFields, fieldSelectorValue.creationType, fieldSelectorValue.linkedPostField, postField]);

    const impactedPostTypes = useMemo(() => {
        if (showBanner && allPostTypesQuery.data) {
            const linkedField = allPostFields.find((field) => field.postFieldID === fieldSelectorValue.linkedPostField);
            const pf = postField ?? linkedField;
            return pf?.postTypeIDs
                ?.filter((ptID) => ptID != postTypeID)
                .map((ptID) => {
                    return {
                        label: allPostTypesQuery.data.find((postType) => postType.postTypeID === ptID)?.name,
                        url: `/settings/post-types/edit/${ptID}`,
                    };
                });
        }
        return [];
    }, [showBanner, allPostTypesQuery.data]);

    const setFormValues = (postField: Partial<PostField>) => {
        const newValues = {
            ...postField,
            ...(postField?.dropdownOptions && { dropdownOptions: postField?.dropdownOptions?.join("\n") }),
        } as Partial<PostField>;
        setValues(newValues);
    };

    useEffect(() => {
        if (isVisible && postField) {
            setFieldSelectorValue({
                creationType: "linked",
                linkedPostField: postField?.postFieldID,
            });
            setFormValues(postField);
            setShouldCreateApiLabel(false);
        } else {
            setFieldSelectorValue({
                creationType: "new",
                linkedPostField: undefined,
            });
        }
    }, [isVisible, postField]);

    useEffect(() => {
        if (fieldSelectorValue.linkedPostField) {
            const linkedField = allPostFields.find((field) => field.postFieldID === fieldSelectorValue.linkedPostField);
            if (linkedField) {
                setFormValues(postField ?? linkedField);
            }
        }
    }, [fieldSelectorValue]);

    const resetAndClose = () => {
        setValues(null);
        setIsDirty(false);
        onCancel();
    };

    const handleClose = () => {
        if (isDirty) {
            setShowConfirmExit(true);
        } else {
            setIsDirty(false);
            resetAndClose();
        }
    };

    const handleSubmit = () => {
        if (values) {
            const payload = {
                ...values,
                ...(values.formType && { dataType: mapFormTypeToDataType(values.formType) }),
                ...(values.dropdownOptions && {
                    dropdownOptions: `${values.dropdownOptions}`.split("\n"),
                }),
            };
            onConfirm(payload);
        }
        resetAndClose();
    };

    const schema = new SchemaFormBuilder()
        .textBox("label", "Field Label", "The name of the post field.", isGlobalDisabled)
        .required()
        .textBox(
            "postFieldID",
            "API Label",
            "The unique identifier for your custom field. Once selected, this cannot be changed.",
            !!props.postField || isGlobalDisabled,
        )
        .required()
        .textArea(
            "description",
            "Description",
            "Provide helpful information or guidelines to users creating a post of this type. ",
            isGlobalDisabled,
        )
        .dropdown(
            "formType",
            "Form Type",
            "The type of form element to use for this field.",
            formTypeAsOptions(),
            isGlobalDisabled,
        )
        .required()
        .textArea(
            "dropdownOptions",
            "Options",
            "A list of options for the dropdown field. One per line.",
            isGlobalDisabled,
        )
        .withCondition({ field: "formType", enum: ["dropdown", "tokens"], default: "" })
        .required()
        .dropdown(
            "visibility",
            "Visibility",
            "The visibility of the field.",
            fieldVisibilityAsOptions(),
            isGlobalDisabled,
        )
        .required()
        .checkBoxRight(
            "isRequired",
            "Required",
            "If this field must be populated in order to make a post of this type.",
            isGlobalDisabled,
        )
        .getSchema();

    const fieldSelectionSchema = new SchemaFormBuilder()
        .dropdown(
            "creationType",
            "Creation Type",
            "If you wish to create a new post field or link an existing field",
            [
                {
                    label: "Create New Field",
                    value: "new",
                },
                {
                    label: "Link Existing Field",
                    value: "linked",
                },
            ],
            !!postField?.postFieldID,
        )
        .required()
        .dropdown(
            "linkedPostField",
            "Post Field",
            "The post field you wish to link to this post type",
            [...postFieldsAsOptions],
            !!postField?.postFieldID,
        )
        .withCondition({ field: "creationType", const: "linked", default: "" })
        .required()
        .getSchema();

    return (
        <>
            <Modal
                isVisible={isVisible}
                size={ModalSizes.LARGE}
                exitHandler={() => handleClose}
                titleID={"editPostField"}
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        handleSubmit();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={handleClose}
                                title={postField ? t("Edit Post Field") : t("Add Post Field")}
                                titleID={"editPostField"}
                            />
                        }
                        body={
                            <FrameBody>
                                <>
                                    {postFieldsAsOptions.length > 0 && (
                                        <DashboardSchemaForm
                                            schema={fieldSelectionSchema}
                                            instance={fieldSelectorValue}
                                            onChange={setFieldSelectorValue}
                                        />
                                    )}
                                </>
                                <>
                                    {showBanner && (
                                        <Message
                                            className={cx(classes.banner, dashboardClasses().extendRow)}
                                            type="warning"
                                            stringContents={t(
                                                "Changes to this field will affect the following post types",
                                            )}
                                            contents={
                                                <>
                                                    {impactedPostTypes && impactedPostTypes?.length > 0 ? (
                                                        <Translate
                                                            source={
                                                                "Changes to this field will affect the following post types: <0></0>"
                                                            }
                                                            c0={(_) => (
                                                                <>
                                                                    {impactedPostTypes.map(({ label, url }) => (
                                                                        <span key={label}>
                                                                            <SmartLink to={url}>{label}</SmartLink>{" "}
                                                                        </span>
                                                                    ))}
                                                                </>
                                                            )}
                                                        />
                                                    ) : (
                                                        <>
                                                            {t("Changes to this field will only affect this post type")}
                                                        </>
                                                    )}
                                                </>
                                            }
                                        />
                                    )}

                                    <DashboardSchemaForm
                                        schema={schema}
                                        instance={values}
                                        onChange={(values) => {
                                            setIsDirty(true);
                                            if (values()["postFieldID"]) {
                                                setShouldCreateApiLabel(false);
                                            }
                                            if (values()["label"] && shouldCreateApiLabel) {
                                                let newValues = values();
                                                newValues = {
                                                    ...newValues,
                                                    postFieldID: slugify(newValues["label"]),
                                                };
                                                setValues((prev) => ({ ...prev, ...newValues }));
                                            } else {
                                                setValues((prev) => ({ ...prev, ...values() }));
                                            }
                                        }}
                                    />
                                </>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={handleClose}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    submit
                                    className={frameFooterClasses().actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {postField ? t("Update") : t("Add")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
            <ModalConfirm
                isVisible={showConfirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => setShowConfirmExit(false)}
                onConfirm={() => {
                    setIsDirty(false);
                    setShowConfirmExit(false);
                    resetAndClose();
                }}
                confirmTitle={t("Discard")}
                bodyClassName={css({ justifyContent: "start" })}
            >
                {t("You have unsaved changes. Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}
