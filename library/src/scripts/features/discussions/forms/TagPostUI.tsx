/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { DashboardSchemaForm } from "@dashboard/forms/DashboardSchemaForm";
import { css, cx } from "@emotion/css";
import { Icon } from "@vanilla/icons";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { IComboBoxOption } from "@library/features/search/ISearchBarProps";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { JSONSchemaType } from "@library/json-schema-forms";
import Heading from "@library/layout/Heading";
import { TokenItem } from "@library/metas/TokenItem";
import { useQuery } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useState, useMemo, ReactNode } from "react";
import { ITag } from "@library/features/tags/TagsReducer";
import { tagPostUIClasses } from "@library/features/discussions/forms/TagPostUI.classes";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";

export const tagLookup = {
    searchUrl: "/tags?query=%s&limit=500",
    singleUrl: "/tags/%s",
    valueKey: "tagID",
    labelKey: "name",
    processOptions: (options: IComboBoxOption[]) => {
        return options.filter((option) => option.data.type === "User");
    },
};
interface TagPostUIProps {
    initialTagIDs?: Array<ITag["tagID"]>;
    onSelectedExistingTag?: (updatedExistingTags: Array<ITag["tagID"]>) => void;
    onSelectedNewTag?: (updatedNewTags: string[]) => void;
    fieldErrors?: IError | null;
    showPopularTags?: boolean;
    popularTagsTitle?: ReactNode;
    popularTagsLayoutClasses?: string;
}

/**
 * UI for adding tags to a post. Bring your own state.
 */
export function TagPostUI(props: TagPostUIProps) {
    const { hasPermission } = usePermissionsContext();

    const TAG_POST_SCHEMA: JSONSchemaType<{ tags: string | number }> = {
        type: "object",
        properties: {
            tags: {
                type: ["string", "number"],
                minLength: 1,
                maxLength: 100,
                "x-control": {
                    labelType: "none",
                    inputType: "select",
                    multiple: true,
                    optionsLookup: tagLookup,
                    createable: hasPermission("tags.add"),
                    isClearable: true,
                    label: t("Tags"),
                    noBorder: true,
                    createableLabel: t("Create and add tag"),
                },
            },
        },
    };

    const [values, setValues] = useState({ tags: props.initialTagIDs ?? [] });

    const classes = tagPostUIClasses();

    const popularTagsQuery = useQuery({
        queryKey: ["popularTags"],
        queryFn: async () => {
            const response = await apiv2.get("tags", {
                params: {
                    excludeNoCountDiscussion: false,
                    sort: "-countDiscussions",
                    type: "User",
                    limit: 10,
                    fields: "tagID,name",
                },
            });
            return response.data;
        },
    });

    const unselectedPopularTags: ITag[] = useMemo(() => {
        if (popularTagsQuery.data && popularTagsQuery.data.length > 0) {
            return popularTagsQuery.data.filter((tag: ITag) => !values.tags.includes(tag.tagID));
        }
    }, [popularTagsQuery.data, values]);

    const handleChange = (newValues) => {
        setValues(newValues);
        let existingTags: number[] = [];
        let newTags: string[] = [];
        newValues.tags.forEach((tag) => {
            if (typeof tag === "string") {
                newTags.push(tag);
            } else {
                existingTags.push(tag);
            }
        });
        props.onSelectedExistingTag?.(existingTags);
        props.onSelectedNewTag?.(newTags);
    };

    const addPopularTag = (tagID: number) => {
        setValues((prev) => {
            return { tags: [...prev.tags, tagID] };
        });
        const existingTagUpdateValues = [...values.tags.filter((tag) => typeof tag === "number"), tagID];
        props.onSelectedExistingTag?.(existingTagUpdateValues);
    };

    return (
        <>
            <DashboardSchemaForm
                fieldErrors={{
                    ...(props.fieldErrors && {
                        tags: [{ message: props.fieldErrors.message, field: "tags" }],
                    }),
                }}
                instance={values}
                onChange={(newValuesDispatch) => handleChange(newValuesDispatch())}
                schema={TAG_POST_SCHEMA}
            />
            {props.showPopularTags && unselectedPopularTags && unselectedPopularTags.length > 0 && (
                <>
                    {props.popularTagsTitle ? (
                        <>{props.popularTagsTitle}</>
                    ) : (
                        <Heading renderAsDepth={4} className={cx(classes.title)}>
                            {t("Popular Tags")}
                        </Heading>
                    )}
                    <div className={cx(classes.layout, props.popularTagsLayoutClasses)}>
                        {unselectedPopularTags.map((tag) => (
                            <Button
                                key={tag.tagID}
                                className={classes.button}
                                buttonType={ButtonTypes.CUSTOM}
                                onClick={() => addPopularTag(tag.tagID)}
                            >
                                <TokenItem className={classes.token}>
                                    <span>{tag.name}</span>
                                    <span>
                                        <Icon className={classes.icon} icon={"data-add"} size={"compact"} />
                                    </span>
                                </TokenItem>
                            </Button>
                        ))}
                    </div>
                </>
            )}
        </>
    );
}
