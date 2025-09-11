import type { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type CallToActionFragment from "@library/widget-fragments/CallToActionFragment.injectable";
import imageUrl from "./CallToActionFragment.previewImage.png";
import { uuidv4 } from "@vanilla/utils";

const previewData: Array<IFragmentPreviewData<CallToActionFragment.Props>> = [
    {
        previewDataUUID: uuidv4(),
        name: "With Background Color",
        description: "A call to action with a background color and buttons",
        data: {
            title: "Check out the thing!",
            description: "The thing is really cool",
            button: {
                title: "Check it out",
                type: "primary",
                url: "https://vanillaforums.com",
                shouldUseButton: true,
            },
            background: {
                color: "#6752a1",
            },
            textColor: "#ffffff",
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "With Image",
        description: "A call to action with a background image and buttons",
        data: {
            title: "Check out the thing!",
            description: "The thing is really cool",
            button: {
                title: "Check it out",
                type: "primary",
                url: "https://vanillaforums.com",
                shouldUseButton: true,
            },
            secondButton: {
                title: "See more infromation",
                type: "standard",
                url: "https://vanillaforums.com",
            },
            background: {
                image: imageUrl,
            },
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Without Image",
        description: "A simple call to action with some text configured.",
        data: {
            title: "Check out the thing!",
            description: "The thing is really cool",
            background: {
                color: "#f4f3f4",
            },
            borderType: "shadow",
        },
    },
];

export default previewData;
