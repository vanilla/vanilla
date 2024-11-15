/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { postTypeSettingsClasses } from "@dashboard/postTypes/pages/postTypeSettings.classes";
import { PostTypesSettingsProvider, usePostTypesSettings } from "@dashboard/postTypes/PostTypeSettingsContext";
import StackableTable, {
    CellRendererProps,
    StackableTableColumnsConfig,
} from "@dashboard/tables/StackableTable/StackableTable";
import { Icon } from "@vanilla/icons";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Heading from "@library/layout/Heading";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import { useEffect, useMemo, useState } from "react";
import { MemoryRouter } from "react-router";
import { ToolTip } from "@library/toolTip/ToolTip";
import { getIconForPostType, originalPostTypes } from "@dashboard/postTypes/utils";
import LinkAsButton from "@library/routing/LinkAsButton";
import { CategoryListModal } from "@dashboard/postTypes/components/CategoryListModal";
import { PostType } from "@dashboard/postTypes/postType.types";
import ModalConfirm from "@library/modal/ModalConfirm";
import { css } from "@emotion/css";
import { usePostTypeDelete } from "@dashboard/postTypes/postType.hooks";
import ButtonLoader from "@library/loaders/ButtonLoader";

interface IProps {}

/**
 * @deprecated
 */
export function PostTypeSettingsImpl(props: IProps) {
    const { status, postTypes, postTypesByPostTypeID, toggleActive } = usePostTypesSettings();
    const postTypeDelete = usePostTypeDelete();
    const [categoriesModal, setCategoriesModal] = useState<string | null>(null);
    const [postTypeToDelete, setPostTypeToDelete] = useState<PostType["postTypeID"] | null>(null);

    const classes = postTypeSettingsClasses();

    const data = useMemo(() => {
        return postTypes
            .map((postType) => ({
                "post label": postType.name,
                "api label": postType.postTypeID,
                "# of categories": postType.countCategories,
                active: postType.isActive,
            }))
            .sort((a, b) => a["post label"].localeCompare(b["post label"]));
    }, [postTypes]);

    const columnsConfiguration: StackableTableColumnsConfig = {
        "post label": {
            order: 1,
            wrapped: false,
            isHidden: false,
        },
        "api label": {
            order: 2,
            wrapped: false,
            isHidden: false,
        },
        "# of categories": {
            order: 3,
            wrapped: false,
            isHidden: false,
        },
        active: {
            order: 4,
            wrapped: false,
            isHidden: false,
        },
    };

    function CellRenderer(props: CellRendererProps) {
        const { data, columnName, wrappedVersion } = props;
        const enabled = data["active"];
        const [ownEnabled, setOwnEnabled] = useState(enabled);
        useEffect(() => {
            if (enabled !== ownEnabled) {
                setOwnEnabled(enabled);
            }
        }, [enabled]);
        switch (columnName) {
            case "post label": {
                const postType = postTypesByPostTypeID[data["api label"]];
                const typeID = postType?.parentPostTypeID ?? postType?.postTypeID;
                return (
                    <div className={classes.postLabelLayout}>
                        <span className={classes.iconBubble}>{getIconForPostType(typeID)}</span>
                        <span>{data[columnName]}</span>
                    </div>
                );
            }
            case "# of categories": {
                return (
                    <div>
                        {wrappedVersion && (
                            <span className={classes.wrappedColumnHeading}>{columnName.toUpperCase()}: </span>
                        )}
                        <Button
                            className={classes.categoryButton}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            onClick={() => {
                                setCategoriesModal(data["api label"]);
                            }}
                        >
                            {data[columnName]}
                        </Button>
                    </div>
                );
            }
            case "active":
                return (
                    <>
                        <DashboardToggle
                            wrapperClassName={classes.toggleWrapper}
                            labelID={data["api label"]}
                            enabled={ownEnabled}
                            onChange={async (newState) => {
                                setOwnEnabled(newState);
                                toggleActive(data["api label"]);
                            }}
                        />
                    </>
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

    function ActionsCellRenderer(props) {
        return (
            <div className={classes.actionsLayout}>
                <ToolTip label={t("Edit Post Type")}>
                    <span>
                        <LinkAsButton
                            to={`/settings/post-types/edit/${props.data["api label"]}`}
                            buttonType={ButtonTypes.ICON}
                            ariaLabel={t("Edit Post Type")}
                        >
                            <Icon icon={"data-pencil"} />
                        </LinkAsButton>
                    </span>
                </ToolTip>
                <ToolTip
                    label={
                        originalPostTypes.includes(props.data["api label"])
                            ? t("Cannot delete original post type")
                            : t("Delete Post Type")
                    }
                >
                    <span>
                        <Button
                            onClick={() => setPostTypeToDelete(props.data["api label"])}
                            buttonType={ButtonTypes.ICON}
                            ariaLabel={t("Delete Post Type")}
                            disabled={originalPostTypes.includes(props.data["api label"])}
                        >
                            <Icon icon={"data-trash"} />
                        </Button>
                    </span>
                </ToolTip>
            </div>
        );
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

    return (
        <>
            <DashboardHeaderBlock
                title={t("Post Types")}
                actionButtons={
                    <LinkAsButton to={`/settings/post-types/new`} buttonType={ButtonTypes.OUTLINE}>
                        {t("Add Post Type")}
                    </LinkAsButton>
                }
            />
            <ErrorPageBoundary>
                <div className={dashboardClasses().extendRow}>
                    <StackableTable
                        data={data}
                        headerClassNames={classes.headerClasses}
                        rowClassNames={classes.rowClasses}
                        isLoading={status.postTypes === "loading"}
                        loadSize={5}
                        hiddenHeaders={["actions"]}
                        columnsConfiguration={columnsConfiguration}
                        CellRenderer={CellRenderer}
                        WrappedCellRenderer={WrappedCellRenderer}
                        actionsColumnWidth={100}
                        ActionsCellRenderer={ActionsCellRenderer}
                    />
                </div>
            </ErrorPageBoundary>
            <DashboardHelpAsset>
                <Heading>{t("About Post Types and Post Fields")}</Heading>
                <p>{t("You can configure and manage the post types available on your community from this page.")} </p>
                <SmartLink to="https://success.vanillaforums.com/kb/articles/TODO_MAKE_A_DRAFT">
                    {t("Learn more about post types and post fields")}
                </SmartLink>
            </DashboardHelpAsset>
            <CategoryListModal
                isVisible={!!categoriesModal}
                onVisibilityChange={() => setCategoriesModal(null)}
                postTypeID={categoriesModal}
            />
            <ModalConfirm
                isVisible={!!postTypeToDelete}
                title={
                    <>
                        {t("Delete Post Type")}: {postTypeToDelete && postTypesByPostTypeID[postTypeToDelete].name}
                    </>
                }
                onCancel={() => setPostTypeToDelete(null)}
                onConfirm={async () => {
                    if (postTypeToDelete) {
                        await postTypeDelete.mutateAsync(postTypeToDelete);
                        setPostTypeToDelete(null);
                    }
                }}
                cancelTitle={t("Cancel")}
                confirmTitle={postTypeDelete.isLoading ? <ButtonLoader /> : t("Delete")}
                confirmClasses={classes.emphasisColor}
                isConfirmDisabled={postTypeDelete.isLoading}
                bodyClassName={css({ justifyContent: "start" })}
            >
                {t("Are you sure you want to delete?")}
            </ModalConfirm>
        </>
    );
}

export function PostTypeSettings(props: IProps) {
    return (
        <PostTypesSettingsProvider>
            <PostTypeSettingsImpl {...props} />
        </PostTypesSettingsProvider>
    );
}

export default PostTypeSettings;
