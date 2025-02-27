import { PostField } from "@dashboard/postTypes/postType.types";
import DateTime from "@library/content/DateTime";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { Widget } from "@library/layout/Widget";
import { Metas, MetaItem } from "@library/metas/Metas";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ToolTip } from "@library/toolTip/ToolTip";
import { PostMetaAssetClasses } from "@vanilla/addon-vanilla/posts/PostMetaAsset.classes";
import { getCurrentLocale, t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";

export interface ProfileFieldProp extends Pick<PostField, "postFieldID" | "label" | "description" | "dataType"> {
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
    const globalVars = globalVariables();

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

    const classes = PostMetaAssetClasses({
        numberOfFields: postFields?.length,
    });

    const getFormattedValue = (field: ProfileFieldProp) => {
        const { dataType } = field;

        switch (dataType) {
            case "boolean":
                return field.value ? t("Yes") : t("No");
            case "date":
                return <DateTime timestamp={`${field.value}`} />;
            case "string[]": {
                if (Intl?.["ListFormat"]) {
                    // We just checked if the browser supports it
                    // @ts-ignore: Property 'ListFormat' does not exist on type 'typeof Intl'.
                    const formatter = new Intl.ListFormat(getCurrentLocale());
                    return formatter.format(field.value);
                }
                return Array.isArray(field.value) && field.value.join(", ");
            }
            default:
                return field.value;
        }
    };

    if (!postFields || postFields?.length === 0) {
        return <></>;
    }

    return (
        <Widget>
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
                                            const { postFieldID, label } = field;
                                            return (
                                                <>
                                                    <MetaItem key={postFieldID}>
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
                                        return (
                                            <li className={classes.field} key={postFieldID}>
                                                <span className={classes.fieldName}>
                                                    <ToolTip label={description} key={postFieldID}>
                                                        <span>{label}</span>
                                                    </ToolTip>
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
        </Widget>
    );
}

export default PostMetaAsset;
