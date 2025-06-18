/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { suggestedContentClasses } from "@library/suggestedContent/SuggestedContent.classes";
import { ICategoryItem } from "@library/categoriesWidget/CategoryItem";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { PageBox } from "@library/layout/PageBox";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { useCurrentUser } from "@library/features/users/userHooks";
import { getMeta } from "@library/utility/appUtils";
import CategoryFollowDropdownWithNotificationPreferencesContext from "@vanilla/addon-vanilla/categories/CategoryFollowDropdown";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";

export interface ISuggestedFollowsProps {
    categories?: ICategoryItem[];
    suggestedFollows: {
        enabled?: boolean;
        title?: string;
        subtitle?: string;
        limit?: number;
    };
    containerOptions?: IHomeWidgetContainerOptions;
    isLoading?: boolean;
}

export function SuggestedFollows(props: ISuggestedFollowsProps) {
    const { categories, suggestedFollows, isLoading } = props;
    const { title, subtitle } = suggestedFollows;
    const classes = suggestedContentClasses();

    const user = useCurrentUser();
    const emailEnabled = getMeta("emails.digest", false);

    return (
        <PageBox className={classes.layout}>
            <PageHeadingBox
                title={title}
                subtitle={subtitle}
                options={{
                    alignment: props.containerOptions?.headerAlignment,
                }}
            />
            {user && (
                <div className={classes.categoryFollowButtonLayout}>
                    {isLoading &&
                        new Array(suggestedFollows.limit ?? 5)
                            .fill(null)
                            .map((_, index) => (
                                <LoadingRectangle
                                    width={Math.floor(Math.random() * (200 - 100) + 100)}
                                    height={32}
                                    inline
                                    key={index}
                                />
                            ))}
                    {!isLoading &&
                        categories?.map((category) => (
                            <CategoryFollowDropdownWithNotificationPreferencesContext
                                key={category.categoryID}
                                userID={user?.userID}
                                recordID={category.categoryID}
                                name={category.name}
                                emailDigestEnabled={emailEnabled}
                                size={"compact"}
                                nameAsLabel
                                viewRecordUrl={category.url}
                            />
                        ))}
                </div>
            )}
        </PageBox>
    );
}
