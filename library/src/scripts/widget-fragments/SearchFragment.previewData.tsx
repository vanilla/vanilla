import { IFragmentPreviewData } from "@library/utility/fragmentsRegistry";
import type SearchFragment from "@library/widget-fragments/SearchFragment.injectable";
import { uuidv4 } from "@vanilla/utils";

const previewData: Array<IFragmentPreviewData<SearchFragment.Props>> = [
    {
        previewDataUUID: uuidv4(),
        name: "With Title",
        description: "A search bar with a title",
        data: {
            title: "Search your community!",
            description: "Search for anything you like",
        },
    },
    {
        previewDataUUID: uuidv4(),
        name: "Minimal",
        description: "A search bar without a title or description",
        data: {},
    },
];
export default previewData;
