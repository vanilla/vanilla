/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISuggestedContentProps, SuggestedContent } from "./SuggestedContent";
import { Widget } from "@library/layout/Widget";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { ISuggestedFollowsProps, SuggestedFollows } from "./SuggestedFollows";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { Icon } from "@vanilla/icons";
import { suggestedContentClasses } from "@library/suggestedContent/SuggestedContent.classes";
import {
    SuggestedContentQueryParams,
    useSuggestedContentQuery,
} from "@library/suggestedContent/SuggestedContent.hooks";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";

export type ISuggestedContentWidgetProps = ISuggestedContentProps &
    ISuggestedFollowsProps & {
        title?: string;
        subtitle?: string;
        description?: string;
        containerOptions?: IHomeWidgetContainerOptions;
        apiParams: SuggestedContentQueryParams;
        preview?: boolean;
    };

export function SuggestedContentWidget(props: ISuggestedContentWidgetProps) {
    const {
        title,
        subtitle,
        description,
        containerOptions,
        suggestedFollows,
        categories,
        suggestedContent,
        discussionOptions,
        discussions,
        apiParams,
        preview,
    } = props;

    const classes = suggestedContentClasses();

    const options = {
        ...containerOptions,
        borderType: containerOptions?.borderType,
        displayType: undefined,
        maxColumnCount: 1,
    };

    const suggestions = useSuggestedContentQuery(
        { ...apiParams },
        {
            discussions,
            categories,
        },
    );

    return (
        <Widget>
            <HomeWidgetContainer options={options}>
                <PageHeadingBox
                    pageHeadingClasses={classes.headerAlignment}
                    title={title}
                    subtitle={subtitle}
                    description={description}
                    options={{ alignment: containerOptions?.headerAlignment }}
                    actions={
                        <>
                            <ToolTip label={t("Refresh Suggestions")}>
                                <Button
                                    className={classes.refetchButton}
                                    buttonType={ButtonTypes.ICON}
                                    onClick={() => !preview && suggestions.refetch()}
                                >
                                    {suggestions.isFetching ? <ButtonLoader /> : <Icon icon={"data-replace"} />}
                                </Button>
                            </ToolTip>
                        </>
                    }
                />
                {suggestedFollows.enabled && (
                    <SuggestedFollows
                        isLoading={suggestions.isFetching}
                        suggestedFollows={suggestedFollows}
                        categories={suggestions?.data?.categories}
                        containerOptions={containerOptions}
                    />
                )}
                {suggestedContent.enabled && (
                    <SuggestedContent
                        isLoading={suggestions.isFetching}
                        suggestedContent={suggestedContent}
                        discussions={suggestions?.data?.discussions}
                        discussionOptions={discussionOptions}
                        containerOptions={containerOptions}
                    />
                )}
            </HomeWidgetContainer>
        </Widget>
    );
}

export default SuggestedContentWidget;
