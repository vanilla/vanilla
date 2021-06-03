/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentType, ReactNode } from "react";
import { ThumbnailGrid, ThumbnailGridItem } from "@dashboard/components/ThumbnailGrid.views";
import LayoutPreviewCard, { ILayoutPreviewCardProps } from "@dashboard/layout/components/LayoutPreviewCard";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import previewCardClasses from "@library/theming/PreviewCard.styles";
import layoutPreviewListClasses from "@dashboard/layout/components/LayoutPreviewList.classes";

interface ILayoutPreviewList {
    title: ReactNode;
    description: ReactNode;
    options: Array<{
        label: string;
        thumbnailComponent: ComponentType<{ className?: string }>;
        onApply?: ILayoutPreviewCardProps["onApply"];
        editUrl?: ILayoutPreviewCardProps["editUrl"];
        active?: ILayoutPreviewCardProps["active"];
    }>;
}

function LayoutPreviewList(props: ILayoutPreviewList) {
    const { title, description, options } = props;
    const classes = layoutPreviewListClasses();
    const classesPreview = previewCardClasses();

    return (
        <>
            <h2 className={classes.heading}>{title}</h2>
            <div>{description}</div>
            <ThumbnailGrid>
                {options.map((option) => (
                    <ThumbnailGridItem key={option.label}>
                        <LayoutPreviewCard
                            previewImage={
                                <option.thumbnailComponent className={css(Mixins.absolute.fullSizeOfParent())} />
                            }
                            onApply={option.onApply}
                            editUrl={option.editUrl}
                            active={option.active}
                        />
                        <h3 className={classesPreview.title}>{option.label}</h3>
                    </ThumbnailGridItem>
                ))}
            </ThumbnailGrid>
        </>
    );
}

export default LayoutPreviewList;
