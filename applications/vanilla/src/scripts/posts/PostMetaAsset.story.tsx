/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutEditorPreviewData } from "@dashboard/layout/editor/LayoutEditorPreviewData";
import PostMetaAsset from "@vanilla/addon-vanilla/posts/PostMetaAsset";

export default {
    title: "Widgets/PostMetaAsset",
};

export const Default = () => {
    return <PostMetaAsset postFields={LayoutEditorPreviewData.postFields()} />;
};

export const InThirds = () => {
    return <PostMetaAsset postFields={LayoutEditorPreviewData.postFields().filter((_, i) => i < 3)} />;
};

export const AsMetaString = () => {
    return <PostMetaAsset postFields={LayoutEditorPreviewData.postFields()} displayOptions={{ asMetas: true }} />;
};

export const WithWidgetOptions = () => {
    return (
        <PostMetaAsset
            title={"Title"}
            subtitle={"Subtitle"}
            description={"The most describable description"}
            postFields={LayoutEditorPreviewData.postFields()}
            containerOptions={{
                outerBackground: {
                    color: "#C0FFEE",
                },
            }}
        />
    );
};
