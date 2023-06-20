/**
 * @author Taylor Chance <tchance@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import React, { useState, useEffect, useMemo } from "react";
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
import CheckboxGroup from "@library/forms/CheckboxGroup";
import Checkbox from "@library/forms/Checkbox";
import { IError } from "@library/errorPages/CoreErrorMessages";
import ErrorMessages from "@library/forms/ErrorMessages";
import {
    ICategory,
    ICategoryFragment,
    CategoryPostNotificationType,
} from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { ToolTip } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import { useToast } from "@library/features/toaster/ToastContext";
import ModalConfirm from "@library/modal/ModalConfirm";
import { MetaItem, Metas } from "@library/metas/Metas";
import { cx } from "@emotion/css";

interface IProps {
    onCancel(): void;
}

interface IDefaultFollowedCategory extends ICategoryFragment {
    iconUrl?: string;
    useEmailNotifications: boolean;
    postNotifications: CategoryPostNotificationType | null;
    parentCategoryName: string;
}

const CONGIG_KEY = "preferences.categoryFollowed.defaults";

export default function DefaultCategoriesModal(props: IProps) {
    const defaultFollowedCategories = useConfigsByKeys([CONGIG_KEY]);
    const { isLoading: isPatchLoading, patchConfig, error: patchError } = useConfigPatcher();
    const [followedCategories, setFollowedCategories] = useState<IDefaultFollowedCategory[]>([]);
    const [showModal, setShowModal] = useState(false);
    const [confirmExit, setConfirmExit] = useState(false);
    const toast = useToast();

    useEffect(() => {
        setShowModal(true);
    }, []);

    useEffect(() => {
        if (defaultFollowedCategories.data && defaultFollowedCategories.data[CONGIG_KEY]) {
            setFollowedCategories(JSON.parse(defaultFollowedCategories.data[CONGIG_KEY]));
        }
    }, [defaultFollowedCategories]);

    const handleSubmit = (e) => {
        e.preventDefault();
        patchConfig({ [CONGIG_KEY]: JSON.stringify(followedCategories) }).then((result) => {
            if (result.meta.requestStatus === "fulfilled") {
                props.onCancel();
            }
        });
    };

    useEffect(() => {
        // When we first receive an error message add a toast.
        if (patchError?.message) {
            toast.addToast({
                dismissible: true,
                body: <>{patchError.message}</>,
            });
        }
    }, [patchError?.message]);

    const { isLoading, error, data } = useQuery<any, IError, ICategory[]>({
        queryKey: ["categoriesData"],
        queryFn: async () => {
            const response = await apiv2.get<ICategory[]>("categories?outputFormat=flat");
            return response.data;
        },
    });

    const categoryOptions = useMemo<IAutoCompleteOption[] | undefined>(() => {
        if (data) {
            return data
                .map((category) => {
                    let parentLabel;
                    const crumbLength = category.breadcrumbs?.length ?? 0;
                    if (crumbLength > 1) {
                        parentLabel = category.breadcrumbs?.[crumbLength - 2]?.name;
                    }

                    return {
                        label: category.name,
                        value: {
                            categoryID: category.categoryID,
                            name: category.name,
                            parentCategoryName: parentLabel,
                            iconUrl: category.iconUrl,
                            useEmailNotifications: false,
                            postNotifications: CategoryPostNotificationType.FOLLOW,
                        },
                        extraLabel: parentLabel,
                    };
                })
                .filter((category) => {
                    return !followedCategories.find((item) => item.name === category.label);
                });
        }
        return undefined;
    }, [data, followedCategories]);

    const tableRows = useMemo(() => {
        if (followedCategories.length) {
            return followedCategories.map((category, index) => ({
                "category name": (
                    <div className={UserPreferencesClasses().categoryName}>
                        {category.iconUrl && (
                            <div className={cx("photoWrap", followedContentClasses().photoWrap)}>
                                <img src={category.iconUrl} className="CategoryPhoto" height="200" width="200" />
                            </div>
                        )}
                        <div>
                            <p>{category.name}</p>
                            <Metas>
                                <MetaItem>{category.parentCategoryName}</MetaItem>
                            </Metas>
                        </div>
                    </div>
                ),
                "notification preference": (
                    <CheckboxGroup>
                        <Checkbox
                            label={t("Notify of new posts")}
                            infoToolTip={t(
                                "Post notifications must be enabled before new comment notifications and receiving notifications as emails are available.",
                            )}
                            labelBold={false}
                            onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                let newFollowedCategories = [...followedCategories];
                                if (event.target.checked) {
                                    newFollowedCategories[index].postNotifications =
                                        CategoryPostNotificationType.DISCUSSIONS;
                                } else {
                                    newFollowedCategories[index].postNotifications =
                                        CategoryPostNotificationType.FOLLOW;
                                    newFollowedCategories[index].useEmailNotifications = false;
                                }
                                setFollowedCategories(newFollowedCategories);
                            }}
                            checked={category.postNotifications !== CategoryPostNotificationType.FOLLOW}
                        />
                        {category.postNotifications !== CategoryPostNotificationType.FOLLOW && (
                            <>
                                <Checkbox
                                    label={t("Notify of new comments")}
                                    labelBold={false}
                                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                        let newFollowedCategories = [...followedCategories];
                                        newFollowedCategories[index].postNotifications = event.target.checked
                                            ? CategoryPostNotificationType.ALL
                                            : CategoryPostNotificationType.DISCUSSIONS;
                                        setFollowedCategories(newFollowedCategories);
                                    }}
                                    checked={category.postNotifications === CategoryPostNotificationType.ALL}
                                />
                                <Checkbox
                                    label={t("Send notifications as emails")}
                                    labelBold={false}
                                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                                        let newFollowedCategories = [...followedCategories];
                                        newFollowedCategories[index].useEmailNotifications = event.target.checked
                                            ? true
                                            : false;
                                        setFollowedCategories(newFollowedCategories);
                                    }}
                                    checked={category.useEmailNotifications}
                                />
                            </>
                        )}
                    </CheckboxGroup>
                ),
                actions: (
                    <ToolTip label={t("Remove from default follow list")}>
                        <Button
                            buttonType={ButtonTypes.ICON}
                            onClick={() => {
                                let newFollowedCategories = [...followedCategories];
                                newFollowedCategories[index].postNotifications = CategoryPostNotificationType.FOLLOW;
                                newFollowedCategories[index].useEmailNotifications = false;
                                setFollowedCategories(followedCategories.filter((item) => item !== category));
                            }}
                            name={t("Remove Category")}
                            role="button"
                        >
                            <Icon icon="data-trash" />
                        </Button>
                    </ToolTip>
                ),
            }));
        }
        return [{ "category name": "No categories selected.", "notification preference": null, actions: null }];
    }, [followedCategories]);

    return (
        <>
            <Modal
                isVisible={showModal}
                size={ModalSizes.LARGE}
                exitHandler={() => {
                    setConfirmExit(true);
                }}
            >
                <form onSubmit={handleSubmit}>
                    <Frame
                        header={
                            <FrameHeader
                                closeFrame={() => {
                                    setConfirmExit(true);
                                }}
                                title={t("Edit Default Categories")}
                            />
                        }
                        body={
                            <FrameBody className={UserPreferencesClasses().frameBody}>
                                {error && (
                                    <ErrorMessages errors={[error]} className={UserPreferencesClasses().errors} />
                                )}

                                <DashboardFormGroup
                                    label={t("Add Categories to Follow by Default")}
                                    description={t(
                                        "If no categories are selected, new users will not follow any categories by default.",
                                    )}
                                    className={UserPreferencesClasses().noBorder}
                                >
                                    <DashboardAutoComplete
                                        options={categoryOptions}
                                        disabled={isLoading}
                                        onChange={(category) => {
                                            setFollowedCategories([...followedCategories, category]);
                                        }}
                                    />
                                </DashboardFormGroup>
                                <Table
                                    tableClassNames={UserPreferencesClasses().table}
                                    headerClassNames={ProfileFieldsListClasses().dashboardHeaderStyles}
                                    rowClassNames={ProfileFieldsListClasses().extendTableRows}
                                    cellClassNames={UserPreferencesClasses().cell}
                                    data={tableRows}
                                    sortable={true}
                                    customColumnSort={{ id: "category name", desc: false }}
                                    columnsNotSortable={["notification preference", "actions"]}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight>
                                <Button
                                    buttonType={ButtonTypes.TEXT}
                                    onClick={() => {
                                        setConfirmExit(true);
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
