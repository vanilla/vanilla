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
import debounce from "lodash/debounce";
import { useAsyncFn } from "@vanilla/react-utils";
import { LoadStatus } from "@library/@types/api/core";
import { CategoryDisplayAs } from "@vanilla/addon-vanilla/categories/categoriesTypes";

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
    const [searchFilter, setSearchFilter] = useState<string>("");
    const suggestions = useCategorySuggestions(searchFilter, null, true);
    const [performDeleteState, performDelete] = useAsyncFn(async () => {
        await apiv2.delete(`/categories/${categoryID}`, {
            params: {
                newCategoryID: actionType === "move" ? replacementCategoryID : undefined,
                longRunnerMode: "sync",
            },
        });
        window.location.reload();
        setIsVisible(false);
    }, [categoryID, actionType, replacementCategoryID]);
    const filteredSuggestions = useMemo(
        () =>
            suggestions.data?.filter(
                (category) =>
                    category.categoryID !== categoryID && category.displayAs === CategoryDisplayAs.DISCUSSIONS,
            ),
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
    const isMoveValid = replacementCategoryID !== undefined;
    const isDeleteValid = !discussionsCount || confirmDelete;
    const isConfirmDisabled = (actionType == "move" && isMoveValid) || (actionType === "delete" && isDeleteValid);
    const isSubmitting = performDeleteState.status === "loading";

    const handleSearchFilter = debounce((value) => {
        setSearchFilter(value);
    }, 100);

    return (
        <ModalConfirm
            isVisible={isVisible}
            onCancel={() => {
                if (!isSubmitting) {
                    setIsVisible(false);
                }
            }}
            size={ModalSizes.LARGE}
            title={t("Delete Category")}
            isConfirmDisabled={!isConfirmDisabled}
            isConfirmLoading={isSubmitting}
            onConfirm={() => {
                performDelete();
            }}
            confirmTitle={t("Delete Category")}
        >
            <DashboardFormList isBlurred={isSubmitting}>
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
                            onInputChange={(value) => {
                                handleSearchFilter(value);
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
