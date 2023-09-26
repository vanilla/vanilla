/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useState, useEffect, useMemo, FormEvent } from "react";
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
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardAutoComplete } from "@dashboard/forms/DashboardAutoComplete";
import { IAutoCompleteOption } from "@vanilla/ui";
import { Table } from "@dashboard/components/Table";
import { IError } from "@library/errorPages/CoreErrorMessages";
import ErrorMessages from "@library/forms/ErrorMessages";
import {
    ICategory,
    DEFAULT_NOTIFICATION_PREFERENCES,
    ICategoryPreferences,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import { useToast } from "@library/features/toaster/ToastContext";
import ModalConfirm from "@library/modal/ModalConfirm";
import { MetaItem, Metas } from "@library/metas/Metas";
import { css, cx } from "@emotion/css";
import { CategoryPreferencesTable } from "@library/preferencesTable/CategoryPreferencesTable";
import omit from "lodash/omit";
import { logDebug } from "@vanilla/utils";

interface IProps {
    isVisible: boolean;
    onCancel(): void;
}

interface IDefaultFollowedCategory extends ICategory {
    preferences: ICategoryPreferences;
}

export interface ISavedDefaultCategory {
    categoryID: ICategory["categoryID"];
    preferences: ICategoryPreferences;
}

export interface ILegacyCategoryPreferences {
    categoryID: ICategory["categoryID"];
    name: ICategory["name"];
    parentCategoryName?: string;
    iconUrl?: string;
    useEmailNotifications: boolean;
    postNotifications: string;
}

const CONFIG_KEY = "preferences.categoryFollowed.defaults";

/**
 * Display a UI to allow users to set default followed categories and
 * default notification preferences for those categories
 *
 * Saves to config
 */
export default function DefaultCategoriesModal(props: IProps) {
    // Get the saved preferences
    const defaultFollowedCategories = useConfigsByKeys([CONFIG_KEY]);
    const { isLoading: isPatchLoading, patchConfig, error: patchError } = useConfigPatcher();

    // Cache form state by categoryID
    const [followedCategories, setFollowedCategories] = useState<
        Record<ICategory["categoryID"], IDefaultFollowedCategory>
    >([]);

    const [dirty, setDirty] = useState(false);
    const [confirmExit, setConfirmExit] = useState(false);
    const toast = useToast();

    const classes = UserPreferencesClasses();

    // Get a category list
    const {
        isLoading,
        error,
        data: categoriesList,
    } = useQuery<any, IError, ICategory[]>({
        queryKey: ["categoriesData"],
        queryFn: async () => {
            const response = await apiv2.get<ICategory[]>("categories?outputFormat=flat");
            return response.data;
        },
    });

    // Cross reference the config value and category list to build up default followed categories
    useEffect(() => {
        if (defaultFollowedCategories?.data?.[CONFIG_KEY] && categoriesList) {
            try {
                const parsedConfig: ISavedDefaultCategory[] | ILegacyCategoryPreferences[] = JSON.parse(
                    defaultFollowedCategories?.data?.[CONFIG_KEY],
                );

                setFollowedCategories((prev) => {
                    return convertOldConfig(parsedConfig).reduce((acc, current) => {
                        const category = categoriesList.find((item) => item.categoryID === current.categoryID);
                        if (category) {
                            return {
                                ...acc,
                                [current.categoryID]: {
                                    ...category,
                                    preferences: current.preferences,
                                },
                            };
                        }
                        return prev;
                    }, prev);
                });
            } catch (error) {
                logDebug(error);
            }
        }
    }, [defaultFollowedCategories, categoriesList, props.isVisible]);

    // Save a subset of ICategory to the config
    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const configValue: ISavedDefaultCategory[] = Object.values(followedCategories).reduce(
            (acc, currentCategory) => {
                return [
                    ...acc,
                    {
                        categoryID: currentCategory.categoryID,
                        preferences: {
                            ...currentCategory.preferences,
                            ...(!currentCategory.preferences.hasOwnProperty("preferences.email.digest") && {
                                "preferences.email.digest": false,
                            }),
                        },
                    },
                ];
            },
            [],
        );

        try {
            const serializedConfig = { [CONFIG_KEY]: JSON.stringify(configValue) };
            patchConfig(serializedConfig).then((result) => {
                if (result.meta.requestStatus === "fulfilled") {
                    props.onCancel();
                }
            });
        } catch (error) {
            logDebug(error);
        }
    };

    // Surface errors as toast
    useEffect(() => {
        // When we first receive an error message add a toast.
        if (patchError?.message) {
            toast.addToast({
                dismissible: true,
                body: <>{patchError.message}</>,
            });
        }
    }, [patchError?.message]);

    // Create dropdown list options from the returned category list
    const categoryOptions = useMemo<IAutoCompleteOption[]>(() => {
        if (categoriesList) {
            return categoriesList
                .map((category) => {
                    return {
                        label: category.name,
                        value: category.categoryID,
                        extraLabel: category.breadcrumbs?.[category.breadcrumbs?.length - 2]?.name ?? "",
                    };
                })
                .filter((category) => {
                    return !followedCategories[category.value];
                });
        }
        return [];
    }, [categoriesList, followedCategories]);

    // Update a followed category's preferences
    const updateCategoryPreference = (categoryID: ICategory["categoryID"], change: Partial<ICategoryPreferences>) => {
        setDirty(true);
        setFollowedCategories((prev) => {
            if (prev[categoryID]) {
                return {
                    ...prev,
                    [categoryID]: {
                        ...prev[categoryID],
                        preferences: {
                            ...prev[categoryID].preferences,
                            ...change,
                        },
                    },
                };
            }
            return prev;
        });
    };

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
        if (Object.keys(followedCategories).length > 0) {
            return Object.values(followedCategories).map((category) => {
                return {
                    "category name": (
                        <div className={classes.categoryName}>
                            {category.iconUrl && (
                                <div className={cx("photoWrap", followedContentClasses().photoWrap)}>
                                    <img src={category.iconUrl} className="CategoryPhoto" height="200" width="200" />
                                </div>
                            )}
                            <div>
                                <p>{category.name}</p>
                                <Metas>
                                    <MetaItem>
                                        {category.breadcrumbs?.[category.breadcrumbs?.length - 2]?.name ?? ""}
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
                                onPreferenceChange={(change) => updateCategoryPreference(category.categoryID, change)}
                                admin
                                canIncludeInDigest
                            />
                        </>
                    ),
                    actions: (
                        <ToolTip label={t("Remove from default follow list")}>
                            <Button
                                buttonType={ButtonTypes.ICON}
                                onClick={() => {
                                    setFollowedCategories((prev) => {
                                        return omit(prev, category.categoryID);
                                    });
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
    }, [followedCategories]);

    const addCategoryToFollowedList = (categoryID: ICategory["categoryID"]) => {
        const categoryListItem = categoriesList?.find((item) => item.categoryID === categoryID);
        if (categoryListItem) {
            setFollowedCategories((prev) => {
                return {
                    ...prev,
                    [categoryID]: {
                        ...categoryListItem,
                        preferences: { ...DEFAULT_NOTIFICATION_PREFERENCES, "preferences.followed": true },
                    },
                };
            });
        }
    };

    const closeIfUntouched = () => {
        if (!dirty) {
            props.onCancel();
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
                <form onSubmit={handleSubmit}>
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
                                {error && <ErrorMessages errors={[error]} className={classes.errors} />}

                                <DashboardFormGroup
                                    label={t("Add Categories to Follow by Default")}
                                    description={t(
                                        "If no categories are selected, new users will not follow any categories by default.",
                                    )}
                                    className={classes.noBorder}
                                >
                                    <DashboardAutoComplete
                                        options={categoryOptions}
                                        disabled={isLoading}
                                        onChange={(categoryID) => {
                                            addCategoryToFollowedList(categoryID);
                                            setDirty(true);
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
                                    {isPatchLoading ? <ButtonLoader /> : t("Save")}
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
                    setDirty(false);
                    props.onCancel();
                }}
                confirmTitle={t("Exit")}
            >
                {t(
                    "You are leaving the editor without saving your changes. Are you sure you want to exit without saving?",
                )}
            </ModalConfirm>
        </>
    );
}

// Check if we have  legacy config and it needs to be converted
const isOldConfig = (
    configs: ISavedDefaultCategory[] | ILegacyCategoryPreferences[],
): configs is ILegacyCategoryPreferences[] => {
    // Assume we don't have some weird mix of config shapes
    const config = configs[0];
    return config && Object.keys(config).some((key) => ["postNotifications", "useEmailNotifications"].includes(key));
};

/**
 * Convert the old notification preference structure to the new one
 *
 * This really shouldn't be needed as we ought to convert all sites which has the config with
 * some other script. But in the event we do not, this function will translate the old values
 * to the new granular ones
 */
function convertOldConfig(config: ISavedDefaultCategory[] | ILegacyCategoryPreferences[]): ISavedDefaultCategory[] {
    if (isOldConfig(config)) {
        return config.reduce((acc, current) => {
            const converted = {
                categoryID: current.categoryID,
                preferences: {
                    ...DEFAULT_NOTIFICATION_PREFERENCES,
                    "preferences.email.digest": false,
                    "preferences.followed": true,
                    /**
                     * The nesting of conditional values here is a little strange,
                     * but gets the job done without a huge if-else chain
                     */
                    ...(current.postNotifications === "discussions" && {
                        "preferences.popup.posts": true,
                        ...(current.useEmailNotifications && {
                            "preferences.email.posts": true,
                        }),
                    }),
                    ...(current.postNotifications === "all" && {
                        "preferences.popup.posts": true,
                        "preferences.popup.comments": true,
                        ...(current.useEmailNotifications && {
                            "preferences.email.comments": true,
                            "preferences.email.posts": true,
                        }),
                    }),
                },
            };
            return [...acc, converted];
        }, []);
    }
    return config;
}
