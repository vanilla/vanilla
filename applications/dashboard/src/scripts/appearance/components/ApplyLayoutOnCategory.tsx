/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */
import React, { useState } from "react";
import { useFormik } from "formik";
import { AutoComplete, FormGroup, FormGroupInput, FormGroupLabel, AutoCompleteLookupOptions } from "@vanilla/ui";
import { t } from "@library/utility/appUtils";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import FrameBody from "@library/layout/frame/FrameBody";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { ILayoutDetails, LayoutViewFragment } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { usePutLayoutViews } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { GLOBAL_LAYOUT_VIEW } from "@dashboard/layout/layoutSettings/LayoutActionsContextProvider";
import { useToast } from "@library/features/toaster/ToastContext";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useConfigPatcher } from "@library/config/configHooks";
import apiv2 from "@library/apiv2";

interface FormValues {
    recordIDs: Array<LayoutViewFragment["recordID"]>;
}

export default function ApplyLayoutOnCategory(props: { layout: ILayoutDetails; forceModalOpen?: boolean }) {
    const { layout, forceModalOpen } = props;

    const putLayoutViews = usePutLayoutViews(layout);
    const { patchConfig } = useConfigPatcher();
    const toast = useToast();
    const [modalOpen, setModalOpen] = useState(forceModalOpen ?? false);

    function closeModal() {
        setModalOpen(false);
    }

    async function handleSubmitForm(formValues: FormValues) {
        const layoutViewFragments: LayoutViewFragment[] = formValues.recordIDs.map((recordID) =>
            recordID === GLOBAL_LAYOUT_VIEW.recordID
                ? GLOBAL_LAYOUT_VIEW
                : ({
                      recordID,
                      recordType: "category",
                  } as LayoutViewFragment),
        );

        try {
            await putLayoutViews(layoutViewFragments);
            toast.addToast({
                autoDismiss: true,
                body: <>{t("Layout settings applied.")}</>,
            });
            patchConfig({
                ["labs.customLayout.categoryList"]: true,
            });
        } catch (e) {
            toast.addToast({
                autoDismiss: false,
                dismissible: true,
                body: <>{e.description}</>,
            });
        } finally {
            setModalOpen(false);
        }
    }

    const { submitForm, setFieldValue, isSubmitting, values, dirty } = useFormik<FormValues>({
        initialValues: {
            recordIDs: layout.layoutViews.map((layoutView) => layoutView.recordID),
        },
        onSubmit: handleSubmitForm,
    });

    const searchUrl = `/categories/search?query=%s`;

    const classFrameFooter = frameFooterClasses();
    const classesFrameBody = frameBodyClasses();

    return (
        <DropDownItemButton
            onClick={() => {
                setModalOpen(true);
            }}
        >
            {t("Apply")}
            <Modal isVisible={modalOpen} size={ModalSizes.SMALL} exitHandler={closeModal}>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        submitForm();
                    }}
                >
                    <Frame
                        header={<FrameHeader closeFrame={closeModal} title={t("Apply Layout")} />}
                        body={
                            <FrameBody>
                                <section className={classesFrameBody.contents}>
                                    <FormGroup>
                                        <FormGroupLabel>{t("Categories")}</FormGroupLabel>
                                        <FormGroupInput>
                                            {(props) => (
                                                <AutoComplete
                                                    {...props}
                                                    size="small"
                                                    multiple
                                                    value={values.recordIDs}
                                                    onChange={(val) => {
                                                        setFieldValue("recordIDs", val ?? []);
                                                    }}
                                                    options={[
                                                        {
                                                            value: GLOBAL_LAYOUT_VIEW.recordID,
                                                            label: t("All Categories Page"),
                                                        },
                                                    ]}
                                                    optionProvider={
                                                        <AutoCompleteLookupOptions
                                                            api={apiv2}
                                                            lookup={{
                                                                searchUrl,
                                                                singleUrl: "categories/%s",
                                                                labelKey: "name",
                                                                valueKey: "categoryID",
                                                                group: t("Categories"),
                                                            }}
                                                        />
                                                    }
                                                    clear
                                                />
                                            )}
                                        </FormGroupInput>
                                    </FormGroup>
                                </section>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={closeModal}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    disabled={!dirty || isSubmitting}
                                    submit
                                    className={classFrameFooter.actionButton}
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                >
                                    {isSubmitting ? <ButtonLoader /> : t("Apply")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </DropDownItemButton>
    );
}
