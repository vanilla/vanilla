/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { usePostTypeQuery } from "@dashboard/postTypes/postType.hooks";
import { PostType } from "@dashboard/postTypes/postType.types";
import { css } from "@emotion/css";
import { Icon } from "@vanilla/icons";
import { useCategoryList } from "@library/categoriesWidget/CategoryList.hooks";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import InputBlock from "@library/forms/InputBlock";
import { NestedSelect } from "@library/forms/nestedSelect";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import Message from "@library/messages/Message";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { FilteredCategorySelector } from "@vanilla/addon-vanilla/createPost/FilteredCategorySelector";
import { t } from "@vanilla/i18n";
import { RecordID } from "@vanilla/utils";
import { useMemo, useState } from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { useMutation } from "@tanstack/react-query";
import { DiscussionsApi } from "@vanilla/addon-vanilla/posts/DiscussionsApi";
import Checkbox from "@library/forms/Checkbox";
import { useToast } from "@library/features/toaster/ToastContext";

interface IProps {
    onCancel: () => void;
    onSuccess?: () => Promise<void>;
    discussion: IDiscussion;
}

const movePostFormClasses = () => {
    const layout = css({
        // Fighting with a _very_ general margin rule in the message component
        marginTop: `${globalVariables().fonts.size.medium.toString()}px!important`,
    });
    const infoBlock = css({
        "& ul": {
            marginBlock: globalVariables().fonts.size.medium / 2,
            "& li": {
                textWrap: "pretty",
                marginBlockEnd: globalVariables().fonts.size.medium / 2,
                display: "flex",
            },
        },
    });
    const visibilityIcon = css({
        marginInlineStart: globalVariables().fonts.size.large * -1,
    });
    return { layout, infoBlock, visibilityIcon };
};

export default function MovePostFormLoadable(props: IProps) {
    const { onSuccess, onCancel, discussion } = props;
    const toast = useToast();

    const postTypeID: string = discussion.postTypeID ?? discussion.type;
    const category = discussion.category!;

    const classes = movePostFormClasses();
    const classesFrameBody = frameBodyClasses();
    const classFrameFooter = frameFooterClasses();

    const postTypeArray = usePostTypeQuery({ postTypeID }, !!postTypeID);
    const allPostTypes = usePostTypeQuery({
        isActive: true,
    });
    const postType = postTypeArray.data?.[0];

    const [targetCategoryID, setTargetCategoryID] = useState<ICategory["categoryID"] | undefined>(
        category.categoryID !== -1 ? category.categoryID : undefined,
    );
    const [targetPostTypeID, setTargetPostTypeID] = useState<PostType["postTypeID"] | undefined>(postTypeID);
    const [addRedirects, setAddRedirects] = useState(false);

    const mutation = useMutation({
        mutationFn: async ({
            categoryID,
            postTypeID,
        }: {
            categoryID: ICategory["categoryID"];
            postTypeID: PostType["postTypeID"];
            addRedirects: boolean;
        }) => {
            return await DiscussionsApi.move({
                discussionIDs: [discussion.discussionID],
                categoryID,
                postTypeID,
                addRedirects,
            });
        },
    });

    async function handleSubmit() {
        try {
            await mutation.mutateAsync({
                categoryID: targetCategoryID!,
                postTypeID: targetPostTypeID!,
                addRedirects,
            });
            void onSuccess?.();
        } catch (e) {
            toast.addToast({
                autoDismiss: false,
                dismissible: true,
                body: <Translate source="There was a problem moving <0/>" c0={discussion.name} />,
            });
            onCancel();
        }
    }

    const categoryQuery = useCategoryList({ categoryID: targetCategoryID }, !!targetCategoryID);
    let result = categoryQuery.data?.result ?? {};

    let selectedCategory = result?.[0] ?? category;

    const postTypeOptions = useMemo(() => {
        if (!targetCategoryID) {
            return (allPostTypes.data ?? [])?.map((postType) => {
                return {
                    label: postType.name,
                    value: postType.postTypeID,
                };
            });
        }
        return (selectedCategory.allowedPostTypeOptions ?? [])?.map((postType) => {
            return {
                label: postType.name,
                value: postType.postTypeID,
            };
        });
    }, [selectedCategory?.allowedPostTypeOptions, allPostTypes, targetCategoryID]);

    return (
        <form
            role="form"
            onSubmit={async (e) => {
                e.preventDefault();
                await handleSubmit();
            }}
        >
            <Frame
                header={<FrameHeader closeFrame={onCancel} title={t("Move Post")} />}
                body={
                    <FrameBody>
                        <div className={classesFrameBody.contents}>
                            <InputBlock label={t("Original Category")}>{category?.name}</InputBlock>
                            <InputBlock legend={<label>{t("Move to Category")}</label>} required>
                                <FilteredCategorySelector
                                    postTypeID={targetPostTypeID}
                                    initialValues={category.categoryID !== -1 ? category.categoryID : undefined}
                                    value={targetCategoryID}
                                    onChange={(categoryID: RecordID | undefined) => {
                                        setTargetCategoryID(categoryID ? (categoryID as number) : undefined);
                                    }}
                                    isClearable
                                    required
                                />
                            </InputBlock>

                            <InputBlock label={t("Original Post Type")}>
                                {postTypeArray.isLoading ? <LoadingRectangle width={80} /> : postType?.name}
                            </InputBlock>

                            <InputBlock legend={<label>{t("Convert to Post Type")}</label>} required>
                                <NestedSelect
                                    value={targetPostTypeID}
                                    options={postTypeOptions}
                                    onChange={(postTypeID: PostType["postTypeID"] | undefined) => {
                                        setTargetPostTypeID(postTypeID);
                                    }}
                                    isClearable
                                    required
                                />
                            </InputBlock>
                            <Message
                                type={"info"}
                                title={t("Field Conversion Information")}
                                icon={<Icon icon="info" />}
                                stringContents={""}
                                className={classes.layout}
                                contents={
                                    <div className={classes.infoBlock}>
                                        <p>{t("Shared post fields will be migrated as-is.")}</p>
                                        <p>
                                            {t(
                                                "Post fields which do not match will be converted in the following manner:",
                                            )}
                                        </p>
                                        <ul>
                                            <li>
                                                <Translate
                                                    source={
                                                        "<0>Public fields</0> will be add as text to <1>the post body</1>"
                                                    }
                                                    c0={(content) => {
                                                        return <strong>{content}&nbsp;</strong>;
                                                    }}
                                                    c1={(content) => {
                                                        return <strong>&nbsp;{content}</strong>;
                                                    }}
                                                />
                                            </li>
                                            <li>
                                                <Icon
                                                    className={classes.visibilityIcon}
                                                    icon={"visibility-private"}
                                                    size={"compact"}
                                                />
                                                <Translate
                                                    source={
                                                        "<0>Private fields</0> will be added to a <1>Private Data field</1>"
                                                    }
                                                    c0={(content) => {
                                                        return <strong>{content}&nbsp;</strong>;
                                                    }}
                                                    c1={(content) => {
                                                        return <strong>&nbsp;{content}</strong>;
                                                    }}
                                                />
                                            </li>
                                            <li>
                                                <Icon
                                                    className={classes.visibilityIcon}
                                                    icon={"visibility-internal"}
                                                    size={"compact"}
                                                />
                                                <Translate
                                                    source={
                                                        "<0>Internal fields</0> will be added to an <1>Internal Data field</1>"
                                                    }
                                                    c0={(content) => {
                                                        return <strong>{content}&nbsp;</strong>;
                                                    }}
                                                    c1={(content) => {
                                                        return <strong>&nbsp;{content}</strong>;
                                                    }}
                                                />
                                            </li>
                                        </ul>
                                    </div>
                                }
                            />
                            <InputBlock>
                                <Checkbox
                                    label={t("Leave a redirect link")}
                                    checked={addRedirects}
                                    onChange={(e) => setAddRedirects(e.target.checked)}
                                />
                            </InputBlock>
                        </div>
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <Button
                            buttonType={ButtonTypes.TEXT}
                            onClick={onCancel}
                            className={classFrameFooter.actionButton}
                        >
                            {t("Cancel")}
                        </Button>
                        <Button
                            submit
                            // disabled={values.category?.value === undefined || !dirty || isSubmitting}
                            buttonType={ButtonTypes.TEXT_PRIMARY}
                            className={classFrameFooter.actionButton}
                        >
                            {/* {isSubmitting ? <ButtonLoader /> : t("Submit")} */}
                            {t("Move")}
                        </Button>
                    </FrameFooter>
                }
            />
        </form>
    );
}
