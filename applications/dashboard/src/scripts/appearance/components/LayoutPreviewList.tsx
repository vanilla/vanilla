/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ComponentType, ReactNode } from "react";
import { ThumbnailGrid, ThumbnailGridItem } from "@dashboard/components/ThumbnailGrid.views";
import LayoutPreviewCard, { ILayoutPreviewCardProps } from "@dashboard/appearance/components/LayoutPreviewCard";
import { css } from "@emotion/css";
import { Mixins } from "@library/styles/Mixins";
import previewCardClasses from "@library/theming/PreviewCard.styles";

interface ILayoutPreviewList {
    options: Array<{
        label: string;
        thumbnailComponent: ComponentType<{ className?: string }>;
        onApply?: ILayoutPreviewCardProps["onApply"];
        editUrl?: ILayoutPreviewCardProps["editUrl"];
        active?: ILayoutPreviewCardProps["active"];
    }>;
}

function LayoutPreviewList(props: ILayoutPreviewList) {
    const { options } = props;
    const classesPreview = previewCardClasses();

    return (
        <ThumbnailGrid>
            {options.map((option) => (
                <ThumbnailGridItem key={option.label}>
                    <LayoutPreviewCard
                        previewImage={<option.thumbnailComponent className={css(Mixins.absolute.fullSizeOfParent())} />}
                        onApply={option.onApply}
                        editUrl={option.editUrl}
                        active={option.active}
                    />
                    <h3 className={classesPreview.title}>{option.label}</h3>
                </ThumbnailGridItem>
            ))}
        </ThumbnailGrid>
    );
}

export default LayoutPreviewList;
