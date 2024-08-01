/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useEffect, useState } from "react";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import { IGetUsersQueryParams, useGetUsers } from "@dashboard/users/userManagement/UserManagement.hooks";
import ProfileLink from "@library/navigation/ProfileLink";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { USERS_LIMIT_PER_PAGE, USERS_MAX_PAGE_COUNT } from "@dashboard/users/userManagement/UserManagementUtils";
import NumberedPager from "@library/features/numberedPager/NumberedPager";
import { cx } from "@emotion/css";
import { AutomationRulesPreviewContent } from "@dashboard/automationRules/preview/AutomationRulesPreviewContent";
import { loadingPlaceholder } from "@dashboard/automationRules/AutomationRules.utils";
import { AutomationRulesPreviewContentHeader } from "@dashboard/automationRules/preview/AutomationRulesPreviewContentHeader";

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

    return (
        <>
            <AutomationRulesPreviewContentHeader
                contentType="Users"
                totalResults={countUsersResults}
                emptyResults={data?.users && data?.users.length === 0}
                fromStatusToggle={props.fromStatusToggle}
                hasError={Boolean(error)}
            />
            {isLoading && loadingPlaceholder("preview")}
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
