/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { postTypeSettingsClasses } from "@dashboard/postTypes/pages/postTypeSettings.classes";
import { PostField } from "@dashboard/postTypes/postType.types";
import StackableTable, {
    CellRendererProps,
    StackableTableColumnsConfig,
} from "@dashboard/tables/StackableTable/StackableTable";
import { Icon } from "@vanilla/icons";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";
import { useMemo, useState } from "react";
import { ToolTip } from "@library/toolTip/ToolTip";
import { TokenItem } from "@library/metas/TokenItem";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { PostFieldEditModal } from "@dashboard/postTypes/components/PostFieldEditModal";
import { PostFieldSubmit, usePostTypeEdit } from "@dashboard/postTypes/PostTypeEditContext";
import { css } from "@emotion/css";
import ModalConfirm from "@library/modal/ModalConfirm";
import PostFieldsReorderModal from "@dashboard/postTypes/components/PostFieldReorderModal";

interface IProps {}

export function PostFieldList(props: IProps) {
    const { postTypeID, isLoading, postFieldsByPostTypeID, addPostField, removePostField, reorderPostFields } =
        usePostTypeEdit();
    const classes = postTypeSettingsClasses();

    const [isEditVisible, setEditVisible] = useState(false);
    const [isReorderVisible, setReorderVisible] = useState(false);
    const [postFieldToEdit, setPostFieldToEdit] = useState<PostField | null>(null);
    const [fieldToDelete, setFieldToDelete] = useState<PostField | null>(null);

    const postFields = useMemo(() => {
        return postTypeID && postFieldsByPostTypeID[postTypeID];
    }, [postFieldsByPostTypeID, postTypeID]);

    const data = useMemo(() => {
        if (postFields && postFields.length > 0) {
            return postFields
                .map((postField) => ({
                    "custom field label": postField.label,
                    "api label": postField.postFieldID,
                    type: postField.dataType,
                    visibility: postField.visibility,
                    // hidden
                    original: postField,
                }))
                .sort((a, b) => (a.original.sort > b.original.sort ? 1 : -1));
        }
        return [];
    }, [postFields, postFieldsByPostTypeID]);

    const columnsConfiguration: StackableTableColumnsConfig = {
        "custom field label": {
            order: 1,
            wrapped: false,
            isHidden: false,
        },
        "api label": {
            order: 2,
            wrapped: false,
            isHidden: false,
        },
        type: {
            order: 3,
            wrapped: false,
            isHidden: false,
        },
        visibility: {
            order: 4,
            wrapped: false,
            isHidden: false,
        },
    };

    function CellRenderer(props: CellRendererProps) {
        const { data, columnName, wrappedVersion } = props;
        switch (columnName) {
            case "custom field label":
                return (
                    <div>
                        {data.original.isRequired && <span className={classes.emphasisColor}>*</span>}
                        {data[columnName]}
                    </div>
                );
            case "type":
                return (
                    <div>
                        {wrappedVersion && (
                            <span className={classes.wrappedColumnHeading}>{columnName.toUpperCase()}: </span>
                        )}
                        <TokenItem>{data[columnName]}</TokenItem>
                    </div>
                );
            default:
                return wrappedVersion ? (
                    <div>
                        <span className={classes.wrappedColumnHeading}>{columnName.toUpperCase()}: </span>
                        {data[columnName]}
                    </div>
                ) : (
                    <div>{data[columnName]}</div>
                );
        }
    }

    function WrappedCellRenderer(props: { orderedColumns: string[]; configuration: object; data: any }) {
        let result = <></>;
        if (props && props.orderedColumns && props.configuration && props.data)
            props.orderedColumns.forEach((columnName, index) => {
                if (!props.configuration[columnName].hidden && props.configuration[columnName].wrapped) {
                    result = (
                        <>
                            {index !== 0 && result}
                            <CellRenderer
                                data={props.data}
                                columnName={columnName}
                                wrappedVersion={props.configuration[columnName].wrapped}
                            />
                        </>
                    );
                }
            });

        return result;
    }

    const handleSubmit = (postField: PostFieldSubmit) => {
        addPostField({ ...postField, postTypeID });
    };

    function ActionsCellRenderer(props) {
        return (
            <div className={classes.actionsLayout}>
                <ToolTip label={t("Edit Post Field")}>
                    <Button
                        onClick={() => {
                            setPostFieldToEdit(props.data.original);
                            setEditVisible(true);
                        }}
                        buttonType={ButtonTypes.ICON}
                        ariaLabel={t("Edit Post Field")}
                    >
                        <Icon icon={"data-pencil"} />
                    </Button>
                </ToolTip>
                <ToolTip label={t("Delete Post Field")}>
                    <Button
                        onClick={() => setFieldToDelete(props.data.original)}
                        buttonType={ButtonTypes.ICON}
                        ariaLabel={t("Delete Post Field")}
                    >
                        <Icon icon={"data-trash"} />
                    </Button>
                </ToolTip>
            </div>
        );
    }

    return (
        <>
            <DashboardHeaderBlock
                title={t("Post Fields")}
                className={classes.bottomBorderOverride}
                actionButtons={
                    <div className={classes.actionButtonsContainer}>
                        <Button disabled={data.length === 0} onClick={() => setReorderVisible(true)}>
                            {t("Reorder")}
                        </Button>

                        <Button onClick={() => setEditVisible(true)}>{t("Add Field")}</Button>
                    </div>
                }
            />
            <div className={dashboardClasses().extendRow}>
                <StackableTable
                    data={data}
                    headerClassNames={classes.headerClasses}
                    rowClassNames={classes.rowClasses}
                    isLoading={isLoading}
                    loadSize={3}
                    hiddenHeaders={["original"]}
                    columnsConfiguration={columnsConfiguration}
                    CellRenderer={CellRenderer}
                    WrappedCellRenderer={WrappedCellRenderer}
                    actionsColumnWidth={100}
                    ActionsCellRenderer={ActionsCellRenderer}
                />
            </div>
            <PostFieldEditModal
                postField={postFieldToEdit}
                isVisible={isEditVisible}
                onConfirm={handleSubmit}
                onCancel={() => {
                    setEditVisible(false);
                    setPostFieldToEdit(null);
                }}
            />
            <PostFieldsReorderModal
                postFields={postTypeID ? postFieldsByPostTypeID[postTypeID] ?? [] : []}
                isVisible={isReorderVisible}
                onConfirm={(newOrder) => {
                    reorderPostFields(newOrder);
                    setReorderVisible(false);
                }}
                onCancel={() => {
                    setReorderVisible(false);
                }}
            />
            <ModalConfirm
                isVisible={!!fieldToDelete}
                title={
                    <>
                        {t("Delete Post Field")}: {fieldToDelete && fieldToDelete.label}
                    </>
                }
                onCancel={() => setFieldToDelete(null)}
                onConfirm={async () => {
                    if (fieldToDelete) {
                        removePostField(fieldToDelete);
                        setFieldToDelete(null);
                    }
                }}
                cancelTitle={t("Cancel")}
                confirmTitle={t("Remove")}
                confirmClasses={classes.emphasisColor}
                bodyClassName={css({ justifyContent: "start" })}
            >
                <p>{t("Are you sure you want to delete?")}</p>
                <p>
                    {t(
                        "New Posts will not show this post field. Posts which already contain this fields data will not be deleted.",
                    )}
                </p>
            </ModalConfirm>
        </>
    );
}
