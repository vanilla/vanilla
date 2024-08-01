/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import advancedMembersFiltersClasses from "@dashboard/components/panels/AdvancedMembersFilters.classes";
import { IMemberSearchTypes } from "@dashboard/components/panels/memberSearchTypes";
import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { FormControl, FormControlGroup } from "@library/forms/FormControl";
import { PlusCircleIcon } from "@library/icons/common";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { locationPickerClasses } from "@library/navigation/locationPickerStyles";
import { pageLocationClasses } from "@library/navigation/pageLocationStyles";
import { t } from "@vanilla/i18n";
import { JsonSchema, JsonSchemaForm } from "@vanilla/json-schema-forms";
import { useFormik } from "formik";
import React, { ComponentType, useEffect, useState } from "react";
interface AdvancedMembersFiltersProps {
    schema: JsonSchema;
    onSubmit(values: any): Promise<void>;
    values?: IMemberSearchTypes["profileFields"];

    /** Some extra props for the usage of this component for other sections*/
    noFocusOnModalExit?: boolean;
    modalClassName?: string;
    modalTitleAndDescription?: {
        titleID?: string;
        title?: string;
        description?: string;
    };
    // if there is dateRange inputType in our schema this will determine its open direction.
    dateRangeDirection?: "above" | "below";
}

export type IProps = AdvancedMembersFiltersProps &
    (
        | {
              ModalTriggerButton: ComponentType<any>;
              modalTriggerButtonProps: {
                  dirty: boolean;
              };
          }
        | {
              ModalTriggerButton?: null;
              modalTriggerButtonProps?: null;
          }
    );

export default function AdvancedMembersFilters(props: IProps) {
    const [modalOpen, setModalOpen] = useState<boolean>(false);

    const {
        schema,
        ModalTriggerButton,
        modalTriggerButtonProps,
        noFocusOnModalExit = false,
        modalClassName,
        modalTitleAndDescription,
        dateRangeDirection,
    } = props;

    const titleID = modalTitleAndDescription?.titleID ?? "MemberFiltersModal";
    const title = t(modalTitleAndDescription?.title ?? "Add More Member Filters");

    const classes = advancedMembersFiltersClasses();
    const classesFrameBody = frameBodyClasses();
    const exitHandler = () => setModalOpen(false);

    const { values, submitForm, setValues, isSubmitting, resetForm } = useFormik<
        NonNullable<IMemberSearchTypes["profileFields"]>
    >({
        initialValues: props.values ?? {},
        onSubmit: async (values) => {
            exitHandler();
            await props.onSubmit(values);
        },
        enableReinitialize: true,
        validateOnChange: false,
    });

    // reset values to what we have from props
    useEffect(() => {
        if (!modalOpen) {
            setValues(props.values ?? {});
        }
    }, [modalOpen]);

    const classesPageLocation = pageLocationClasses();
    const classesLocationPicker = locationPickerClasses();

    return (
        <>
            {ModalTriggerButton && (
                <ModalTriggerButton
                    onClick={() => {
                        setModalOpen(true);
                    }}
                    dirty={modalTriggerButtonProps?.dirty}
                />
            )}
            {!ModalTriggerButton && (
                <Button
                    buttonType={ButtonTypes.CUSTOM}
                    className={classesPageLocation.picker}
                    onClick={() => setModalOpen(true)}
                    title={t("More Filters")}
                >
                    <span className={classesLocationPicker.iconWrapper}>
                        <PlusCircleIcon />
                    </span>
                    <span className={classesLocationPicker.initialText}>{t("More Filters")}</span>
                </Button>
            )}

            <Modal
                isVisible={modalOpen}
                exitHandler={exitHandler}
                size={ModalSizes.MEDIUM}
                titleID={titleID}
                noFocusOnExit={noFocusOnModalExit}
                className={modalClassName}
            >
                <form
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={<FrameHeader titleID={titleID} closeFrame={exitHandler} title={title} />}
                        body={
                            <FrameBody className={classes.root}>
                                <div className={classesFrameBody.contents}>
                                    <p className={classes.description}>
                                        {t(
                                            modalTitleAndDescription?.description ??
                                                "Filter search results by adding more available Members filters.",
                                        )}
                                    </p>

                                    <JsonSchemaForm
                                        schema={schema}
                                        instance={values}
                                        FormControl={(props) =>
                                            FormControl({
                                                ...props,
                                                dateRangeDirection: dateRangeDirection,
                                            })
                                        }
                                        FormControlGroup={FormControlGroup}
                                        onChange={setValues}
                                    />
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter className={css({ justifyContent: "space-between" })}>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        resetForm({ values: {} });
                                    }}
                                    disabled={isSubmitting}
                                >
                                    {t("Clear All")}
                                </Button>
                                <Button submit disabled={isSubmitting} buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {isSubmitting ? <ButtonLoader /> : t("Filter")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </>
    );
}
