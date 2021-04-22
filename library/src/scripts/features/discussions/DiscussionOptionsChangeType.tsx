/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent, ReactNode, useState } from "react";
import { IDiscussion } from "@dashboard/@types/api/discussion";
import { t } from "@library/utility/appUtils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import { useFormik } from "formik";
import RadioButtonGroup from "@library/forms/RadioButtonGroup";
import RadioButton from "@library/forms/RadioButton";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useDiscussionPutType } from "@library/features/discussions/discussionHooks";
import { labelize } from "@vanilla/utils";

type FormValues = {
    type: string;
};

interface IProps {
    onCancel: () => void;
    onSuccess: () => void;
    discussion: IDiscussion;
}

export const NON_CHANGE_TYPE = ["poll", "event"];

function ChangeTypeDiscussionForm({ onCancel, onSuccess, discussion }: IProps) {
    const { putDiscussionType } = useDiscussionPutType(discussion.discussionID);
    const formik = useFormik<FormValues>({
        initialValues: {
            type: discussion.type,
        },
        onSubmit: ({ type }) => {
            putDiscussionType({
                type: type,
            });

            onSuccess();
        },
    });

    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const allowedDiscussionTypes = discussion?.category?.allowedDiscussionTypes ?? undefined;

    const { isSubmitting, handleSubmit, handleChange } = formik;

    let buttonGroup: ReactNode[] = [];

    if (allowedDiscussionTypes) {
        buttonGroup = allowedDiscussionTypes.map((type) => {
            if (type.length > 1 && !NON_CHANGE_TYPE.includes(type)) {
                return (
                    <RadioButton
                        key={type}
                        defaultChecked={formik.values.type === type}
                        onChange={handleChange}
                        name={"type"}
                        value={`${type}`}
                        label={t(`${labelize(type)}`)}
                    />
                );
            }
        });
    }

    return (
        <form onSubmit={handleSubmit}>
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Change Discussion Type")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <>{t("Select Discussion Type:")}</>
                            <RadioButtonGroup>{buttonGroup}</RadioButtonGroup>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            baseClass={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            disabled={isSubmitting}
                            baseClass={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                        >
                            {isSubmitting ? <ButtonLoader /> : t("OK")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}

const DiscussionOptionsChangeType: FunctionComponent<{ discussion: IDiscussion }> = ({ discussion }) => {
    const [isVisible, setIsVisible] = useState(false);
    const open = () => setIsVisible(true);
    const close = () => setIsVisible(false);

    return (
        <>
            <DropDownItemButton onClick={open}>{t("Change Type")}</DropDownItemButton>
            <Modal isVisible={isVisible} size={ModalSizes.MEDIUM} exitHandler={close}>
                <ChangeTypeDiscussionForm discussion={discussion} onSuccess={close} onCancel={close} />
            </Modal>
        </>
    );
};

export default DiscussionOptionsChangeType;
