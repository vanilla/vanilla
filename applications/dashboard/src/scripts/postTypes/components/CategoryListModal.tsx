/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { PostType } from "@dashboard/postTypes/postType.types";
import { usePostTypesSettings } from "@dashboard/postTypes/PostTypeSettingsContext";
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
}

interface CategoryLinkResponse {
    results: INavigationVariableItem[];
    pagination: ILinkPages;
}

export function CategoryListModal(props: IProps) {
    const { postTypesByPostTypeID } = usePostTypesSettings();
    const name = props.postTypeID ? postTypesByPostTypeID[props.postTypeID]?.name : "";

    const categoryQuery = useQuery<any, IApiError, CategoryLinkResponse>({
        queryFn: async () => {
            const response = await apiv2.get("/categories", {
                params: {
                    postTypeID: props.postTypeID,
                    limit: 500,
                },
            });

            const links: INavigationVariableItem[] = response.data.map((category: ICategory) => {
                return {
                    id: category.categoryID,
                    name: category.name,
                    url: category.url,
                };
            });

            const pagination = SimplePagerModel.parseHeaders(response.headers);
            return { results: links, pagination: pagination };
        },
        queryKey: ["categoryList", props.postTypeID],
        enabled: props.isVisible && !!props.postTypeID,
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
                        title={<Translate source={"Categories Containing <0/> Posts"} c0={name} />}
                    />
                }
                body={
                    <FrameBody className={frameBodyClasses().root}>
                        {categoryQuery.isLoading ? (
                            <div style={{ padding: 16 }}>
                                <Loader small />
                            </div>
                        ) : (
                            <>
                                <QuickLinksView links={categoryQuery.data?.results ?? []} />
                            </>
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
