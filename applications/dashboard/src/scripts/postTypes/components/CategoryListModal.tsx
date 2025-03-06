/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostType } from "@dashboard/postTypes/postType.types";
import { css } from "@emotion/css";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { frameBodyClasses } from "@library/layout/frame/frameBodyStyles";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import FrameHeader from "@library/layout/frame/FrameHeader";
import Loader from "@library/loaders/Loader";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { QuickLinksView } from "@library/navigation/QuickLinks.view";
import SimplePagerModel, { ILinkPages } from "@library/navigation/SimplePagerModel";
import { useQuery } from "@tanstack/react-query";
import { ICategory } from "@vanilla/addon-vanilla/categories/categoriesTypes";
import { t } from "@vanilla/i18n";

interface IProps {
    isVisible: boolean;
    onVisibilityChange: (isVisible: boolean) => void;
    postTypeID?: PostType["postTypeID"] | null;
    postTypeName?: PostType["name"] | null;
}

interface CategoryLinkResponse {
    results: INavigationVariableItem[];
    pagination: ILinkPages;
}

export function CategoryListModal(props: IProps) {
    const name = props.postTypeName ?? props.postTypeID;

    const categoryQuery = useQuery<any, IApiError, CategoryLinkResponse>({
        queryFn: async () => {
            const response = await apiv2.get<ICategory[]>("/categories", {
                params: {
                    postTypeStatus: "both",
                    postTypeID: props.postTypeID,
                    limit: 500,
                },
            });

            const links: INavigationVariableItem[] = response.data
                .filter(({ displayAs }) => displayAs === "discussions")
                .map((category: ICategory) => {
                    return {
                        id: `${category.categoryID}`,
                        name: category.name,
                        url: category.url,
                    };
                });

            const pagination = SimplePagerModel.parseHeaders(response.headers);
            return { results: links, pagination: pagination };
        },
        queryKey: ["categoryList", props.postTypeID, props.isVisible],
        enabled: props.isVisible && !!props.postTypeID,
    });

    const hasResults = categoryQuery.data?.results && categoryQuery.data?.results.length > 0;

    const container = css({
        paddingTop: 16,
        paddingBottom: 16,
    });

    return (
        <Modal
            isVisible={props.isVisible}
            exitHandler={() => props.onVisibilityChange(false)}
            size={ModalSizes.MEDIUM}
            titleID={"categoriesByPostType"}
        >
            <Frame
                header={
                    <FrameHeader
                        titleID={"categoriesByPostType"}
                        closeFrame={() => props.onVisibilityChange(false)}
                        title={
                            <Translate
                                source={"Categories containing <0/> posts"}
                                c0={() => {
                                    return <strong>&nbsp;&quot;{name}&quot;&nbsp;</strong>;
                                }}
                            />
                        }
                    />
                }
                body={
                    <FrameBody className={frameBodyClasses().root}>
                        {categoryQuery.isLoading ? (
                            <div style={{ padding: 16 }}>
                                <Loader small />
                            </div>
                        ) : (
                            <div className={container}>
                                {hasResults ? (
                                    <QuickLinksView links={categoryQuery.data?.results ?? []} />
                                ) : (
                                    <p>There are no categories assigned to this post type</p>
                                )}
                            </div>
                        )}
                    </FrameBody>
                }
                footer={
                    <FrameFooter justifyRight>
                        <>
                            <Button
                                buttonType={ButtonTypes.TEXT}
                                onClick={() => props.onVisibilityChange(false)}
                                className={frameFooterClasses().actionButton}
                            >
                                {t("Close")}
                            </Button>
                        </>
                    </FrameFooter>
                }
            />
        </Modal>
    );
}
