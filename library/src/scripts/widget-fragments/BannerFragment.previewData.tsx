import { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type BannerFragment from "@vanilla/injectables/BannerFragment";
import { uuidv4 } from "@vanilla/utils";

const previewData: Array<IFragmentPreviewData<BannerFragment.Props>> = [
    {
        previewDataUUID: uuidv4(),
        name: "Basic",
        description: "A banner with all options enabled",
        data: {
            titleType: "static",
            title: "Banner Title",
            descriptionType: "static",
            description:
                "Sample Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
            showSearch: true,
            alignment: "center",
            textColor: "rgba(255, 255, 255, 1)",
            background: {
                color: "rgb(3, 108, 163)",
                useOverlay: false,
                imageSource: "styleGuide",
            },
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Minimal",
        description: "A banner with no background image, search and is left aligned",
        data: {
            titleType: "static",
            title: "Banner Title",
            descriptionType: "static",
            description:
                "Sample Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
            showSearch: false,
            alignment: "left",
            textColor: "rgba(255, 255, 255, 1)",
            background: {
                color: "rgb(3, 108, 163)",
            },
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Full",
        description: "A banner with a background image",
        data: {
            titleType: "static",
            title: "Banner Title",
            descriptionType: "static",
            description:
                "Sample Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
            showSearch: true,
            alignment: "center",
            textColor: "rgba(255, 255, 255, 1)",
            background: {
                color: "rgb(3, 108, 163)",
                useOverlay: true,
                imageSource: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
            },
        },
    },
];

export default previewData;
