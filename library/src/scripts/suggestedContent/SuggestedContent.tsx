/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { IDiscussionItemOptions } from "@library/features/discussions/DiscussionList.variables";
import { suggestedContentClasses } from "@library/suggestedContent/SuggestedContent.classes";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { PageBox } from "@library/layout/PageBox";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import DiscussionsWidget from "@library/features/discussions/DiscussionsWidget";
import DiscussionListLoader from "@library/features/discussions/DiscussionListLoader";

export interface ISuggestedContentProps {
    discussions?: IDiscussion[];
    discussionOptions?: IDiscussionItemOptions;
    suggestedContent: {
        enabled?: boolean;
        title?: string;
        subtitle?: string;
        excerptLength?: number;
        featuredImage?: boolean;
        fallbackImage?: string;
        limit?: number;
    };
    containerOptions?: IHomeWidgetContainerOptions;
    isLoading?: boolean;
}

export function SuggestedContent(props: ISuggestedContentProps) {
    const { discussions, suggestedContent, discussionOptions, containerOptions, isLoading } = props;
    const { title, subtitle } = suggestedContent;
    const classes = suggestedContentClasses();

    return (
        <PageBox className={classes.layout}>
            <PageHeadingBox
                title={title}
                subtitle={subtitle}
                options={{
                    alignment: props.containerOptions?.headerAlignment,
                }}
            />
            <div>
                {isLoading && (
                    <DiscussionListLoader
                        count={suggestedContent.limit ?? 5}
                        displayType={containerOptions?.displayType}
                        containerProps={{ containerOptions: { ...containerOptions, borderType: undefined } }}
                    />
                )}
                {!isLoading && discussions && (
                    <DiscussionsWidget
                        apiParams={{
                            featuredImage: suggestedContent.featuredImage,
                            fallbackImage: suggestedContent.fallbackImage,
                        }}
                        discussions={discussions}
                        discussionOptions={{
                            ...discussionOptions,
                            featuredImage: {
                                display: suggestedContent.featuredImage,
                                ...(suggestedContent.fallbackImage && {
                                    fallbackImage: suggestedContent.fallbackImage,
                                }),
                            },
                        }}
                        containerOptions={{ ...containerOptions, borderType: undefined }}
                        noCheckboxes
                    />
                )}
            </div>
        </PageBox>
    );
}
