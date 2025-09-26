/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { postTypeSettingsClasses } from "@dashboard/postTypes/pages/postTypeSettings.classes";
import { PostField, PostFieldDeleteMethod } from "@dashboard/postTypes/postType.types";
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
import PostFieldsReorderModal from "@dashboard/postTypes/components/PostFieldReorderModal";
import { notEmpty } from "@vanilla/utils";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Translate from "@library/content/Translate";
import InputBlock from "@library/forms/InputBlock";
import { RadioPicker } from "@library/forms/RadioPicker";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import Message from "@library/messages/Message";
import SmartLink from "@library/routing/links/SmartLink";
import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";

interface IProps {}

export function PostFieldList(props: IProps) {
    const { postTypeID, isLoading, postFieldsByPostTypeID, addPostField, removePostField, reorderPostFields } =
        usePostTypeEdit();
    const classes = postTypeSettingsClasses();

    const [isEditVisible, setEditVisible] = useState(false);
    const [isReorderVisible, setReorderVisible] = useState(false);
    const [postFieldToEdit, setPostFieldToEdit] = useState<PostField | null>(null);
    const [fieldToDelete, setFieldToDelete] = useState<PostField | null>(null);
    const allPostTypesQuery = usePostTypeQuery();

    const impactedPostTypes = useMemo(() => {
        if (fieldToDelete && allPostTypesQuery.data) {
            return fieldToDelete.postTypeIDs?.map((postTypeID) => {
                return {
                    label: allPostTypesQuery.data.find((postType) => postType.postTypeID === postTypeID)?.name,
                    url: `/settings/post-types/edit/${postTypeID}`,
                };
            });
        }
        return [];
    }, [fieldToDelete, allPostTypesQuery.data]);

    const postFields = useMemo(() => {
        return postTypeID ? postFieldsByPostTypeID[postTypeID] : undefined;
    }, [postFieldsByPostTypeID, postTypeID]);

    const data = useMemo(() => {
        if (postFields && postFields.length > 0) {
            return postFields.map((postField) => ({
                "custom field label": postField.label,
                "api label": postField.postFieldID,
                type: postField.dataType,
                visibility: postField.visibility,
                // hidden
                original: postField,
            }));
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
        const existingIDs = postField.postTypeIDs ?? [];
        const appendedIDs = [...new Set([...existingIDs, postTypeID].filter(notEmpty))];
        addPostField({ ...postField, postTypeIDs: appendedIDs });
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
                        <Icon icon={"edit"} />
                    </Button>
                </ToolTip>
                <ToolTip label={t("Delete Post Field")}>
                    <Button
                        onClick={() => setFieldToDelete(props.data.original)}
                        buttonType={ButtonTypes.ICON}
                        ariaLabel={t("Delete Post Field")}
                    >
                        <Icon icon={"delete"} />
                    </Button>
                </ToolTip>
            </div>
        );
    }

    const [deleteMethod, setDeleteMethod] = useState<PostFieldDeleteMethod>("unlink");

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
                postTypeID={postTypeID}
                postField={postFieldToEdit}
                isVisible={isEditVisible}
                onConfirm={handleSubmit}
                onCancel={() => {
                    setEditVisible(false);
                    setPostFieldToEdit(null);
                }}
            />
            <PostFieldsReorderModal
                postFields={postFields ?? []}
                isVisible={isReorderVisible}
                onConfirm={(newOrder) => {
                    reorderPostFields(newOrder);
                    setReorderVisible(false);
                }}
                onCancel={() => {
                    setReorderVisible(false);
                }}
            />
            <Modal isVisible={!!fieldToDelete} size={ModalSizes.MEDIUM} titleID={"delete-field-Modal"}>
                <Frame
                    header={
                        <FrameHeader closeFrame={() => setFieldToDelete(null)} title={<>{t("Delete Post Field")}</>} />
                    }
                    body={
                        <FrameBody>
                            <div className={frameBodyClasses().contents}>
                                <>
                                    <div>
                                        {fieldToDelete && (
                                            <>
                                                <p>
                                                    <Translate
                                                        source={'You are about to delete "<0/>" field.'}
                                                        c0={fieldToDelete.label}
                                                    />
                                                </p>
                                            </>
                                        )}
                                    </div>
                                    <InputBlock required label={t("Delete Method")}>
                                        <RadioPicker
                                            pickerTitle={t("Delete Method")}
                                            value={deleteMethod}
                                            onChange={(val: PostFieldDeleteMethod) => setDeleteMethod(val)}
                                            options={[
                                                {
                                                    value: "unlink",
                                                    label: t("Unlink from Post Type"),
                                                    description: t(
                                                        "Remove this field from this post type only. This will not affect any other post types.",
                                                    ),
                                                },
                                                {
                                                    value: "delete",
                                                    label: t("Delete"),
                                                    description: t(
                                                        "Completely remove this post field from your community. This will also remove this fields from all other post types.",
                                                    ),
                                                },
                                            ]}
                                            dropdownContentsFullWidth
                                        />
                                    </InputBlock>
                                    <div>
                                        {deleteMethod === "delete" ? (
                                            <>
                                                <Message
                                                    type={"warning"}
                                                    stringContents={t(
                                                        "Completely remove this post field from your community.",
                                                    )}
                                                    contents={
                                                        <Translate
                                                            source={
                                                                "This action will affect the following post types: <0></0>"
                                                            }
                                                            c0={(_) => (
                                                                <>
                                                                    {impactedPostTypes.map(({ label, url }) => (
                                                                        <span key={label}>
                                                                            <SmartLink to={url}>{label}</SmartLink>{" "}
                                                                        </span>
                                                                    ))}
                                                                </>
                                                            )}
                                                        />
                                                    }
                                                />
                                            </>
                                        ) : (
                                            <>
                                                <p>
                                                    {t(
                                                        "Removing this field from this post type only. This will not affect any other post types.",
                                                    )}
                                                </p>
                                            </>
                                        )}
                                    </div>
                                </>
                            </div>
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter justifyRight>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => setFieldToDelete(null)}
                                className={frameFooterClasses().actionButton}
                            >
                                {t("Cancel")}
                            </Button>
                            <Button
                                buttonType={ButtonTypes.TEXT_PRIMARY}
                                className={frameFooterClasses().actionButton}
                                onClick={() => {
                                    if (fieldToDelete) {
                                        removePostField(fieldToDelete, deleteMethod);
                                        setFieldToDelete(null);
                                    }
                                }}
                            >
                                {deleteMethod === "delete" ? t("Delete") : t("Unlink")}
                            </Button>
                        </FrameFooter>
                    }
                />
            </Modal>
        </>
    );
}
