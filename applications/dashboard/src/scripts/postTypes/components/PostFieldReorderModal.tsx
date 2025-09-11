/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostField } from "@dashboard/postTypes/postType.types";
import TruncatedText from "@library/content/TruncatedText";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { JsonSchema } from "@library/json-schema-forms";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import FormTree from "@library/tree/FormTree";
import { itemsToTree } from "@library/tree/utils";
import { t } from "@vanilla/i18n";
import { useState, useEffect } from "react";
import { ITreeData } from "@library/tree/types";
import { css } from "@emotion/css";

type PostFieldTreeItem = Pick<PostField, "postFieldID" | "label" | "description" | "visibility">;

interface IProps {
    postFields: PostField[];
    isVisible: boolean;
    onCancel(): void;
    onConfirm(values: Record<PostField["postFieldID"], number>): void;
}

const POST_FIELD_ITEM_SCHEMA: JsonSchema = {
    type: "object",
    properties: {
        postFieldID: {
            type: "string",
        },
        label: {
            type: "string",
        },
        description: {
            type: "string",
        },
        visibility: {
            type: "string",
        },
    },
    required: [],
};

export default function PostFieldsReorderModal(props: IProps) {
    const { postFields, isVisible, onCancel, onConfirm } = props;

    const makeTreeItems = (fields: PostField[]): ITreeData<PostFieldTreeItem> => {
        return itemsToTree(
            fields.map((field) => ({
                postFieldID: field.postFieldID,
                label: field.label,
                description: field.description,
                visibility: field.visibility,
                children: [],
            })),
        );
    };

    const [values, setValues] = useState<ITreeData<PostFieldTreeItem>>();

    useEffect(() => {
        isVisible && postFields && setValues(makeTreeItems(postFields ?? []));
    }, [postFields, isVisible]);

    const cancel = () => {
        onCancel();
    };

    const submitForm = () => {
        if (values) {
            const payload = Object.fromEntries(
                values.items.tree.children.map((uuid, index) => {
                    const postFieldID = values.items[uuid].data.postFieldID;
                    return [postFieldID, index];
                }),
            );
            onConfirm(payload);
        }
    };

    return (
        <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={cancel}>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    submitForm();
                }}
            >
                <Frame
                    header={<FrameHeader closeFrame={cancel} title={t("Reorder Post Fields")} />}
                    body={
                        <FrameBody hasVerticalPadding>
                            {values && (
                                <FormTree<PostFieldTreeItem>
                                    displayLabels={true}
                                    itemSchema={POST_FIELD_ITEM_SCHEMA}
                                    onChange={setValues}
                                    value={values}
                                    isItemEditable={(item) => false}
                                    isItemDeletable={(item) => false}
                                    isItemHideable={(item) => false}
                                    getRowIcon={() => null}
                                    RowContentsComponent={PostFieldRow}
                                />
                            )}
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button buttonType={ButtonTypes.TEXT} onClick={cancel}>
                                {t("Cancel")}
                            </Button>
                            <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                {t("Reorder")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}

function PostFieldRow(props: PostFieldTreeItem) {
    const classes = {
        row: css({
            width: "100%",
            display: "flex",
            alignItems: "start",
            justifyContent: "space-between",
            padding: "4px 16px",

            "& > div": {
                display: "grid",
                gridAutoFlow: "column",
                gridTemplateColumns: "repeat(3, 100px)",
                alignItems: "start",
                justifyContent: "start",
                gap: 8,
            },
        }),
    };

    const { postFieldID, label, description, visibility } = props;
    return (
        <div className={classes.row}>
            <div>
                <span>
                    <TruncatedText lines={1}>{label}</TruncatedText>
                </span>
                <span>
                    <TruncatedText lines={1}>{postFieldID}</TruncatedText>
                </span>
                <span>
                    <TruncatedText lines={1}>{description}</TruncatedText>
                </span>
            </div>
            <span>{visibility}</span>
        </div>
    );
}
