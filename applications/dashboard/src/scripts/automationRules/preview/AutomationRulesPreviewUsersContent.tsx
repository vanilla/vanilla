/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { useEffect, useMemo, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { IGetUsersQueryParams, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import ProfileLink from "@library/navigation/ProfileLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import Translate from "@library/content/Translate";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import { USERS_LIMIT_PER_PAGE, USERS_MAX_PAGE_COUNT } from "@dashboard/users/userManagement/UserManagementUtils";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { cx } from "@emotion/css";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import Message from "@library/messages/Message";
import { ErrorIcon } from "@library/icons/common";
import { AutomationRulesPreviewContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewContent";

interface IProps extends Omit<React.ComponentProps<typeof AutomationRulesPreviewContent>, "formValues"> {
    query: IGetUsersQueryParams;
}

export function AutomationRulesPreviewUsersContent(props: IProps) {
    const classes = automationRulesClasses();
    const [query, setQuery] = useState<IGetUsersQueryParams>(props.query);

    const { error, data, isLoading } = useGetUsers(query);

    const hasData = data?.users && data.users.length > 0;
    const countUsersResults = data?.countUsers ? parseInt(data?.countUsers) : 0;

    useEffect(() => {
        if (data?.users && data?.users.length === 0) {
            props.onPreviewContentLoad?.(true);
        }
    }, [data]);

    const message = useMemo(() => {
        if (countUsersResults > 0) {
            return (
                <>
                    <div className={classes.bold}>
                        <Translate
                            source={"Users Matching Criteria Now: <0 />"}
                            c0={
                                countUsersResults && countUsersResults >= USERS_MAX_PAGE_COUNT
                                    ? `${humanReadableNumber(countUsersResults)}+`
                                    : countUsersResults
                            }
                        />
                    </div>
                    <div>
                        {props.fromStatusToggle
                            ? t(
                                  "The action will apply to them when the rule is enabled. In future, other users who meet the trigger criteria will have the action applied to them as well.",
                              )
                            : t("The action will be applied to only them if you proceed.")}
                    </div>
                    <div className={classes.italic}>
                        {t("Note: Actions will not affect users that already have the associated action applied.")}
                    </div>
                </>
            );
        } else if (data?.users && data?.users.length === 0) {
            return (
                <>
                    {t("This will not affect anyone right now. It will affect those that meet the criteria in future.")}
                </>
            );
        }
    }, [data]);

    return (
        <>
            <div>{message}</div>
            {error && (
                <div className={classes.padded()}>
                    <Message
                        type="error"
                        stringContents={t(
                            "Failed to load the preview data. Please check your trigger and action values.",
                        )}
                        icon={<ErrorIcon />}
                    />
                </div>
            )}
            {isLoading && (
                <div className={classes.padded(true)} style={{ marginTop: 16 }}>
                    {Array.from({ length: 12 }, (_, index) => (
                        <div key={index} className={classes.flexContainer()} style={{ marginBottom: 16 }}>
                            <LoadingRectangle style={{ width: 25, height: 25, marginRight: 10, borderRadius: "50%" }} />
                            <LoadingRectangle style={{ width: "95%", height: 25 }} />
                        </div>
                    ))}
                </div>
            )}

            <div>
                {(hasData || countUsersResults >= USERS_MAX_PAGE_COUNT) && (
                    <NumberedPager
                        {...{
                            totalResults: parseInt(data?.countUsers ?? "0"),
                            currentPage: parseInt(data?.currentPage ?? "1"),
                            pageLimit: USERS_LIMIT_PER_PAGE,
                            hasMorePages: countUsersResults ? countUsersResults >= USERS_MAX_PAGE_COUNT : false,
                            className: classes.previewPager,
                            showNextButton: false,
                        }}
                        onChange={(page: number) => setQuery({ ...query, page: page })}
                        isMobile={false}
                    />
                )}
                {hasData && (
                    <ul>
                        {data.users.map((user, index) => (
                            <li key={index} className={classes.verticalGap}>
                                <ProfileLink
                                    userFragment={user}
                                    buttonType={ButtonTypes.RESET}
                                    className={cx(classes.flexContainer(true), classes.previewUserItem)}
                                >
                                    <UserPhoto size={UserPhotoSize.MEDIUM} userInfo={user} />
                                    <span>{user.name}</span>
                                </ProfileLink>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
