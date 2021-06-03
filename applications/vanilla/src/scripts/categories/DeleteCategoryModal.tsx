/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useCallback, useEffect, useMemo, useReducer, useState } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import { useCategorySuggestions } from "@vanilla/addon-vanilla/forms/CommunityCategoryInput";
import { DashboardRadioGroup } from "@dashboard/forms/DashboardRadioGroups";
import { DashboardRadioButton } from "@dashboard/forms/DashboardRadioButton";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardCheckBox } from "@dashboard/forms/DashboardCheckBox";
import { sprintf } from "sprintf-js";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import { MenuPlacement } from "@library/forms/select/SelectOne";
import { RecordID } from "@vanilla/utils";
import apiv2 from "@library/apiv2";

interface IProps {
    categoryID: number;
    discussionsCount?: number;
}

type ActionType = "move" | "delete";

export function DeleteCategoryModal(props: IProps) {
    const { categoryID, discussionsCount } = props;
    const [isVisible, setIsVisible] = useState(true);
    const [confirmDelete, setConfirmDelete] = useState(true);
    const [actionType, setActionType] = useState<ActionType | undefined>();
    const [replacementCategoryID, setReplacementCategoryID] = useState<RecordID | undefined>();
    const [performDeleteCounter, dispatchDelete] = useReducer((state: number, action) => {
        switch (action.type) {
            case "start":
            case "retry":
                return state + 1;
            case "finished":
                window.location.reload();
                setIsVisible(false);
                return 0;
            case "cancel":
                return 0;
        }
        return state;
    }, 0);
    const suggestions = useCategorySuggestions("", true);

    const filteredSuggestions = useMemo(
        () => suggestions.data?.filter((category) => category.categoryID !== categoryID),
        [suggestions.data, categoryID],
    );

    const categoryOptions = useMemo<IComboBoxOption[]>(
        () =>
            filteredSuggestions?.map((suggestion) => ({
                label: suggestion.name,
                value: suggestion.categoryID,
                data: suggestion,
            })) || [],
        [filteredSuggestions],
    );

    const onPerformDelete = useCallback(async () => {
        try {
            const response = await apiv2.delete(`categories/${categoryID}`, {
                params: {
                    batch: true,
                    newCategoryID: actionType === "move" ? replacementCategoryID : undefined,
                },
                timeout: 60000,
            });
            const { status } = response;
            switch (status) {
                case 204:
                    dispatchDelete({ type: "finished" });
                    break;
                case 202:
                    dispatchDelete({ type: "retry" });
                    break;
            }
        } catch {
            dispatchDelete({ type: "cancel" });
        }
    }, [actionType, replacementCategoryID, categoryID]);

    useEffect(() => {
        if (performDeleteCounter > 0) {
            onPerformDelete();
        }
    }, [performDeleteCounter, onPerformDelete]);

    const isMoveValid = replacementCategoryID !== undefined;
    const isDeleteValid = !discussionsCount || confirmDelete;
    const isConfirmDisabled = (actionType == "move" && isMoveValid) || (actionType === "delete" && isDeleteValid);
    const isFormDisabled = performDeleteCounter > 0;

    return (
        <ModalConfirm
            isVisible={isVisible}
            onCancel={() => {
                if (!isFormDisabled) {
                    setIsVisible(false);
                }
            }}
            size={ModalSizes.LARGE}
            title={t("Delete Category")}
            isConfirmDisabled={!isConfirmDisabled}
            isConfirmLoading={performDeleteCounter > 0}
            onConfirm={() => {
                dispatchDelete({ type: "start" });
            }}
            confirmTitle={t("Delete Category")}
        >
            <DashboardFormList isBlurred={isFormDisabled}>
                <DashboardFormGroup label={t("Action")}>
                    <DashboardRadioGroup onChange={(value) => setActionType(value as ActionType)} value={actionType}>
                        <DashboardRadioButton
                            value={"move"}
                            label="Move content from this category to a replacement category."
                        />
                        <DashboardRadioButton
                            value={"delete"}
                            label="Permanently delete all content in this category."
                        />
                    </DashboardRadioGroup>
                </DashboardFormGroup>
                {actionType === "move" && (
                    <DashboardFormGroup
                        label={"Replacement Category"}
                        description={t(
                            "Heads Up! Moving discussions into a replacement category can result in discussions vanishing (or appearing) if the replacement category has different permissions than the category being deleted.",
                        )}
                    >
                        <DashboardSelect
                            isClearable={false}
                            options={categoryOptions}
                            menuPlacement={MenuPlacement.TOP}
                            value={categoryOptions.find((option) => {
                                return option.value === replacementCategoryID;
                            })}
                            onChange={(option) => {
                                setReplacementCategoryID(option?.value);
                            }}
                            /**
                             * This height depends on the height of the modal, ideally we
                             * want to allow the menu options to break out of the modal
                             * but those styles would be bigger kludge than this one.
                             */
                            maxHeight={150}
                        ></DashboardSelect>
                    </DashboardFormGroup>
                )}
                {actionType === "delete" && discussionsCount !== undefined && (
                    <DashboardFormGroup
                        label={t("Delete All")}
                        description={
                            <span
                                style={{ color: "red" }}
                                dangerouslySetInnerHTML={{
                                    __html: sprintf(
                                        t(
                                            discussionsCount > 1
                                                ? "<strong>%s</strong> discussion will be deleted. There is no undo and it will not be logged."
                                                : "<strong>%s</strong> discussions will be deleted. There is no undo and they will not be logged.",
                                        ),
                                        discussionsCount,
                                    ),
                                }}
                            />
                        }
                    >
                        <DashboardRadioGroup>
                            <DashboardCheckBox
                                checked={confirmDelete}
                                onChange={setConfirmDelete}
                                label="Yes, permanently delete it all."
                            />
                        </DashboardRadioGroup>
                    </DashboardFormGroup>
                )}
            </DashboardFormList>
        </ModalConfirm>
    );
}
