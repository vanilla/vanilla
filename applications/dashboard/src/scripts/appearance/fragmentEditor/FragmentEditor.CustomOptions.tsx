/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useFragmentEditor } from "@dashboard/appearance/fragmentEditor/FragmentEditor.context";
import {
    choicesStringToArray,
    customFieldToSchema,
    FragmentControlType,
    getControlTypeOptions,
    schemaToCustomField,
    type ICustomField,
} from "@dashboard/appearance/fragmentEditor/FragmentEditorSchemaUtils";
import { DashboardFormSubheading } from "@dashboard/forms/DashboardFormSubheading";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import {
    closestCenter,
    defaultDropAnimationSideEffects,
    DndContext,
    DragOverlay,
    KeyboardSensor,
    PointerSensor,
    TouchSensor,
    useSensor,
    useSensors,
    type DropAnimation,
} from "@dnd-kit/core";
import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { SchemaFormBuilder, type JsonSchema } from "@library/json-schema-forms";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { Row } from "@library/layout/Row";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaItem } from "@library/metas/Metas";
import { TokenItem } from "@library/metas/TokenItem";
import { FramedModal } from "@library/modal/FramedModal";
import ModalSizes from "@library/modal/ModalSizes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType, singleBorder } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { notEmpty, slugify } from "@vanilla/utils";
import camelCase from "lodash-es/camelCase";
import uniq from "lodash-es/uniq";
import { useState } from "react";
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { ColorVar } from "@library/styles/CssVar";
import { PageBox } from "@library/layout/PageBox";

const dropAnimationConfig: DropAnimation = {
    sideEffects: defaultDropAnimationSideEffects({
        styles: {
            active: {
                opacity: "0.5",
            },
        },
    }),
};

export function FragmentEditorCustomOptions() {
    const editor = useFragmentEditor();
    const customSchema = editor.form.customSchema ?? { properties: {}, required: [] };
    const [showAddEdit, setShowAddEdit] = useState<boolean | ICustomField>(false);

    const [draggingApiName, setDraggingApiName] = useState<string | null>(null);

    const customFields = Object.entries(customSchema.properties ?? {})
        .map(([fieldName, field]) => {
            const customField = schemaToCustomField(
                fieldName,
                field,
                customSchema.required?.includes(fieldName) ?? false,
            );
            return customField;
        })
        .filter(notEmpty);

    const draggingField = customFields.find((field) => field.apiName === draggingApiName);

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(TouchSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    return (
        <div className={classes.root}>
            <div className={classes.formContainer}>
                <PageHeadingBox
                    title={t("Form Fields")}
                    depth={3}
                    description={t(
                        "Manage a custom form for your fragment. Your community managers will be able to use this form to configure the fragment.",
                    )}
                />
                <DndContext
                    onDragStart={(event) => {
                        const { active } = event;

                        setDraggingApiName(active.id as string);
                    }}
                    onDragCancel={() => {
                        setDraggingApiName(null);
                    }}
                    onDragEnd={(event) => {
                        const { active, over } = event;

                        if (active.id !== over?.id) {
                            const existingPropertiesEntries = Object.entries(customSchema?.properties ?? {});
                            const oldIndex = existingPropertiesEntries.findIndex(([key]) => key === active.id);
                            const newIndex = existingPropertiesEntries.findIndex(([key]) => key === over?.id);

                            const newPropertyEntries = arrayMove(existingPropertiesEntries, oldIndex, newIndex);
                            const newProperties = Object.fromEntries(newPropertyEntries);

                            editor.updateForm({
                                customSchema: {
                                    ...customSchema,
                                    properties: newProperties,
                                },
                            });
                        }
                        setDraggingApiName(null);
                    }}
                    sensors={sensors}
                    collisionDetection={closestCenter}
                >
                    <SortableContext
                        items={customFields.map((field) => field.apiName)}
                        strategy={verticalListSortingStrategy}
                    >
                        <List options={{ itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                            {customFields.map((field, index) => {
                                return (
                                    <CustomFieldItem
                                        key={field.apiName}
                                        field={field}
                                        onEdit={() => setShowAddEdit(field)}
                                    />
                                );
                            })}
                        </List>
                        <DragOverlay dropAnimation={dropAnimationConfig}>
                            {draggingField ? (
                                <div style={{ padding: "0 8px" }}>
                                    <List options={{ itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION }}>
                                        <CustomFieldItem isOverlay={true} field={draggingField} />
                                    </List>
                                </div>
                            ) : null}
                        </DragOverlay>
                    </SortableContext>
                </DndContext>
                <div className={classes.addRow}>
                    <Button
                        onClick={() => {
                            setShowAddEdit(true);
                        }}
                        buttonType={"input"}
                    >
                        {t("Add Field")}
                    </Button>
                </div>

                {showAddEdit && (
                    <CustomFieldModal
                        initialValue={typeof showAddEdit === "object" ? showAddEdit : undefined}
                        onClose={() => {
                            setShowAddEdit(false);
                        }}
                    />
                )}
            </div>
        </div>
    );
}

function CustomFieldItem(props: { field: ICustomField; isOverlay?: boolean; onEdit?: () => void }) {
    const { field } = props;
    const editor = useFragmentEditor();
    const { isDragging, setActivatorNodeRef, attributes, listeners, setNodeRef, transform, transition } = useSortable({
        id: field.apiName,
        transition: null,
    });

    const style: React.CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        zIndex: isDragging ? 1 : 0,
        opacity: isDragging ? 0.3 : 1,
    };
    const controlTypeOption = getControlTypeOptions().find((option) => option.value === field.controlType)!;
    return (
        <PageBox options={{ borderType: BorderType.SEPARATOR }} ref={setNodeRef} style={style} className={classes.item}>
            <ListItem
                name={
                    <Row align={"baseline"} gap={8} className={classes.name}>
                        {field.required && (
                            <span aria-label={t("required")} className={classes.required}>
                                *
                            </span>
                        )}
                        {field.label}
                        <code className={classes.apiName}>{field.apiName}</code>
                    </Row>
                }
                metas={
                    <>
                        <MetaItem>
                            <strong>Form Type: </strong>
                            {controlTypeOption.label}
                        </MetaItem>
                    </>
                }
                description={
                    field.choices && (
                        <Row align={"center"} wrap={true} gap={6}>
                            {choicesStringToArray(field.choices).map((choice, i) => (
                                <TokenItem key={i}>{choice.value}</TokenItem>
                            ))}
                        </Row>
                    )
                }
                actionAlignment={"center"}
                actions={
                    <>
                        <Button buttonType={"icon"} ref={setActivatorNodeRef} {...attributes} {...listeners}>
                            <Icon icon={"move-drag"} />
                        </Button>
                        <Button
                            buttonType={"icon"}
                            onClick={() => {
                                props.onEdit?.();
                            }}
                        >
                            <Icon icon={"edit"} />
                        </Button>
                        <Button
                            buttonType={"icon"}
                            onClick={() => {
                                const newProperties = { ...editor.form.customSchema?.properties };
                                delete newProperties[field.apiName];
                                const required =
                                    editor.form.customSchema?.required?.filter((req) => req !== field.apiName) ?? [];
                                editor.updateForm({
                                    customSchema: {
                                        ...editor.form.customSchema,
                                        required,
                                        properties: newProperties,
                                    },
                                });
                            }}
                        >
                            <Icon icon={"delete"} />
                        </Button>
                    </>
                }
            />
        </PageBox>
    );
}

const addFieldSchema = SchemaFormBuilder.create()
    .textBox("label", "Field Label", "The label that will be displayed to community managers.")
    .required()
    .textBox("apiName", "API Label", "The property name your react component will use to access this field.")
    .required()
    .selectStatic("controlType", "Field Type", "The type of data that will in the property", getControlTypeOptions())
    .required()
    .textArea("choices", "Choices", "A list of options for the dropdown field. One per line.")
    .withCondition({
        field: "controlType",
        enum: [FragmentControlType.SelectMulti, FragmentControlType.SelectSingle],
        default: "",
    })
    .checkBoxRight("required", "Required", "Whether or not this field is required.")
    .withCondition({
        field: "controlType",
        enum: [FragmentControlType.CheckBox],
        invert: true,
    })
    .getSchema();

function CustomFieldModal(props: { initialValue?: ICustomField; onClose: () => void }) {
    const editor = useFragmentEditor();

    const [didTouchApiLabel, setDidTouchApiLabel] = useState(props.initialValue != null);

    const [value, _setValue] = useState<ICustomField>(
        props.initialValue ?? {
            apiName: "",
            label: "",
            controlType: "Text",
            choices: "",
            required: false,
        },
    );

    const setValue = (newValue: ICustomField) => {
        if (newValue.apiName !== value.apiName && !didTouchApiLabel) {
            setDidTouchApiLabel(true);
        } else if (!didTouchApiLabel) {
            // Update the API label
            newValue.apiName = camelCase(newValue.label);
        }

        _setValue(newValue);
    };

    return (
        <FramedModal
            title={t("Add Field")}
            onClose={props.onClose}
            size={ModalSizes.LARGE}
            onFormSubmit={(e) => {
                if (!e.currentTarget.reportValidity()) {
                    return;
                }

                let newRequired = uniq([...(editor.form.customSchema?.required ?? []), value.apiName]);

                if (!value.required) {
                    newRequired = newRequired.filter(
                        (field) => field !== value.apiName && field !== props.initialValue?.apiName,
                    );
                }

                const newProperties = {
                    ...editor.form.customSchema?.properties,
                };

                if (props.initialValue) {
                    // Remove the old property
                    delete newProperties[props.initialValue.apiName];
                }

                newProperties[value.apiName] = customFieldToSchema(value);

                editor.updateForm({
                    customSchema: {
                        ...editor.form.customSchema,
                        properties: newProperties,
                        required: newRequired,
                    } as JsonSchema,
                });

                props.onClose();

                // Add the field to the custom schema.
            }}
            footer={
                <Button buttonType={"textPrimary"} type="submit">
                    {t("Save")}
                </Button>
            }
        >
            <DashboardSchemaForm
                instance={value}
                onChange={(newValue) => setValue(newValue(value))}
                schema={addFieldSchema}
            />
        </FramedModal>
    );
}

const classes = {
    root: css({
        display: "flex",
        flexDirection: "column",
        height: "100%",
        overflow: "auto",
    }),
    item: css({
        background: ColorsUtils.var(ColorVar.Background),
        color: ColorsUtils.var(ColorVar.Foreground),
    }),
    formContainer: css({
        padding: "12px  28px",
    }),
    heading: css({
        marginTop: 16,
    }),
    name: css({
        position: "relative",
    }),
    addRow: css({
        display: "flex",
        alignItems: "center",
        justifyContent: "flex-end",
        padding: "16px 0",
    }),
    required: css({
        position: "absolute",
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.red),
        left: -16,
        lineHeight: "24px",
    }),
    apiName: css({
        fontFamily: globalVariables().fonts.families.monospace,
        fontSize: 12,
        border: singleBorder(),
        padding: "2px 4px",
        borderRadius: 4,
    }),
    formTypeCell: css({
        paddingTop: 4,
        paddingBottom: 4,
        display: "flex",
        flexDirection: "column",
        gap: 4,
    }),
};
