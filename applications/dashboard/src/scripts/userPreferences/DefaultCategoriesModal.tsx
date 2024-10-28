/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useState, useMemo, useCallback, useEffect } from "react";
import ProfileFieldsListClasses from "@dashboard/userProfiles/components/ProfileFieldsList.classes";
import UserPreferencesClasses from "@dashboard/userPreferences/UserPreferences.classes";
import { followedContentClasses } from "@library/followedContent/FollowedContent.classes";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { useQuery } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardAutoComplete } from "@dashboard/forms/DashboardAutoComplete";
import { AutoCompleteLookupOptions } from "@vanilla/ui";
import { Table } from "@dashboard/components/Table";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import ModalConfirm from "@library/modal/ModalConfirm";
import { MetaItem, Metas } from "@library/metas/Metas";
import { css, cx } from "@emotion/css";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";
import { useNotificationPreferencesContext } from "@library/notificationPreferences";
import {
    CATEGORY_NOTIFICATION_TYPES,
    getDefaultCategoryNotificationPreferences,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/CategoryNotificationPreferences.hooks";
import { useFormik } from "formik";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { IServerError } from "@library/@types/api/core";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import Message from "@library/messages/Message";

interface IProps {
    isVisible: boolean;
    onCancel(): void;
    initialValues: IFollowedCategory[];
    onSubmit(values: IFollowedCategory[]): Promise<void>;
}

export interface IFollowedCategory {
    categoryID: ICategory["categoryID"];
    preferences: Omit<ICategoryPreferences, "preferences.followed">;
}

export interface ILegacyCategoryPreferences {
    categoryID: ICategory["categoryID"];
    name: ICategory["name"];
    parentCategoryName?: string;
    iconUrl?: string;
    useEmailNotifications: boolean;
    postNotifications: string;
}

/**
 * Display a UI to allow users to set default followed categories and
 * default notification preferences for those categories
 *
 * Saves to config
 */
export default function DefaultCategoriesModal(props: IProps) {
    const { preferences } = useNotificationPreferencesContext();

    const [confirmExit, setConfirmExit] = useState(false);

    const classes = UserPreferencesClasses();
    const classesFrameBody = frameBodyClasses();

    /**
     * We require a central list of categories,
     * they may be updated as the config loads in, or as a user searches for one
     */
    const [categoriesList, setCategoriesList] = useState<Record<ICategory["categoryID"], ICategory>>({});

    /**
     * Helper function to format and store categories in `categoriesList`
     */
    const addToCategoryList = (categories: ICategory[]) => {
        const formattedCategories = categories.reduce(
            (previous, category) => ({ ...previous, [category.categoryID]: category }),
            {},
        );
        setCategoriesList((prev) => {
            return {
                ...prev,
                ...formattedCategories,
            };
        });
    };

    const [serverError, setServerError] = useState<IServerError | null>(null);

    const { values, dirty, setFieldValue, setValues, submitForm, isSubmitting, resetForm } = useFormik<{
        followedCategories: IFollowedCategory[];
    }>({
        initialValues: {
            followedCategories: props.initialValues,
        },
        enableReinitialize: true,
        onSubmit: async function (values) {
            setServerError(null);
            try {
                await props.onSubmit(
                    values.followedCategories.map((followedCategory) => ({
                        ...followedCategory,
                        preferences: {
                            ...followedCategory.preferences,
                            "preferences.followed": true,
                            ...(!followedCategory.preferences.hasOwnProperty("preferences.email.digest") && {
                                "preferences.email.digest": false,
                            }),
                        },
                    })),
                );
                props.onCancel();
            } catch (error) {
                setServerError(error);
            }
        },
    });

    // We might not have followed category data. Make a list of missing category IDs
    const missingCategoryIDs = values.followedCategories
        .filter(({ categoryID }) => !(`${categoryID}` in categoriesList))
        .map(({ categoryID }) => categoryID);

    // Get missing categories
    const {
        error: fetchCategoriesError,
        isLoading: missingCategoriesLoading,
        fetchStatus: missingCategoriesFetchStatus,
    } = useQuery<ICategory[] | undefined, IError>({
        queryKey: ["getMissingCategories", { missingCategoryIDs }],
        queryFn: async () => {
            const { data } = await apiv2.get<ICategory[]>("/categories/", {
                params: {
                    categoryID: missingCategoryIDs,
                },
            });
            addToCategoryList(data);
            return data;
        },
        retry: false,
        enabled: missingCategoryIDs.length > 0,
    });

    function handleExit() {
        props.onCancel();
        resetForm();
    }

    // These override the existing admin table styles
    const overrides = css({
        '& td[role="cell"], & th[role="columnheader"]': {
            padding: "0!important",
            "& > label": {
                paddingTop: 4,
                paddingBottom: 4,
            },
        },
    });

    const tableRows = useMemo(() => {
        if (
            values.followedCategories.length > 0 &&
            values.followedCategories
                .map((category) => category.categoryID)
                .every((categoryID) => categoryID in categoriesList)
        ) {
            return values.followedCategories.map((category, index) => {
                const categoryFull = categoriesList[category.categoryID];

                return {
                    "category name": (
                        <div className={classes.categoryName}>
                            {categoryFull.iconUrl && (
                                <div className={cx("photoWrap", followedContentClasses().photoWrap)}>
                                    <img
                                        src={categoryFull.iconUrl}
                                        className="CategoryPhoto"
                                        height="200"
                                        width="200"
                                    />
                                </div>
                            )}
                            <div>
                                <p>{categoryFull.name}</p>
                                <Metas>
                                    <MetaItem>
                                        {categoryFull.breadcrumbs?.[categoryFull.breadcrumbs?.length - 2]?.name ?? ""}
                                    </MetaItem>
                                </Metas>
                            </div>
                        </div>
                    ),
                    "notification preference": (
                        <>
                            <CategoryPreferencesTable
                                className={overrides}
                                preferences={category.preferences}
                                onPreferenceChange={async (change) => {
                                    setFieldValue(`followedCategories.${index}.preferences`, {
                                        ...category.preferences,
                                        ...change,
                                    });
                                }}
                                admin
                                canIncludeInDigest
                                notificationTypes={CATEGORY_NOTIFICATION_TYPES}
                            />
                        </>
                    ),
                    actions: (
                        <ToolTip label={t("Remove from default follow list")}>
                            <Button
                                buttonType={ButtonTypes.ICON}
                                onClick={() => {
                                    setValues(({ followedCategories }) => ({
                                        followedCategories: followedCategories.filter((_, i) => i !== index),
                                    }));
                                }}
                                name={t("Remove Category")}
                                role="button"
                                title={t("Remove Category")}
                            >
                                <Icon icon="data-trash" />
                            </Button>
                        </ToolTip>
                    ),
                };
            });
        }
        return [{ "category name": "No categories selected.", "notification preference": null, actions: null }];
    }, [categoriesList, values.followedCategories]);

    const closeIfUntouched = () => {
        if (!dirty) {
            handleExit();
        } else {
            setConfirmExit(true);
        }
    };

    return (
        <>
            <Modal
                isVisible={props.isVisible}
                size={ModalSizes.LARGE}
                exitHandler={() => {
                    closeIfUntouched();
                }}
            >
                <form
                    role="form"
                    onSubmit={async (e) => {
                        e.preventDefault();
                        await submitForm();
                    }}
                >
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    closeIfUntouched();
                                }}
                                title={t("Edit Default Categories")}
                            />
                        }
                        body={
                            <FrameBody className={classes.frameBody}>
                                {fetchCategoriesError && (
                                    <Message
                                        error={fetchCategoriesError}
                                        stringContents={fetchCategoriesError.message}
                                        className={classesFrameBody.error}
                                    />
                                )}

                                {serverError && (
                                    <Message
                                        error={serverError}
                                        stringContents={serverError.message}
                                        className={classesFrameBody.error}
                                    />
                                )}

                                <DashboardFormGroup
                                    label={t("Add Categories to Follow by Default")}
                                    description={t(
                                        "If no categories are selected, new users will not follow any categories by default.",
                                    )}
                                    noBorder
                                >
                                    <DashboardAutoComplete
                                        options={Object.keys(categoriesList)
                                            .filter((categoryID) => {
                                                return !values.followedCategories
                                                    .map((category) => `${category.categoryID}`)
                                                    .includes(categoryID);
                                            })
                                            .map((categoryID) => {
                                                return {
                                                    label: categoriesList[categoryID].name,
                                                    value: categoryID,
                                                    extraLabel:
                                                        categoriesList[categoryID].breadcrumbs?.[
                                                            categoriesList[categoryID].breadcrumbs?.length - 2
                                                        ]?.name ?? "",
                                                };
                                            })}
                                        optionProvider={
                                            <AutoCompleteLookupOptions
                                                lookup={{
                                                    searchUrl: "categories/search?displayAs[]=Discussions&query=%s",
                                                    singleUrl: `/categories/%s`,
                                                    labelKey: "name",
                                                    valueKey: "categoryID",
                                                }}
                                                handleLookupResults={useCallback((results) => {
                                                    addToCategoryList(results.map(({ data }) => data));
                                                }, [])}
                                                addLookupResultsToOptions={false}
                                            />
                                        }
                                        disabled={missingCategoriesLoading && missingCategoriesFetchStatus !== "idle"}
                                        onChange={(categoryID) => {
                                            setValues(({ followedCategories }) => {
                                                return {
                                                    followedCategories: followedCategories.concat([
                                                        {
                                                            categoryID,
                                                            preferences: getDefaultCategoryNotificationPreferences(
                                                                preferences?.data,
                                                            ),
                                                        },
                                                    ]),
                                                };
                                            });
                                        }}
                                    />
                                </DashboardFormGroup>
                                <div className={classes.tableWrap}>
                                    <Table
                                        tableClassNames={classes.table}
                                        headerClassNames={cx(
                                            ProfileFieldsListClasses().dashboardHeaderStyles,
                                            classes.headers,
                                        )}
                                        rowClassNames={ProfileFieldsListClasses().extendTableRows}
                                        cellClassNames={classes.cell}
                                        data={tableRows}
                                        sortable={true}
                                        customColumnSort={{ id: "category name", desc: false }}
                                        columnsNotSortable={["notification preference", "actions"]}
                                    />
                                </div>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        closeIfUntouched();
                                    }}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button submit buttonType={ButtonTypes.TEXT_PRIMARY}>
                                    {isSubmitting ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </form>
            </Modal>

            <ModalConfirm
                isVisible={confirmExit}
                title={t("Unsaved Changes")}
                onCancel={() => {
                    setConfirmExit(false);
                }}
                onConfirm={() => {
                    setConfirmExit(false);
                    handleExit();
                }}
                confirmTitle={t("Exit")}
            >
                {t("You have unsaved changes. Are you sure you want to exit without saving?")}
            </ModalConfirm>
        </>
    );
}
