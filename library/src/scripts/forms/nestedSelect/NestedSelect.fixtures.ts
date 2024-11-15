/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INestedSelectOptionProps } from "@library/forms/nestedSelect/NestedSelect.types";
import type { Select } from "@vanilla/json-schema-forms";
import get from "lodash-es/get";
import set from "lodash-es/set";

export const STORY_POKEMON_LOOKUP: Select.LookupApi = {
    searchUrl: "https://pokeapi.co/api/v2/pokemon?limit=150",
    singleUrl: "https://pokeapi.co/pokemon/%s",
    resultsKey: "results",
    excludeLookups: ["bulbasaur", "charmander"],
    labelKey: "name",
    extraLabelKey: "url",
};

export const STORY_COUNTRY_LOOKUP: Select.LookupApi = {
    searchUrl: "https://restcountries.com/v3.1/name/%s?fields=name,region,subregion",
    singleUrl: "https://restcountries.com/v3.1/name/%s?fullText=true&fields=name,region,subregion",
    defaultListUrl: "https://restcountries.com/v3.1/all?fields=name,region,subregion",
    labelKey: "name.official",
    valueKey: "name.common",
    processOptions: nestedLookupOptions,
};

function nestedLookupOptions(initialOptions: Select.Option[]): Select.Option[] {
    const mapping: any = {};

    initialOptions.forEach((opt) => {
        const { data } = opt;
        const groupName = data.region;
        const group = get(mapping, groupName, {
            label: groupName,
            children: {},
        });

        const subGroupName = data.subregion;
        if (subGroupName) {
            const subGroupKey = ["children", subGroupName].join(".");
            const subGroup = get(group, subGroupKey, {
                label: subGroupName,
                children: [],
            });

            subGroup.children.push(opt);
            set(group, subGroupKey, subGroup);
        } else {
            set(group, `children.${opt.label}`, opt);
        }

        set(mapping, groupName, group);
    });

    const options = Object.values(mapping).map(({ children, ...parent }) => ({
        ...parent,
        children: Object.values(children),
    }));

    return options as Select.Option[];
}

export const MOCK_SIMPLE_LIST: Select.Option[] = [
    { value: "apple", label: "Apple" },
    { value: "orange", label: "Orange" },
    { value: "banana", label: "Banana" },
    { value: "grape", label: "Grape" },
    { value: "strawberry", label: "Strawberry" },
    { value: "kiwi", label: "Kiwi" },
    { value: "watermelon", label: "Watermelon" },
];

export const MOCK_NESTED_LIST: Select.Option[] = [
    { value: "", label: "None" },
    { value: "all", label: "Select All" },
    {
        label: "Fruit",
        value: "fruit",
        children: [
            { value: "apple", label: "Apple" },
            { value: "orange", label: "Orange" },
            { value: "banana", label: "Banana" },
            { value: "grape", label: "Grape" },
            { value: "strawberry", label: "Strawberry" },
            { value: "kiwi", label: "Kiwi" },
            { value: "watermelon", label: "Watermelon" },
        ],
    },
    { value: "no-fruit", label: "No Fruit" },
    {
        label: "Vegetables",
        children: [
            { value: "all-vegetables", label: "Select All" },
            { value: "lettuce", label: "Lettuce" },
            { value: "carrot", label: "Carrot" },
            { value: "potato", label: "Potato" },
            { value: "broccoli", label: "Broccoli" },
            { value: "cauliflower", label: "Cauliflower" },
        ],
    },
];

export const MOCK_DEEP_NESTED_LIST: Select.Option[] = [
    {
        label: "Fruit",
        value: "fruit",
        children: [
            { value: "apple", label: "Apple" },
            { value: "orange", label: "Orange" },
            { value: "banana", label: "Banana" },
            { value: "grape", label: "Grape" },
            { value: "strawberry", label: "Strawberry" },
            { value: "kiwi", label: "Kiwi" },
            { value: "watermelon", label: "Watermelon" },
        ],
    },
    { value: "no-fruit", label: "No Fruit" },
    {
        label: "Shaved Ice",
        children: [
            { value: "no-flavor", label: "No Flavors" },
            { value: "no-toppings", label: "No Toppings" },
            {
                label: "Flavors",
                children: [
                    { value: "shavedIce-flavor-cherry", label: "Cherry" },
                    { value: "shavedIce-flavor-raspberry", label: "Raspberry" },
                    { value: "shavedIce-flavor-kiwi", label: "Kiwi" },
                    { value: "shavedIce-flavor-grape", label: "Grape" },
                    { value: "shavedIce-flavor-watermelon", label: "Watermelon" },
                ],
            },
            {
                label: "Topping",
                children: [
                    { value: "shavedIce-topping-cherry", label: "Cherry" },
                    { value: "shavedIce-topping-orange", label: "Orange" },
                    { value: "shavedIce-topping-cream", label: "Whip Cream" },
                ],
            },
        ],
    },
    { value: 1, label: "Chocolate Bar" },
    { value: 2, label: "Licorice" },
    {
        label: "Ice Cream",
        extraLabel: "Single scoop of vanilla ice cream with no toppings.",
        value: "iceCream",
        children: [
            {
                label: "Flavors",
                children: [
                    { value: "iceCream-flavor-vanilla", label: "Vanilla" },
                    { value: "iceCream-flavor-chocolate", label: "Chocolate" },
                    { value: "iceCream-flavor-strawberry", label: "Strawberry" },
                    { value: "iceCream-flavor-banana", label: "Banana" },
                ],
            },
            {
                label: "Toppings",
                children: [
                    { value: "iceCream-topping-chocolate", label: "Chocolate Syrup" },
                    { value: "iceCream-topping-caramel", label: "Caramel Syrup" },
                    { value: "iceCream-topping-cream", label: "Whip Cream" },
                    { value: "iceCream-topping-cherry", label: "Cherry" },
                    { value: "iceCream-topping-peanuts", label: "Peanuts" },
                ],
            },
        ],
    },
    {
        label: "Candy",
        children: [
            { value: "lollipop", label: "Lollipop" },
            { value: "gummy-bears", label: "Gummy Bears" },
            {
                label: "Chocolate",
                children: [
                    { value: "chocolate-truffle", label: "Truffle" },
                    { value: "chocolate-cherry", label: "Cherry" },
                    { value: "chocolate-peanuts", label: "Peanuts" },
                ],
            },
        ],
    },
];

export const MOCK_DEFAULT_RESPONSE: any[] = [
    {
        itemID: 1,
        name: "Apple Jack",
        type: "Earth Pony",
        animal: "Pony",
    },
    {
        itemID: 2,
        name: "Discord",
        type: null,
        animal: null,
    },
    {
        itemID: 3,
        name: "Rarity",
        type: "Unicorn",
        animal: "Pony",
    },
    {
        itemID: 4,
        name: "Pinky Pie",
        type: "Earth Pony",
        animal: "Pony",
    },
    {
        itemID: 5,
        name: "Rainbow Dash",
        type: "Pegasus",
        animal: "Pony",
    },
    {
        itemID: 6,
        name: "Spike",
        type: null,
        animal: "Dragon",
    },
    {
        itemID: 7,
        name: "Sparky",
        type: null,
        animal: "Dragon",
    },
    {
        itemID: 8,
        name: "Fluttershy",
        type: "Pegasus",
        animal: "Pony",
    },
    {
        itemID: 9,
        name: "Twilight Sparkle",
        type: "Alicorn",
        animal: "Pony",
    },
    {
        itemID: 10,
        name: "Sunny",
        type: "Alicorn",
        animal: "Pony",
    },
    {
        itemID: 11,
        name: "Izzy",
        type: "Unicorn",
        animal: "Pony",
    },
    {
        itemID: 12,
        name: "Hitch",
        type: "Earth Pony",
        animal: "Pony",
    },
    {
        itemID: 13,
        name: "Pipp",
        type: "Pegasus",
        animal: "Pony",
    },
    {
        itemID: 14,
        name: "Zipp",
        type: "Pegasus",
        animal: "Pony",
    },
];

export const MOCK_SEARCH_ALL_RESPONSE: any[] = [
    {
        itemID: 1,
        name: "Apple Jack",
        type: "Earth Pony",
        animal: "Pony",
    },
    {
        itemID: 2,
        name: "Discord",
        type: null,
        animal: null,
    },
    {
        itemID: 3,
        name: "Rarity",
        type: "Unicorn",
        animal: "Pony",
    },
    {
        itemID: 4,
        name: "Pinky Pie",
        type: "Earth Pony",
        animal: "Pony",
    },
    {
        itemID: 5,
        name: "Rainbow Dash",
        type: "Pegasus",
        animal: "Pony",
    },
    {
        itemID: 6,
        name: "Spike",
        type: null,
        animal: "Dragon",
    },
    {
        itemID: 8,
        name: "Fluttershy",
        type: "Pegasus",
        animal: "Pony",
    },
    {
        itemID: 9,
        name: "Twilight Sparkle",
        type: "Alicorn",
        animal: "Pony",
    },
];

export const MOCK_SEARCH_RESPONSE: any[] = [
    {
        itemID: 3,
        name: "Rarity",
        type: "Unicorn",
        animal: "Pony",
    },
    {
        itemID: 9,
        name: "Twilight Sparkle",
        type: "Alicorn",
        animal: "Pony",
    },
];

export const MOCK_SEARCH_NESTED_RESULT: INestedSelectOptionProps[] = [
    {
        label: "Pony",
        isHeader: true,
        group: undefined,
        depth: 0,
    },
    {
        label: "Earth Pony",
        isHeader: true,
        group: "[Pony]",
        depth: 0,
        tooltip: "Pony",
    },
    {
        label: "Apple Jack",
        value: 1,
        isHeader: false,
        extraLabel: undefined,
        group: "[Pony]>[Earth Pony]",
        depth: 1,
        tooltip: "Pony > Earth Pony",
        data: {
            itemID: 1,
            name: "Apple Jack",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Pinky Pie",
        value: 4,
        isHeader: false,
        extraLabel: undefined,
        group: "[Pony]>[Earth Pony]",
        depth: 1,
        tooltip: "Pony > Earth Pony",
        data: {
            itemID: 4,
            name: "Pinky Pie",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Unicorn",
        isHeader: true,
        group: "[Pony]",
        depth: 0,
        tooltip: "Pony",
    },
    {
        label: "Rarity",
        value: 3,
        isHeader: false,
        extraLabel: undefined,
        group: "[Pony]>[Unicorn]",
        depth: 1,
        tooltip: "Pony > Unicorn",
        data: {
            itemID: 3,
            name: "Rarity",
            animal: "Pony",
            type: "Unicorn",
        },
    },
    {
        label: "Pegasus",
        isHeader: true,
        group: "[Pony]",
        depth: 0,
        tooltip: "Pony",
    },
    {
        label: "Rainbow Dash",
        value: 5,
        extraLabel: undefined,
        isHeader: false,
        group: "[Pony]>[Pegasus]",
        depth: 1,
        tooltip: "Pony > Pegasus",
        data: {
            itemID: 5,
            name: "Rainbow Dash",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Fluttershy",
        value: 8,
        extraLabel: undefined,
        isHeader: false,
        group: "[Pony]>[Pegasus]",
        depth: 1,
        tooltip: "Pony > Pegasus",
        data: {
            itemID: 8,
            name: "Fluttershy",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Alicorn",
        isHeader: true,
        group: "[Pony]",
        depth: 0,
        tooltip: "Pony",
    },
    {
        label: "Twilight Sparkle",
        value: 9,
        isHeader: false,
        extraLabel: undefined,
        group: "[Pony]>[Alicorn]",
        depth: 1,
        tooltip: "Pony > Alicorn",
        data: {
            itemID: 9,
            name: "Twilight Sparkle",
            animal: "Pony",
            type: "Alicorn",
        },
    },
    {
        label: "Discord",
        value: 2,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 2,
            name: "Discord",
            animal: null,
            type: null,
        },
    },
    {
        label: "Dragon",
        isHeader: true,
        group: undefined,
        depth: 0,
    },
    {
        label: "Spike",
        value: 6,
        isHeader: false,
        extraLabel: undefined,
        group: "[Dragon]",
        depth: 0,
        tooltip: "Dragon",
        data: {
            itemID: 6,
            name: "Spike",
            animal: "Dragon",
            type: null,
        },
    },
];

export const MOCK_SEARCH_SIMPLE_RESULT: INestedSelectOptionProps[] = [
    {
        label: "Apple Jack",
        value: 1,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 1,
            name: "Apple Jack",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Discord",
        value: 2,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 2,
            name: "Discord",
            animal: null,
            type: null,
        },
    },
    {
        label: "Rarity",
        value: 3,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 3,
            name: "Rarity",
            animal: "Pony",
            type: "Unicorn",
        },
    },
    {
        label: "Pinky Pie",
        value: 4,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 4,
            name: "Pinky Pie",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Rainbow Dash",
        value: 5,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 5,
            name: "Rainbow Dash",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Spike",
        value: 6,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 6,
            name: "Spike",
            animal: "Dragon",
            type: null,
        },
    },
    {
        label: "Fluttershy",
        value: 8,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 8,
            name: "Fluttershy",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Twilight Sparkle",
        value: 9,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 9,
            name: "Twilight Sparkle",
            animal: "Pony",
            type: "Alicorn",
        },
    },
];

export const MOCK_DEFAULT_RESULT: INestedSelectOptionProps[] = [
    {
        label: "Apple Jack",
        value: 1,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 1,
            name: "Apple Jack",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Discord",
        value: 2,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 2,
            name: "Discord",
            animal: null,
            type: null,
        },
    },
    {
        label: "Rarity",
        value: 3,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 3,
            name: "Rarity",
            animal: "Pony",
            type: "Unicorn",
        },
    },
    {
        label: "Pinky Pie",
        value: 4,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 4,
            name: "Pinky Pie",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Rainbow Dash",
        value: 5,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 5,
            name: "Rainbow Dash",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Spike",
        value: 6,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 6,
            name: "Spike",
            animal: "Dragon",
            type: null,
        },
    },
    {
        label: "Sparky",
        value: 7,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 7,
            name: "Sparky",
            animal: "Dragon",
            type: null,
        },
    },
    {
        label: "Fluttershy",
        value: 8,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 8,
            name: "Fluttershy",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Twilight Sparkle",
        value: 9,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 9,
            name: "Twilight Sparkle",
            animal: "Pony",
            type: "Alicorn",
        },
    },
    {
        label: "Sunny",
        value: 10,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 10,
            name: "Sunny",
            animal: "Pony",
            type: "Alicorn",
        },
    },
    {
        label: "Izzy",
        value: 11,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 11,
            name: "Izzy",
            animal: "Pony",
            type: "Unicorn",
        },
    },
    {
        label: "Hitch",
        value: 12,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        data: {
            itemID: 12,
            name: "Hitch",
            animal: "Pony",
            type: "Earth Pony",
        },
    },
    {
        label: "Pipp",
        value: 13,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 13,
            name: "Pipp",
            animal: "Pony",
            type: "Pegasus",
        },
    },
    {
        label: "Zipp",
        value: 14,
        extraLabel: undefined,
        isHeader: false,
        group: undefined,
        depth: 0,
        data: {
            itemID: 14,
            name: "Zipp",
            animal: "Pony",
            type: "Pegasus",
        },
    },
];

export const MOCK_SEARCH_FILTERED_RESULT: INestedSelectOptionProps[] = [
    {
        label: "Rarity",
        value: 3,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        tooltip: "Pony > Unicorn",
        data: {
            itemID: 3,
            name: "Rarity",
            animal: "Pony",
            type: "Unicorn",
        },
    },
    {
        label: "Twilight Sparkle",
        value: 9,
        isHeader: false,
        extraLabel: undefined,
        group: undefined,
        depth: 0,
        tooltip: "Pony > Alicorn",
        data: {
            itemID: 9,
            name: "Twilight Sparkle",
            animal: "Pony",
            type: "Alicorn",
        },
    },
];
