/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo, useState } from "react";
import userManagementClasses from "@dashboard/users/userManagement/UserManagement.classes";
import { t } from "@vanilla/i18n";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardAutoComplete } from "@dashboard/forms/DashboardAutoComplete";
import {
    ColumnConfig,
    StackableTableColumnsConfig,
    StackableTableSortOption,
} from "@dashboard/tables/StackableTable/StackableTable";
import FormTree from "@library/tree/FormTree";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { itemsToTree } from "@library/tree/utils";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { JsonSchema } from "@vanilla/json-schema-forms";
import { ToolTip } from "@library/toolTip/ToolTip";
import {
    DEFAULT_ADDITIONAL_STATIC_COLUMNS,
    DEFAULT_CONFIGURATION,
    SORTABLE_COLUMNS,
    UserManagementColumnNames,
} from "@dashboard/users/userManagement/UserManagementUtils";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { labelize } from "@vanilla/utils";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";

type UserManagementColumnTreeItem = ColumnConfig & {
    column: string;
    label: string;
    selectedItemID?: string;
};

interface IProps {
    configuration: StackableTableColumnsConfig;
    onConfigurationChange: (newColumn: StackableTableColumnsConfig) => void;
    treeColumns: string[];
    additionalColumns: string[];
    profileFields?: ProfileField[];
    storyBookMode?: boolean;
}

export default function UserManagementColumnsConfig(props: IProps) {
    const { configuration, onConfigurationChange, treeColumns, additionalColumns, profileFields } = props;
    const classes = userManagementClasses();
    const [visible, setVisible] = useState<boolean>(!!props.storyBookMode);
    const [isValidConfig, setIsValidConfig] = useState<boolean>(true);

    const itemSchema: JsonSchema = {
        type: "object",
        properties: {
            order: {
                type: "number",
            },
            wrapped: {
                type: "boolean",
            },
            isHidden: {
                type: "boolean",
            },
            sortDirection: {
                type: "string",
            },
        },
        required: [],
    };

    // this bit is for storybook purposes
    useEffect(() => {
        if (props.storyBookMode && !treeColumns.length) {
            setIsValidConfig(false);
        }
    }, [props.storyBookMode]);

    const getTreeItems = (items?: string[], defaultConfiguration?: boolean) => {
        const columns = items ?? treeColumns;
        return itemsToTree(
            columns.map((column) => {
                return {
                    column: column,
                    label: labelize(column.replace(/i[pd]/, (m) => m.toUpperCase())),
                    ...(defaultConfiguration
                        ? DEFAULT_CONFIGURATION[column]
                        : items
                        ? {
                              wrapped: false,
                              isHidden: false,
                          }
                        : configuration[column]),
                };
            }) as UserManagementColumnTreeItem[],
        );
    };

    const [treeValue, setTreeValue] = useState(getTreeItems());

    useEffect(() => {
        setTreeValue(getTreeItems());
    }, [configuration]);

    const columnsForDropdown = useMemo(() => {
        const treeItemIDs = treeValue.items.tree.children;
        const columnsIntree = treeItemIDs.map((itemID) => treeValue.items[itemID]?.data.column);

        //always order main columns first, then static additional columns, then profile field columns
        const defaultMaindAndStaticAdditionalColumns = [
            ...Object.keys(DEFAULT_CONFIGURATION),
            ...DEFAULT_ADDITIONAL_STATIC_COLUMNS,
        ];
        const profileFieldColumns = [...treeColumns, ...additionalColumns].filter(
            (column) => !defaultMaindAndStaticAdditionalColumns.includes(column),
        );

        const orderedAllColumns = [...defaultMaindAndStaticAdditionalColumns, ...profileFieldColumns];

        //if in treeValue already, don't show in dropdown
        const dropdownColumns = orderedAllColumns.filter((column) => {
            if (!columnsIntree.includes(column)) {
                return column;
            }
        });
        return dropdownColumns;
    }, [treeValue, additionalColumns]);

    const closeModal = () => {
        setVisible(false);
        setIsValidConfig(true);
        setTreeValue(getTreeItems());
    };

    const handleDropdownOptionChange = (newColumn: string) => {
        const newItems = { ...treeValue.items };

        const newTreeItem = getTreeItems([newColumn]);
        const newItemID = newTreeItem.items.tree.children[0];

        newItems[newItemID] = newTreeItem.items[newItemID];
        newItems.tree.children = [...treeValue.items.tree.children, newItemID];

        setTreeValue({
            ...treeValue,
            items: newItems,
        });
    };

    const handleSubmit = () => {
        const newConfiguration = {};
        const treeItemIDs = treeValue.items.tree.children;
        const canApplyChanges =
            !!treeItemIDs.length && treeItemIDs.some((itemID) => !treeValue.items[itemID]?.data.isHidden);

        if (canApplyChanges) {
            treeItemIDs.forEach((itemID, index) => {
                const itemData = treeValue.items[itemID]?.data;
                const columnProfileField = profileFields?.filter(
                    (profileField) => profileField.label === itemData.column,
                );
                newConfiguration[itemData.column] = {
                    ...configuration[itemData.column],
                    order: index + 1,
                    isHidden: itemData.isHidden ?? false,
                    wrapped: false,
                    columnID:
                        columnProfileField && columnProfileField.length ? columnProfileField[0].apiName : undefined,
                    sortDirection: SORTABLE_COLUMNS.includes(itemData.column as UserManagementColumnNames)
                        ? StackableTableSortOption.NO_SORT
                        : undefined,
                    width: [
                        UserManagementColumnNames.USER_ID,
                        UserManagementColumnNames.POSTS,
                        UserManagementColumnNames.POINTS,
                    ].includes(itemData.column as UserManagementColumnNames)
                        ? 60
                        : undefined,
                };
            });
            onConfigurationChange(newConfiguration);
            closeModal();

            if (!isValidConfig) {
                setIsValidConfig(true);
            }
        } else {
            setIsValidConfig(false);
        }
    };

    return (
        <div>
            <ToolTip label={t("Column Display Settings")}>
                <span>
                    <Button
                        buttonType={ButtonTypes.ICON}
                        onClick={() => {
                            setVisible(!visible);
                        }}
                        className={classes.columnsConfigurationButton}
                    >
                        <Icon icon="edit-filters" />
                    </Button>
                </span>
            </ToolTip>

            <Modal
                isVisible={visible}
                size={ModalSizes.LARGE}
                exitHandler={closeModal}
                className={classes.columnsConfigurationModal}
                noFocusOnExit
            >
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        handleSubmit();
                    }}
                >
                    <Frame
                        header={<FrameHeader closeFrame={closeModal} title={t("Column Display Settings")} />}
                        body={
                            <FrameBody hasVerticalPadding>
                                {!isValidConfig && (
                                    <Message
                                        type="error"
                                        stringContents={t(
                                            "At least one visible column is required. Show or add a visible column.",
                                        )}
                                        icon={<ErrorIcon />}
                                        className={classes.topLevelError}
                                    />
                                )}
                                <DashboardFormGroup
                                    label={t("Add Columns")}
                                    description={t("Select which columns are displayed on the manage users page list.")}
                                    className={classes.dropdownContainer}
                                >
                                    <DashboardAutoComplete
                                        options={columnsForDropdown.map((column) => {
                                            return {
                                                label: labelize(column.replace(/i[pd]/, (m) => m.toUpperCase())),
                                                value: column,
                                            };
                                        })}
                                        onChange={handleDropdownOptionChange}
                                    />
                                </DashboardFormGroup>
                                <div className={classes.treeTitle}>{t("Reorder Columns")}</div>
                                <FormTree<UserManagementColumnTreeItem>
                                    treeRowClassName={classes.treeItem}
                                    forceCompactDeleteAction
                                    itemSchema={itemSchema}
                                    onChange={setTreeValue}
                                    value={treeValue}
                                    isItemEditable={() => false}
                                    isItemDeletable={() => true}
                                    markItemHidden={(
                                        itemID: string,
                                        item: UserManagementColumnTreeItem,
                                        isHidden: boolean,
                                    ) => {
                                        return {
                                            ...item,
                                            isHidden: isHidden,
                                        };
                                    }}
                                    isItemHidden={(item) => {
                                        return item.isHidden ?? false;
                                    }}
                                    isItemHideable={() => true}
                                    getRowIcon={() => "move-drag"}
                                    RowContentsComponent={(props: UserManagementColumnTreeItem) => {
                                        const { label } = props;
                                        return <div className={classes.treeItemName}>{label}</div>;
                                    }}
                                    displayLabels
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter className={classes.modalFooter}>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        setTreeValue(getTreeItems(Object.keys(DEFAULT_CONFIGURATION), true));
                                    }}
                                >
                                    {t("Reset to Default")}
                                </Button>
                                <Button buttonType={ButtonTypes.TEXT} onClick={closeModal}>
                                    {t("Cancel")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {t("Apply")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>
        </div>
    );
}
