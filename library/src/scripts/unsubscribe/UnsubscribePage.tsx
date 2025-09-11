/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
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
    useUnsubscribeData,
    useSaveUnsubscribe,
    useUndoUnsubscribe,
    useGetPreferenceLink,
} from "@library/unsubscribe/unsubscribePageHooks";
import { t } from "@library/utility/appUtils";
import { useFormik } from "formik";
import isEmpty from "lodash-es/isEmpty";
import { useState } from "react";
import { useParams } from "react-router-dom";
import { IUnsubscribeData, IUnsubscribeToken } from "@library/unsubscribe/unsubscribePage.types";

function UnsubscribePageMultipleReasonsForm(props: {
    onSubmit: (data: IUnsubscribeData) => Promise<any>;
    data: IUnsubscribeData;
}) {
    const { onSubmit, data } = props;
    const { user, isUnfollowContent, preferences, followedContent } = data;

    const getPreferenceLink = useGetPreferenceLink();

    const prefLink = getPreferenceLink(user, isUnfollowContent);
    const classes = unsubscribePageClasses();

    const tmpSettingsList = [...preferences];
    if (followedContent) {
        tmpSettingsList.push(followedContent);
    }

    const initialValues = Object.fromEntries(tmpSettingsList.map(({ optionID, enabled }) => [optionID, enabled]));

    const { submitForm, values, setFieldValue, dirty, isSubmitting } = useFormik<typeof initialValues>({
        initialValues,
        onSubmit: async (newValues) => {
            await onSubmit({
                ...data,
                preferences: preferences?.map((pref) => ({
                    ...pref,
                    enabled: newValues[pref.optionID],
                })),
                followedContent: followedContent
                    ? {
                          ...followedContent,
                          enabled: newValues[followedContent.optionID],
                      }
                    : null,
            } as IUnsubscribeData);
        },
        enableReinitialize: true,
    });

    return (
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
                    void submitForm();
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
                            onChange={(event) => {
                                const { id, checked } = event.target;
                                void setFieldValue(id, checked);
                            }}
                        />
                    ))}
                </CheckboxGroup>
                <Button
                    buttonType={ButtonTypes.PRIMARY}
                    className={classes.saveButton}
                    submit
                    disabled={!dirty || isSubmitting}
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

/**
 * Page the user is directed to from email to unsubscribe from notifications
 */
export function UnsubscribePageContent(props: { data: IUnsubscribeData }) {
    const {
        data: {
            user,
            isUnfollowContent,
            isAlreadyProcessed,
            isEmailDigest,
            followedContent,
            preferences,
            mutedContent,
        },
    } = props;

    const getPreferenceLink = useGetPreferenceLink();

    const classes = unsubscribePageClasses();
    const prefLink = getPreferenceLink(user, isUnfollowContent);

    // Unsubscribe/Unfollow request has already been processed
    if (isAlreadyProcessed) {
        return (
            <>
                <p className={classes.info}>{t("Your request to unsubscribe has already been processed.")}</p>
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
    else if (isEmailDigest) {
        return <p className={classes.digestInfo}>{t("You will no longer receive the email digest.")}</p>;
    }
    // Render Unfollow Content landing page
    else if (followedContent && followedContent.preferenceName.toLowerCase() === "follow") {
        return <div className={classes.infoLight}>{followedContent.label}</div>;
    }
    // Render Digest Hide Content landing page
    else if (followedContent && followedContent.preferenceName.toLowerCase() === "digest") {
        return (
            <>
                <div className={classes.infoLight}>{t("This content will no longer appear in your email digest.")}</div>
                {followedContent.label}
            </>
        );
    }

    // Simple notification with single reason
    else {
        if (mutedContent?.label || followedContent?.label || preferences.length > 0) {
            return (
                <>
                    <p className={classes.info}>{t("You will no longer receive email notifications for")}:</p>
                    {mutedContent?.label ?? followedContent?.label ?? preferences?.[0]?.label}
                </>
            );
        }

        return null;
    }
}

export function UnsubscribePageImpl(props: { token: IUnsubscribeToken }) {
    const { token } = props;
    const [error, setError] = useState<IError | undefined>(undefined);

    const { isFullWidth } = useSection();
    const getPreferenceLink = useGetPreferenceLink();

    const handleError = setError;

    const [undone, setUndone] = useState(false);

    const { mutateAsync: undoUnsubscribe, isLoading: undoLoading } = useUndoUnsubscribe(token);

    async function handleUndo() {
        try {
            await undoUnsubscribe(token);
            setUndone(true);
        } catch (error) {
            setUndone(false);
            handleError(error);
        }
    }

    const { data, error: dataError, isLoading: unsubscribeDataLoading } = useUnsubscribeData(token);

    const { mutateAsync: saveUnsubscribe, isSuccess: unsubscribeSucceeded } = useSaveUnsubscribe(token);

    if (dataError || error) {
        <ErrorPage error={dataError ?? error} />;
    }

    if (!data) {
        return null;
    }

    const {
        isAlreadyProcessed: isAlreadyProcessed,
        followedContent,
        hasMultiple,
        user,
        isUnfollowContent,
        preferences,
        mutedContent,
    } = data;

    const isLoading = unsubscribeDataLoading || undoLoading;

    let title: string;

    if (undone) {
        title = t("Notification Settings Restored");
    } else {
        if (mutedContent) {
            title = t("Post Successfully Muted");
        } else if (isAlreadyProcessed) {
            title = t("Request Processed");
        } else if (followedContent && followedContent.preferenceName.toLowerCase() === "follow") {
            title = t("Unfollow Successful");
        } else if (followedContent && followedContent.preferenceName.toLowerCase() === "digest") {
            title = t("Email Digest Preferences Updated");
        } else if (hasMultiple) {
            title = unsubscribeSucceeded ? t("Unsubscribe Successful") : t("Unsubscribe");
        } else if (isLoading) {
            title = t("Processing Request");
        } else {
            title = t("Unsubscribe Successful");
        }
        if (undone) {
            title = t("Notification Settings Restored");
        }
    }

    const classes = unsubscribePageClasses();

    const userFragment = {
        userID: user?.userID ?? 0,
        name: user?.name ?? "",
    };

    const userInfoContent = isEmpty(data) ? null : (
        <div className={classes.userInfo}>
            <ProfileLink userFragment={userFragment} isUserCard>
                <UserPhoto userInfo={user} size={UserPhotoSize.MEDIUM} styleType={UserIconTypes.DEFAULT} />
            </ProfileLink>
            <div className={classes.username}>
                <ProfileLink userFragment={userFragment} isUserCard className={classes.usernameLink} />
                {user?.email}
            </div>
        </div>
    );
    const manageNotificationBtn = isEmpty(data) ? null : (
        <LinkAsButton
            to={getPreferenceLink(user, isUnfollowContent)}
            buttonType={ButtonTypes.STANDARD}
            className={classes.manageButton}
        >
            {isUnfollowContent ? t("Manage Followed Content") : t("Manage All Notifications")}
        </LinkAsButton>
    );

    let content = (
        <>
            <UnsubscribePageContent data={data} />
            {!isAlreadyProcessed && (
                <p className={classes.actions}>
                    {t("Change your mind?")}
                    <Button buttonType={ButtonTypes.TEXT_PRIMARY} className={classes.undoButton} onClick={handleUndo}>
                        {t("Undo")}
                    </Button>
                </p>
            )}
        </>
    );
    if (hasMultiple) {
        content = unsubscribeSucceeded ? (
            <>
                <p className={classes.info}>{t("You will no longer receive email notifications for")}:</p>
                {preferences.filter(({ enabled }) => !enabled).map(({ label }) => label)}
                {!followedContent?.enabled && followedContent?.label}
            </>
        ) : (
            <UnsubscribePageMultipleReasonsForm
                onSubmit={async (values) => {
                    try {
                        await saveUnsubscribe(values);
                    } catch (error) {
                        handleError(error);
                    }
                }}
                data={data}
            />
        );
    }
    if (undone) {
        content = <p className={classes.info}>{t("Your email notifications have been restored.")}</p>;
    }
    if (isLoading) {
        content = <Loader />;
    }

    return (
        <DocumentTitle title={t("Unsubscribe")}>
            <Backgrounds />
            <TitleBar onlyLogo />
            <Container>
                <SectionTwoColumns
                    mainTop={
                        <>
                            {!isFullWidth && userInfoContent}
                            <div className={classes.header}>
                                <Heading depth={1} title={title} className={classes.title} />
                                {isFullWidth && manageNotificationBtn}
                            </div>
                            <div className={classes.content}>
                                {content}
                                {!isFullWidth && !hasMultiple && !isAlreadyProcessed && manageNotificationBtn}
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
