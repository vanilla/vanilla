import { PostField } from "@dashboard/postTypes/postType.types";
import { ProfileFieldVisibilityIcon } from "@dashboard/userProfiles/components/ProfileFieldVisibilityIcon";
import DateTime from "@library/content/DateTime";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { Metas, MetaItem } from "@library/metas/Metas";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { PostMetaAssetClasses } from "@vanilla/addon-vanilla/posts/PostMetaAsset.classes";
import { formatList, getCurrentLocale, getJSLocaleKey, t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { RecordID } from "@vanilla/utils";
import type React from "react";

export interface ProfileFieldProp
    extends Pick<PostField, "postFieldID" | "label" | "description" | "dataType" | "visibility"> {
    value: RecordID | RecordID[] | boolean;
}

interface IProps {
    title?: string;
    subtitle?: string;
    description?: string;
    containerOptions?: IHomeWidgetContainerOptions;
    postFields?: ProfileFieldProp[];
    displayOptions?: {
        asMetas?: boolean;
    };
}

export function PostMetaAsset(props: IProps) {
    const { postFields, subtitle, description, title, containerOptions, displayOptions } = props;
    const { asMetas } = displayOptions ?? {};
    const globalVars = globalVariables.useAsHook();

    const _containerOptions: IHomeWidgetContainerOptions = {
        ...containerOptions,
        outerBackground: {
            ...(!asMetas && {
                color: ColorsUtils.colorOut(
                    globalVars.elementaryColors.primary.mix(globalVars.elementaryColors.white, 0.1),
                ),
            }),
            ...containerOptions?.outerBackground,
        },
        borderType: BorderType.NONE,
    };

    const classes = PostMetaAssetClasses.useAsHook({
        numberOfFields: postFields?.length,
    });

    const getFormattedValue = (field: ProfileFieldProp) => {
        const { dataType } = field;

        switch (dataType) {
            case "boolean":
                return field.value ? t("Yes") : t("No");
            case "date":
                return new Date(`${field.value}`).toLocaleString(getJSLocaleKey(), {
                    timeZone: "UTC",
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            case "string[]": {
                return formatList(field.value as string[]);
            }
            default:
                return field.value;
        }
    };

    if (!postFields || postFields?.length === 0) {
        return <></>;
    }

    return (
        <LayoutWidget>
            <ErrorBoundary>
                <HomeWidgetContainer
                    subtitle={subtitle}
                    description={description}
                    options={_containerOptions}
                    title={title}
                >
                    {postFields && postFields.length > 0 && (
                        <>
                            {asMetas ? (
                                <>
                                    <Metas>
                                        {postFields.map((field, index) => {
                                            const { postFieldID, label, visibility } = field;
                                            return (
                                                <>
                                                    <MetaItem key={postFieldID}>
                                                        <ProfileFieldVisibilityIcon visibility={visibility} />
                                                        <span className={classes.metaFieldName}>
                                                            <span>{label}</span>:&nbsp;
                                                        </span>
                                                        <span className={classes.metaFieldValue}>
                                                            {getFormattedValue(field)}
                                                        </span>
                                                    </MetaItem>
                                                    {index < postFields.length - 1 && <span>•</span>}
                                                </>
                                            );
                                        })}
                                    </Metas>
                                </>
                            ) : (
                                <ol className={classes.layout}>
                                    {postFields.map((field) => {
                                        const { postFieldID, label, description } = field;
                                        let infoTooltip: React.ReactNode = null;
                                        if (description) {
                                            infoTooltip = (
                                                <ToolTip label={description}>
                                                    <ToolTipIcon>
                                                        <Icon icon={"info"} />
                                                    </ToolTipIcon>
                                                </ToolTip>
                                            );
                                        }
                                        return (
                                            <li className={classes.field} key={postFieldID}>
                                                <span className={classes.fieldName}>
                                                    {label}
                                                    {infoTooltip}
                                                </span>
                                                <span className={classes.fieldValue}>{getFormattedValue(field)}</span>
                                            </li>
                                        );
                                    })}
                                </ol>
                            )}
                        </>
                    )}
                </HomeWidgetContainer>
            </ErrorBoundary>
        </LayoutWidget>
    );
}

export default PostMetaAsset;
