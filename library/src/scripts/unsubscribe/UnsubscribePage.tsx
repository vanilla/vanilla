/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { ErrorPage } from "@library/errorPages/ErrorComponent";
import Button from "@library/forms/Button";
import CheckBox from "@library/forms/Checkbox";
import CheckboxGroup from "@library/forms/CheckboxGroup";
import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@library/headers/TitleBar";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { UserIconTypes } from "@library/icons/titleBar";
import { Backgrounds } from "@library/layout/Backgrounds";
import Heading from "@library/layout/Heading";
import { useSection } from "@library/layout/LayoutContext";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import Container from "@library/layout/components/Container";
import Loader from "@library/loaders/Loader";
import ProfileLink from "@library/navigation/ProfileLink";
import DocumentTitle from "@library/routing/DocumentTitle";
import LinkAsButton from "@library/routing/LinkAsButton";
import SmartLink from "@library/routing/links/SmartLink";
import "@library/theming/reset";
import { unsubscribePageClasses } from "@library/unsubscribe/UnsubscribePage.classes";
import {
    useGetUnsubscribe,
    useSaveUnsubscribe,
    useUndoUnsubscribe,
    usePreferenceLink,
} from "@library/unsubscribe/unsubscribePageHooks";
import { t } from "@library/utility/appUtils";
import { useFormik } from "formik";
import isEmpty from "lodash/isEmpty";
import React, { ReactElement, useEffect, useRef, useState } from "react";
import { useParams } from "react-router-dom";
import { IUnsubscribeData, IUnsubscribeToken } from "@library/unsubscribe/unsubscribePage.types";

interface IProps {
    token: IUnsubscribeToken;
}

interface IPageContent {
    title?: string;
    body?: ReactElement;
    error?: IError;
}

const PAGE_LOADING = {
    title: t("Processing Request"),
    body: <Loader />,
};

/**
 * Page the user is directed to from email to unsubscribe from notifications
 */
export function UnsubscribePageImpl(props: IProps) {
    const { token } = props;
    const getPreferenceLink = usePreferenceLink();
    const { isLoading: getLoading, mutateAsync: getUnsubscribe } = useGetUnsubscribe(token);
    const { isLoading: undoLoading, mutateAsync: undoUnsubscribe } = useUndoUnsubscribe(token);
    const { isLoading: saveLoading, mutateAsync: saveUnsubscribe } = useSaveUnsubscribe(token);
    const selfRef = useRef<HTMLDivElement | null>(null);
    const { isFullWidth } = useSection();
    const classes = unsubscribePageClasses();
    const [unsubscribeData, setUnsubscribeData] = useState<Partial<IUnsubscribeData>>({});
    const [pageContent, setPageContent] = useState<IPageContent>(PAGE_LOADING);
    const saveBtnRef = useRef<HTMLButtonElement | null>(null);

    const { submitForm, setValues, values, setFieldValue } = useFormik({
        initialValues: {},
        onSubmit: async (newValues) => {
            const tmpPreferences = unsubscribeData.preferences?.map((pref) => ({
                ...pref,
                enabled: newValues[pref.optionID],
            }));
            const tmpFollowedCategory = unsubscribeData.followedCategory
                ? {
                      ...unsubscribeData.followedCategory,
                      enabled: newValues[unsubscribeData.followedCategory.optionID],
                  }
                : null;

            const newData = {
                ...unsubscribeData,
                preferences: tmpPreferences,
                followedCategory: tmpFollowedCategory,
            } as IUnsubscribeData;

            await saveUnsubscribe(newData, {
                onError: (error: IError) => {
                    setPageContent({ error });
                },
                onSettled: (data: IUnsubscribeData, error: IError) => {
                    setUnsubscribeData(newData);
                    setPageContent({
                        title: t("Unsubscribe Successful"),
                        body: (
                            <>
                                <p className={classes.info}>
                                    {t("You will no longer receive email notifications for")}:
                                </p>
                                {newData.preferences.filter(({ enabled }) => !enabled).map(({ label }) => label)}
                                {!newData.followedCategory?.enabled && newData.followedCategory?.label}
                            </>
                        ),
                        error,
                    });
                },
            });
        },
        enableReinitialize: true,
    });

    useEffect(() => {
        if (saveBtnRef.current) {
            saveBtnRef.current.disabled = Object.values(values).filter((val) => !val).length === 0;
        }
    }, [values, saveBtnRef]);

    const handleUndo = async () => {
        await undoUnsubscribe(token, {
            onError: (error: IError) => {
                setPageContent({ error });
            },
            onSettled: (data: IUnsubscribeData, error: IError) => {
                setUnsubscribeData(data);
                setPageContent({
                    error,
                    title: t("Notification Settings Restored"),
                    body: <p className={classes.info}>{t("Your email notifications have been restored.")}</p>,
                });
            },
        });
    };

    const fetchData = async () => {
        getUnsubscribe(token, {
            onError: (error: IError) => {
                setPageContent({ error });
            },
            onSettled: (data: IUnsubscribeData, error: IError) => {
                if (!data) {
                    const pageError = error || { message: t("Unsubscribe token is invalid.") };
                    setPageContent({ error: pageError });
                    return;
                }

                let title: string = "";
                let body: ReactElement = <></>;
                const prefLink = getPreferenceLink(data.user, data.isUnfollowCategory);

                const undoContent = (
                    <p className={classes.actions}>
                        {t("Change your mind?")}
                        <Button
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classes.undoButton}
                            onClick={handleUndo}
                        >
                            {t("Undo")}
                        </Button>
                    </p>
                );

                // Unsubscribe/Unfollow request has already been processed
                if (data.isAllProcessed) {
                    title = t("Request Processed");
                    body = (
                        <>
                            <p className={classes.info}>
                                {t("Your request to unsubscribe has already been processed.")}
                            </p>
                            <p>
                                <Translate
                                    source="Further customize all notification settings on the <0>notification preferences page</0>."
                                    c0={(content) => <SmartLink to={prefLink}>{content}</SmartLink>}
                                />
                            </p>
                        </>
                    );
                }
                // Render Email Digest landing page
                else if (data.isEmailDigest) {
                    title = t("Unsubscribe Successful");
                    body = (
                        <>
                            <p className={classes.digestInfo}>{t("You will no longer receive the email digest.")}</p>
                            {undoContent}
                        </>
                    );
                }
                // Render Unfollow Category landing page
                else if (data.followedCategory && data.followedCategory.preferenceName.toLowerCase() === "follow") {
                    title = t("Unfollow Successful");
                    body = (
                        <>
                            <p className={classes.infoLight}>{data.followedCategory.label}</p>
                            {undoContent}
                        </>
                    );
                }
                // Render Digest Hide Category ladning page
                else if (data.followedCategory && data.followedCategory.preferenceName.toLowerCase() === "digest") {
                    title = t("Email Digest Preferences Updated");
                    body = (
                        <>
                            <p className={classes.infoLight}>
                                {t("This category will no longer appear in your email digest.")}
                            </p>
                            {data.followedCategory.label}
                            {undoContent}
                        </>
                    );
                }
                // Render multiple reasons as checkboxes
                else if (data.hasMultiple) {
                    const tmpSettingsList = [...data.preferences];
                    if (data.followedCategory) {
                        tmpSettingsList.push(data.followedCategory);
                    }

                    const tmpSettings = Object.fromEntries(
                        tmpSettingsList.map(({ optionID, enabled }) => [optionID, enabled]),
                    );

                    setValues(tmpSettings);

                    const togglePreference = (event) => {
                        const { id, checked } = event.target;
                        setFieldValue(id, checked);
                    };

                    title = t("Unsubscribe");
                    body = (
                        <>
                            <p className={classes.info}>
                                {t(
                                    "You are subscribed to the following email notifications, which are related to the notification you received.",
                                )}
                            </p>
                            <p>{t("Uncheck the notifications you no longer want to recieve.")}</p>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    submitForm();
                                }}
                                className={classes.multipleOptions}
                            >
                                <CheckboxGroup className={classes.checkboxGroup}>
                                    {tmpSettingsList.map(({ label, optionID }) => (
                                        <CheckBox
                                            key={optionID}
                                            label={label}
                                            labelBold={false}
                                            id={optionID}
                                            defaultChecked
                                            checked={values[optionID]}
                                            onChange={togglePreference}
                                        />
                                    ))}
                                </CheckboxGroup>
                                <Button
                                    buttonType={ButtonTypes.PRIMARY}
                                    className={classes.saveButton}
                                    submit
                                    disabled
                                    ref={saveBtnRef}
                                >
                                    {t("Save Changes")}
                                </Button>
                            </form>
                            <p>
                                <Translate
                                    source="Further customize all notification settings on the <0>notification preferences page</0>."
                                    c0={(content) => <SmartLink to={prefLink}>{content}</SmartLink>}
                                />
                            </p>
                        </>
                    );
                }
                // Simple notification with single reason
                else {
                    title = t("Unsubscribe Successful");
                    body = (
                        <>
                            <p className={classes.info}>{t("You will no longer receive email notifications for")}:</p>
                            {data.followedCategory?.label ?? data.preferences[0].label}
                            {undoContent}
                        </>
                    );
                }

                setUnsubscribeData(data);
                setPageContent({
                    title,
                    body,
                });
            },
        });
    };

    useEffect(() => {
        if (token && isEmpty(unsubscribeData)) {
            fetchData();
        }
    }, [token]);

    useEffect(() => {
        if (getLoading || undoLoading || saveLoading) {
            setPageContent(PAGE_LOADING);
        }
    }, [getLoading, undoLoading, saveLoading]);

    if (pageContent.error) {
        return <ErrorPage error={pageContent.error} />;
    }

    const userFragment = {
        userID: unsubscribeData.user?.userID ?? 0,
        name: unsubscribeData.user?.name ?? "",
    };

    const userInfoContent = isEmpty(unsubscribeData) ? null : (
        <div className={classes.userInfo}>
            <ProfileLink userFragment={userFragment} isUserCard>
                <UserPhoto
                    userInfo={unsubscribeData.user}
                    size={UserPhotoSize.MEDIUM}
                    styleType={UserIconTypes.DEFAULT}
                />
            </ProfileLink>
            <div className={classes.username}>
                <ProfileLink userFragment={userFragment} isUserCard className={classes.usernameLink} />
                {unsubscribeData.user?.email}
            </div>
        </div>
    );

    const manageNotificationBtn = isEmpty(unsubscribeData) ? null : (
        <LinkAsButton
            to={getPreferenceLink(unsubscribeData.user, unsubscribeData.isUnfollowCategory)}
            buttonType={ButtonTypes.STANDARD}
            className={classes.manageButton}
        >
            {unsubscribeData.isUnfollowCategory ? t("Manage Followed Categories") : t("Manage All Notifications")}
        </LinkAsButton>
    );

    return (
        <DocumentTitle title={t("Unsubscribe")}>
            <Backgrounds />
            <TitleBar onlyLogo />
            <Container>
                <SectionTwoColumns
                    contentRef={selfRef}
                    mainTop={
                        <>
                            {!isFullWidth && userInfoContent}
                            <div className={classes.header}>
                                <Heading depth={1} title={pageContent.title} className={classes.title} />
                                {isFullWidth && manageNotificationBtn}
                            </div>
                            <div className={classes.content}>
                                {pageContent.body}
                                {!isFullWidth &&
                                    !unsubscribeData?.hasMultiple &&
                                    !unsubscribeData?.isAllProcessed &&
                                    manageNotificationBtn}
                            </div>
                        </>
                    }
                    secondaryTop={isFullWidth && userInfoContent}
                />
            </Container>
        </DocumentTitle>
    );
}

export default function UnsubscribePage() {
    const { token } = useParams() as { token: IUnsubscribeToken };
    return <UnsubscribePageImpl token={token} />;
}
