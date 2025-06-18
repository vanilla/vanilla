/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { css } from "@emotion/css";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { createPostFormAssetClasses } from "@vanilla/addon-vanilla/createPost/CreatePostFormAsset.classes";
import { draftFormFooterContentClasses } from "@vanilla/addon-vanilla/drafts/components/DraftFormFooterContent.classes";

interface IProps {
    title?: string;
    description?: string;
    subtitle?: string;
    containerOptions?: IHomeWidgetContainerOptions;
}

const skeletonClasses = () => {
    const categoryTypeContainer = css({
        display: "flex",
        gap: 8,
    });
    const columnFlex = css({
        display: "flex",
        flexDirection: "column",
        gap: 8,
    });
    const tagsContainer = css({
        display: "flex",
        flexDirection: "column",
        gap: 8,
        "& span": {
            display: "flex",
            gap: 8,
        },
    });
    return { categoryTypeContainer, columnFlex, tagsContainer };
};

export function CreatePostFormAssetSkeleton(props: IProps) {
    const classes = createPostFormAssetClasses();
    const skeleton = skeletonClasses();
    return (
        <>
            <HomeWidgetContainer
                title={props.title}
                description={props.description}
                subtitle={props.subtitle}
                options={props.containerOptions}
            >
                <section className={classes.formContainer}>
                    <div className={skeleton.categoryTypeContainer}>
                        <LoadingRectangle width="50%" height="32px" />
                        <LoadingRectangle width="50%" height="32px" />
                    </div>
                    <div className={classes.main}>
                        <div className={skeleton.columnFlex}>
                            <LoadingRectangle width="120px" height="12px" />
                            <LoadingRectangle width="100%" height="32px" />
                            <LoadingRectangle width="80px" height="12px" />
                            <LoadingRectangle width="100%" height="32px" />
                        </div>
                        <div className={skeleton.columnFlex}>
                            <LoadingRectangle width="80px" height="12px" />
                            <LoadingRectangle width="100%" height="200px" />
                        </div>
                        <div className={skeleton.tagsContainer}>
                            <LoadingRectangle width="120px" height="12px" />
                            <LoadingRectangle width="100%" height="32px" />
                            <span>
                                <LoadingRectangle width="80px" height="32px" />
                                <LoadingRectangle width="120px" height="32px" />
                                <LoadingRectangle width="70px" height="32px" />
                            </span>
                        </div>
                    </div>
                    <div className={draftFormFooterContentClasses().footer}>
                        <LoadingRectangle width="150px" height="32px" />
                        <LoadingRectangle width="120px" height="32px" />
                    </div>
                </section>
            </HomeWidgetContainer>
        </>
    );
}
