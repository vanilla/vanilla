/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { t } from "@vanilla/i18n";
import React, { useEffect, useMemo, useState } from "react";

import { ProfileField, PutUserProfileFieldsParams } from "@dashboard/userProfiles/types/UserProfiles.types";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useFormik } from "formik";

import FormTree from "@library/tree/FormTree";
import { itemsToTree } from "@library/tree/utils";
import { JsonSchema } from "@vanilla/json-schema-forms";
import isEqual from "lodash/isEqual";
import sortBy from "lodash/sortBy";
import { ReorderProfileFieldsClasses } from "@dashboard/userProfiles/components/ReorderProfileFields.classes";
import StatusLight from "@library/statusLight/StatusLight";
import TruncatedText from "@library/content/TruncatedText";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";

type ReorderProfileFieldsFormValues = PutUserProfileFieldsParams;

type ProfileFieldTreeItem = Pick<ProfileField, "apiName" | "label" | "enabled" | "visibility">;
interface IProps {
    isVisible: boolean;
    onCancel(): void;
    onSubmit(values: ReorderProfileFieldsFormValues): Promise<void>;
    onSubmitSuccess(): void;
    onSubmitError(error?: any): void;
    sortedProfileFields: ProfileField[];
}

export default function ReorderProfileFields(props: IProps) {
    const { sortedProfileFields, isVisible, onCancel, onSubmit, onSubmitSuccess, onSubmitError } = props;

    function getInitialValues() {
        return Object.fromEntries(sortedProfileFields.map(({ apiName, sort }) => [apiName, sort ?? 0]));
    }

    function getInitialTreeItems() {
        return itemsToTree(
            sortedProfileFields.map(({ apiName, label, enabled, visibility }) => ({
                apiName,
                label,
                enabled,
                visibility,
            })) as ProfileFieldTreeItem[],
        );
    }

    const { handleSubmit, isSubmitting, setValues, values, resetForm } = useFormik<ReorderProfileFieldsFormValues>({
        initialValues: getInitialValues(),
        enableReinitialize: true,
        onSubmit: async (values) => {
            try {
                await onSubmit(values);
                onSubmitSuccess();
            } catch (error) {
                onSubmitError(error);
            }
        },
    });

    function cancel() {
        onCancel();
    }

    const orderHasChanged = useMemo(() => {
        const initialSort = sortedProfileFields.map(({ apiName }) => apiName);
        const newSort = sortBy(Object.entries(values), ([, sortIndex]) => sortIndex).map(([apiName]) => apiName);

        return !isEqual(initialSort, newSort);
    }, [sortedProfileFields, values]);

    const submitButtonDisabled = isSubmitting || !orderHasChanged;

    const itemSchema: JsonSchema = {
        type: "object",
        properties: {
            apiName: {
                type: "string",
            },
            label: {
                type: "string",
                "x-control": {
                    inputType: "textBox",
                },
            },
            enabled: {
                type: "string",
                "x-control": {
                    inputType: "textBox",
                    label: "enabled",
                },
            },
        },
    };

    const [treeValue, setTreeValue] = useState(getInitialTreeItems());

    useEffect(() => {
        setTreeValue(getInitialTreeItems());
    }, [sortedProfileFields]);

    useEffect(() => {
        const order: Array<[string, number]> = treeValue.items.tree!.children.map((draggableID, index) => {
            return [treeValue.items[draggableID]!.data!.apiName!, index + 1];
        });
        const formVals = Object.fromEntries(order);
        setValues(formVals);
    }, [treeValue, setValues]);

    return (
        <Modal isVisible={isVisible} size={ModalSizes.LARGE} exitHandler={cancel}>
            <form onSubmit={handleSubmit}>
                <Frame
                    header={<FrameHeader closeFrame={cancel} title={t("Reorder Profile Fields")} />}
                    body={
                        <FrameBody hasVerticalPadding>
                            <FormTree<ProfileFieldTreeItem>
                                displayLabels={false}
                                itemSchema={itemSchema}
                                onChange={setTreeValue}
                                value={treeValue}
                                isItemEditable={(item) => false}
                                isItemDeletable={(item) => false}
                                isItemHideable={(item) => false}
                                getRowIcon={() => null}
                                RowContentsComponent={ProfileFieldRow}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button buttonType={ButtonTypes.TEXT} onClick={cancel}>
                                {t("Cancel")}
                            </Button>
                            <Button submit buttonType={ButtonTypes.TEXT_PRIMARY} disabled={submitButtonDisabled}>
                                {isSubmitting ? <ButtonLoader /> : t("Apply")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </form>
        </Modal>
    );
}

function ProfileFieldRow(props: ProfileFieldTreeItem) {
    const classes = ReorderProfileFieldsClasses();

    const { enabled, label, visibility } = props;

    const enabledTitle = enabled ? t("Active") : t("Inactive");
    return (
        <div className={classes.row}>
            <div className={classes.labelContainer}>
                <TruncatedText lines={1}>{label}</TruncatedText>
                <ProfileFieldVisibilityIcon visibility={visibility} />
            </div>
            <div className={classes.enabledContainer}>
                <StatusLight active={enabled} title={enabledTitle} className={classes.statusLight} /> {enabledTitle}
            </div>
        </div>
    );
}
