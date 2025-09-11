/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    useCustomPagesCreate,
    useCustomPagesDelete,
    useCustomPagesMutation,
} from "@dashboard/appearance/customPages/CustomPages.hooks";
import { useEffect, useMemo, useState } from "react";
import { BorderType } from "@library/styles/styleHelpersBorders";
import Button from "@library/forms/Button";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import { CustomPagesAPI } from "@dashboard/appearance/customPages/CustomPagesApi";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import DropDown from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import ErrorMessages from "@library/forms/ErrorMessages";
import { FramedModal } from "@library/modal/FramedModal";
import { Icon } from "@vanilla/icons";
import { ListItem } from "@library/lists/ListItem";
import Message from "@library/messages/Message";
import { MetaItem } from "@library/metas/Metas";
import ModalSizes from "@library/modal/ModalSizes";
import Notice from "@library/metas/Notice";
import { Row } from "@library/layout/Row";
import { SchemaFormBuilder } from "@library/json-schema-forms";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import Translate from "@library/content/Translate";
import { css } from "@emotion/css";
import { globalVariables } from "@library/styles/globalStyleVars";
import { metasClasses } from "@library/metas/Metas.styles";
import { notEmpty } from "@vanilla/utils";
import { t } from "@vanilla/i18n";
import { useCustomPageContext } from "@dashboard/appearance/customPages/CustomPages.context";
import { useHistory } from "react-router";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { useThemeCache } from "@library/styles/themeCache";
import { useToast } from "@library/features/toaster/ToastContext";

export function CustomPagesUI({ children }: { children: React.ReactNode }) {
    return <div>{children}</div>;
}

const customPageClasses = () => {
    return {
        metasContainerOverride: css({
            marginTop: "0!important",
        }),
        toggleMarginOverride: css({
            paddingRight: 0,
        }),
    };
};

function Item(props: CustomPagesAPI.Page) {
    const { seoTitle, seoDescription } = props;
    const classes = customPageClasses();
    return (
        <>
            <ListItem
                name={seoTitle}
                description={seoDescription}
                metas={<CustomPagesUI.Metas {...props} />}
                metasContainerClass={classes.metasContainerOverride}
                boxOptions={{ borderType: BorderType.SHADOW }}
                actionAlignment={"center"}
                primaryActions={<CustomPagesUI.PrimaryActions {...props} />}
                actions={<CustomPagesUI.Actions {...props} />}
            />
        </>
    );
}

const customPagesClasses = useThemeCache(() => {
    const metaLink = css({
        color: ColorsUtils.colorOut(globalVariables().elementaryColors.darkText),
    });
    return {
        metaLink,
    };
});

function Metas(props: CustomPagesAPI.Page) {
    const { url, status } = props;
    const metaClasses = metasClasses.useAsHook();
    const classes = customPagesClasses.useAsHook();
    const { hasPermission } = usePermissionsContext();
    const canManageSettings = hasPermission("site.manage");

    return (
        <>
            <MetaItem>
                <ConditionalWrap
                    condition={canManageSettings}
                    component={SmartLink}
                    componentProps={{ to: url, className: classes.metaLink, openInNewTab: true }}
                >
                    {url}
                    {canManageSettings && (
                        <Icon icon={"meta-external-compact"} className={metaClasses.alignVerticallyInMetaItem(12)} />
                    )}
                </ConditionalWrap>
            </MetaItem>
            {status === "unpublished" && (
                <MetaItem>
                    <Notice>{t("Unpublished")}</Notice>
                </MetaItem>
            )}
        </>
    );
}

function Actions(props: CustomPagesAPI.Page) {
    const { customPageID, status, layoutID, urlcode } = props;
    const { setPageToDelete, setPageToCopy, setPageToEdit } = useCustomPageContext();
    const { hasPermission } = usePermissionsContext();
    const hasAnalyticsPermission = hasPermission("data.view");

    const handleCopyPage = () => {
        // AIDEV-NOTE: Open copy modal instead of directly creating page
        setPageToCopy(props);
    };

    return (
        <Row gap={12} align={"center"}>
            <DropDown handleID={`customPageActions-${customPageID}`}>
                <DropDownItemButton onClick={() => setPageToEdit(props)}>{t("Edit Page Details")}</DropDownItemButton>
                <DropDownItemLink to={`/appearance/layouts/customPage/${layoutID}/edit`}>
                    {t("Edit Page Layout")}
                </DropDownItemLink>
                <DropDownItemButton onClick={handleCopyPage}>{t("Copy Layout")}</DropDownItemButton>
                <ConditionalWrap
                    condition={status === "published"}
                    component={ToolTip}
                    componentProps={{ label: t("Published pages cannot be deleted") }}
                >
                    <span>
                        <DropDownItemButton
                            onClick={() => {
                                setPageToDelete(customPageID);
                            }}
                            disabled={status === "published"}
                        >
                            {t("Delete")}
                        </DropDownItemButton>
                    </span>
                </ConditionalWrap>
                {hasAnalyticsPermission && (
                    <>
                        <DropDownItemSeparator />
                        <DropDownItemLink
                            to={`/analytics/v2/dashboards/drilldown/page?urlPath=${encodeURIComponent(urlcode)}`}
                        >
                            {t("See Page Analytics")}
                        </DropDownItemLink>
                    </>
                )}
            </DropDown>
        </Row>
    );
}

function PrimaryActions(props: CustomPagesAPI.Page) {
    const { customPageID, seoTitle: title, status, layoutID } = props;
    const classes = customPageClasses();

    const isActive = status === "published";
    const statusMutation = useCustomPagesMutation();
    const { setPageToEdit } = useCustomPageContext();
    const history = useHistory();

    const togglePublishState = () => {
        const params = { customPageID, status: isActive ? "unpublished" : "published" };
        statusMutation.mutate(params);
    };

    return (
        <>
            <DashboardFormGroup
                tag={"div"}
                label={<Translate source="Toggle publish state for <0/>" c0={title} />}
                labelType={"none"}
                noBorder
                className={classes.toggleMarginOverride}
            >
                <DashboardToggle
                    enabled={isActive}
                    onChange={() => {
                        togglePublishState();
                    }}
                    disabled={statusMutation.isLoading}
                />
            </DashboardFormGroup>
            <ToolTip label={t("Edit Page Layout")}>
                <Button
                    buttonType={"icon"}
                    onClick={() => {
                        history.push(`/appearance/layouts/customPage/${layoutID}/edit`);
                    }}
                >
                    <Icon icon={"edit"} />
                </Button>
            </ToolTip>
            <ToolTip label={t("Edit Page Details")}>
                <Button
                    buttonType={"icon"}
                    onClick={() => {
                        setPageToEdit(props);
                    }}
                >
                    <Icon icon={"settings"} />
                </Button>
            </ToolTip>
        </>
    );
}

function DeleteConfirmation() {
    const { pageToDelete, setPageToDelete } = useCustomPageContext();
    const deleteMutation = useCustomPagesDelete();

    const deletePage = () => {
        if (pageToDelete) {
            deleteMutation.mutate({ customPageID: pageToDelete });
        }
    };

    if (!pageToDelete) {
        return <></>;
    }

    return (
        <FramedModal
            padding={"all"}
            title={t("Are you sure you want to delete this page?")}
            onClose={() => setPageToDelete(null)}
            onFormSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                deletePage();
            }}
            footer={
                <>
                    <Button buttonType={"text"} onClick={() => setPageToDelete(null)}>
                        {t("Cancel")}
                    </Button>
                    <Button buttonType={"textPrimary"} type="submit">
                        {deleteMutation.isLoading ? <ButtonLoader /> : t("Delete")}
                    </Button>
                </>
            }
        >
            {t("This action cannot be undone.")}
        </FramedModal>
    );
}

type FormValues = {
    seoTitle: string;
    seoDescription: string;
    urlcode: string;
    siteSectionID: string;
    roleIDs: number[];
    rankIDs: number[];
};

function AddEditPageDetails() {
    const { pageToEdit, setPageToEdit, pageToCopy, setPageToCopy } = useCustomPageContext();
    const createPageMutation = useCustomPagesCreate();
    const updatePageMutation = useCustomPagesMutation();
    const history = useHistory();
    const { addToast } = useToast();

    const isNewPage = pageToEdit === "new";
    const isCopyMode = !!pageToCopy;

    // Form values state
    const initialValues = useMemo(() => {
        if (!isNewPage && pageToEdit) {
            return {
                seoTitle: pageToEdit.seoTitle,
                seoDescription: pageToEdit.seoDescription,
                urlcode: pageToEdit.urlcode,
                siteSectionID: pageToEdit.siteSectionID,
                roleIDs: pageToEdit.roleIDs,
                rankIDs: pageToEdit.rankIDs,
            };
        }
        return {
            seoTitle: "",
            seoDescription: "",
            urlcode: "",
            siteSectionID: "0",
            roleIDs: [],
            rankIDs: [],
        };
    }, [pageToEdit]);

    const [formValues, setFormValues] = useState<FormValues>(initialValues);

    // Update form values when pageToEdit changes
    useEffect(() => {
        setFormValues(initialValues);
    }, [initialValues]);

    // Schema for the form
    const schema = SchemaFormBuilder.create()
        .textBox(
            "seoTitle",
            "Page Title",
            "The title that will appear in search results and browser tabs. This is also used as the page heading.",
        )
        .required()
        .textArea(
            "seoDescription",
            "Page Description",
            "A brief description of what this page contains. This will appear in search results and when sharing on social media.",
        )
        .required()
        .subHeading("Location")
        .textBox(
            "urlcode",
            "Page URL Path",
            "The unique URL path for this page. Do not include your community domain or subcommunity path.",
        )
        .required()
        .selectLookup(
            "siteSectionID",
            "Subcommunity",
            "Select the subcommunity this page belongs to. Leave empty for the default subcommunity.",
            {
                searchUrl: "/subcommunities",
                singleUrl: "/subcommunities/%s",
                labelKey: "name",
                valueKey: "siteSectionID",
            },
            false,
            "Community Home",
        )
        .subHeading("Visibility")
        .selectLookup(
            "roleIDs",
            "Roles",
            "Select which user roles can view this page. Leave empty to allow all users to view.",
            {
                searchUrl: "/roles",
                singleUrl: "/roles/%s",
                labelKey: "name",
                valueKey: "roleID",
            },
            true,
            "All Roles",
        )
        .selectLookup(
            "rankIDs",
            "Ranks",
            "Select which user ranks can view this page. Leave empty to allow all users to view.",
            {
                searchUrl: "/ranks",
                singleUrl: "/ranks/%s",
                labelKey: "name",
                valueKey: "rankID",
            },
            true,
            "All Ranks",
        )
        .getSchema();

    if (!pageToEdit && !pageToCopy) {
        return <></>;
    }

    const handleFormSubmit = async () => {
        let payload: CustomPagesAPI.CreateParams = {
            ...formValues,
            siteSectionID: formValues.siteSectionID ?? 0,
            status: "unpublished",
            layoutData: {
                layout: [],
                name: formValues.seoTitle || "Untitled Page",
                titleBar: {
                    $hydrate: "react.titleBar",
                },
            },
        };

        // Mutate the payload based on the mode
        if (isCopyMode) {
            payload.copyLayoutID = pageToCopy.layoutID;
        }

        // Handle update
        if (pageToEdit && !isNewPage) {
            // Update existing page - exclude layoutData to preserve existing layout
            const { layoutData, ...updatePayload } = payload;
            const updatedPage = await updatePageMutation.mutateAsync({
                ...updatePayload,
                customPageID: pageToEdit?.customPageID,
                status: pageToEdit.status, // Preserve current status when updating
            });
            setPageToEdit(null);
            addToast({
                autoDismiss: true,
                body: <>{t("Success! Page updated")}</>,
            });
        }

        // Handle create
        if (isNewPage || isCopyMode) {
            const createdPage = await createPageMutation.mutateAsync({
                ...payload,
                ...(isCopyMode && { copyLayoutID: pageToCopy.layoutID }),
            });
            setPageToCopy(null);
            history.push(`/appearance/layouts/customPage/${createdPage.layoutID}/edit`);
        }
    };

    const isLoading = createPageMutation.isLoading || updatePageMutation.isLoading;

    const buttonConfirmText = isCopyMode ? t("Copy Page") : isNewPage ? t("Create Page") : t("Update Page");

    // AIDEV-NOTE: Modal title shows copy mode with original page name
    const modalTitle = isCopyMode ? (
        <Translate source={`Copy "<0/>"`} c0={pageToCopy.seoTitle} />
    ) : isNewPage ? (
        t("Create Page")
    ) : (
        t("Edit Page")
    );

    const handleModalClose = () => {
        setPageToCopy(null);
        setPageToEdit(null);
        createPageMutation.reset();
        updatePageMutation.reset();
    };

    return (
        <FramedModal
            size={ModalSizes.LARGE}
            padding={"all"}
            title={modalTitle}
            onClose={handleModalClose}
            onFormSubmit={(e) => {
                e.preventDefault();
                e.stopPropagation();
                void handleFormSubmit();
            }}
            footer={
                <>
                    <Button buttonType={"text"} onClick={handleModalClose} disabled={isLoading}>
                        {t("Cancel")}
                    </Button>
                    <Button buttonType={"textPrimary"} type="submit" disabled={isLoading}>
                        {isLoading ? <ButtonLoader /> : buttonConfirmText}
                    </Button>
                </>
            }
        >
            {(createPageMutation.isError || updatePageMutation.isError) && (
                <Message
                    type="error"
                    stringContents={`${createPageMutation.error?.message} ${updatePageMutation.error?.message}`}
                    contents={
                        <ErrorMessages errors={[createPageMutation.error, updatePageMutation.error].filter(notEmpty)} />
                    }
                />
            )}
            <DashboardSchemaForm
                instance={formValues}
                schema={schema}
                onChange={setFormValues}
                disabled={isLoading}
                fieldErrors={{
                    ...createPageMutation.error?.errors,
                    ...updatePageMutation.error?.errors,
                }}
            />
        </FramedModal>
    );
}

CustomPagesUI.ListItem = Item;
CustomPagesUI.Metas = Metas;
CustomPagesUI.PrimaryActions = PrimaryActions;
CustomPagesUI.Actions = Actions;
CustomPagesUI.DeleteConfirmation = DeleteConfirmation;
CustomPagesUI.AddEditPageDetails = AddEditPageDetails;
