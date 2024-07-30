/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { reorderReportReasonModalClasses } from "@dashboard/communityManagementSettings/ReorderReportReasonModal.classes";
import { useReasonsSortMutation } from "@dashboard/moderation/CommunityManagement.hooks";
import { IReason } from "@dashboard/moderation/CommunityManagementTypes";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import ButtonLoader from "@library/loaders/ButtonLoader";
import Modal from "@library/modal/Modal";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import FormTree from "@library/tree/FormTree";
import { ITreeData } from "@library/tree/types";
import { itemsToTree } from "@library/tree/utils";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { useEffect, useMemo, useState } from "react";

interface IProps {
    reportReasons: IReason[];
    isVisible: boolean;
    onVisibilityChange: (visible: boolean) => void;
}

type ReasonTreeValue = {
    id: string;
    name: string;
    description: string;
};

const REASON_ITEM_SCHEMA: JsonSchema = {
    type: "object",
    properties: {
        id: {
            type: "string",
        },
        name: {
            type: "string",
            "x-control": {
                inputType: "textBox",
            },
        },
        description: {
            type: "string",
            "x-control": {
                inputType: "textBox",
            },
        },
    },
    required: [],
};

export default function ReorderReportReasonModalImpl(props: IProps) {
    const { isVisible, onVisibilityChange, reportReasons } = props;
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();
    const classes = reorderReportReasonModalClasses();
    const reasonSort = useReasonsSortMutation();

    const makeTreeItems = (reasons: IReason[]): ITreeData<ReasonTreeValue> => {
        return itemsToTree(
            reasons.map((reason) => ({
                id: reason.reportReasonID,
                name: reason.name,
                description: reason.description,
            })),
        );
    };

    const [confirmDialogVisible, setConfirmDialogVisible] = useState(false);
    const [isDirty, setIsDirty] = useState(false);
    const [treeValue, setTreeValue] = useState<ITreeData<ReasonTreeValue>>();

    const handleCloseButton = () => {
        if (isDirty) {
            setConfirmDialogVisible(true);
        } else {
            resetAndClose();
        }
    };

    const resetAndClose = () => {
        setTreeValue(makeTreeItems(reportReasons));
        setIsDirty(false);
        setConfirmDialogVisible(false);
        onVisibilityChange(false);
    };

    const submitForm = async () => {
        if (treeValue) {
            const payload = Object.fromEntries(
                treeValue.items.tree.children.map((reasonID, index) => [reasonID, index]),
            );
            await reasonSort.mutateAsync(payload);
            onVisibilityChange(false);
        }
    };

    useEffect(() => {
        reportReasons && setTreeValue(makeTreeItems(reportReasons));
    }, [reportReasons]);

    useEffect(() => {
        if (reportReasons) {
            const initialOrder = reportReasons
                .sort((a, b) => (a.sort > b.sort ? 1 : -1))
                .map((reason) => reason.reportReasonID);
            const currentOrder = treeValue?.items.tree.children ?? [];

            setIsDirty(!initialOrder.every((value, index) => value === currentOrder[index]));
        }
    }, [reportReasons, treeValue]);

    return (
        <>
            <Modal
                isVisible={isVisible}
                exitHandler={() => handleCloseButton()}
                size={ModalSizes.LARGE}
                titleID={"reasonConfiguration"}
            >
                <form
                    onSubmit={async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={"reasonConfiguration"}
                                closeFrame={() => handleCloseButton()}
                                title={t("Reorder Report Reasons")}
                            />
                        }
                        body={
                            <FrameBody className={cx(classesFrameBody.root, classes.frameBody)}>
                                {treeValue && (
                                    <FormTree<ReasonTreeValue>
                                        itemSchema={REASON_ITEM_SCHEMA}
                                        onChange={setTreeValue}
                                        value={treeValue}
                                        isItemEditable={() => false}
                                        isItemDeletable={() => false}
                                        isItemHideable={() => false}
                                        getRowIcon={() => null}
                                        displayLabels={true}
                                        RowContentsComponent={ReasonRow}
                                    />
                                )}
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <>
                                    <Button
                                        buttonType={ButtonTypes.TEXT}
                                        onClick={() => handleCloseButton()}
                                        className={classFrameFooter.actionButton}
                                    >
                                        {t("Cancel")}
                                    </Button>
                                    <Button
                                        submit
                                        disabled={reasonSort.isLoading}
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                    >
                                        {reasonSort.isLoading ? <ButtonLoader /> : t("Save")}
                                    </Button>
                                </>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <ModalConfirm
                isVisible={confirmDialogVisible}
                title={t("Discard Changes?")}
                onCancel={() => setConfirmDialogVisible(false)}
                onConfirm={() => resetAndClose()}
                confirmTitle={t("Exit")}
            >
                {t("Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}

function ReasonRow(props: ReasonTreeValue) {
    const classes = reorderReportReasonModalClasses();
    return (
        <div className={classes.row}>
            <span>
                <span>{props.name}</span>
            </span>
            <span>{props.description}</span>
        </div>
    );
}
