/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { mockAPI } from "@vanilla/library/src/scripts/__tests__/utility";
import React, { useState } from "react";
import { WidgetFormGenerator } from "./WidgetFormGenerator";
import keyBy from "lodash/keyBy";
import mapValues from "lodash/mapValues";
import { IJsonSchema } from "@dashboard/widgets/JsonSchemaTypes";

export default {
    title: "Dashboard/Widgets",
    decorators: [dashboardCssDecorator],
};

const mockCategories = [
    {
        categoryID: 1,
        name: "General",
        description: "General discussions",
    },
    {
        categoryID: 2,
        name: "Sales & Marketing",
        description: "",
    },
    {
        categoryID: 3,
        name: "Dev & Ops",
        description: "",
    },
    {
        categoryID: 4,
        name: "Product",
        description: "",
    },
    {
        categoryID: 5,
        name: "Ideas",
        description: "",
    },
    {
        categoryID: 6,
        name: "Community",
        description: "It's all about community!",
    },
];

const mockGroups = [
    {
        groupID: 1,
        name: "Moderators",
        description: "",
    },
    {
        groupID: 2,
        name: "Developers",
        description: "",
    },
    {
        groupID: 3,
        name: "Community",
        description: "",
    },
    {
        groupID: 4,
        name: "Administrators",
        description: "",
    },
    {
        groupID: 5,
        name: "Tea Time",
        description: "",
    },
    {
        groupID: 6,
        name: "Watercooler Talk",
        description: "",
    },
];

const widgetSchema: IJsonSchema = {
    type: "object",
    properties: {
        name: {
            type: "string",
            minLength: 1,
            maxLength: 10,
            "x-control": {
                inputType: "textBox",
                label: "name",
                description: "name text box description",
                placeholder: "placeholder text",
            },
        },
        recordType: {
            type: "string",
            enum: ["category", "group"],
            "x-control": {
                inputType: "radio",
                label: "recordType",
                description: "recordType radio options",
                choices: {
                    staticOptions: {
                        category: "Category",
                        group: "Group",
                    },
                },
            },
        },
        recordID: {
            type: "string",
            "x-control": [
                {
                    inputType: "dropDown",
                    label: "group",
                    description: "group drop down",
                    choices: {
                        api: {
                            searchUrl: "/groups?query=%s&limit=5",
                            singleUrl: "/groups/%s",
                            valueKey: "groupID",
                            labelKey: "name",
                        },
                    },
                    conditions: [{ fieldName: "recordType", values: ["group"] }],
                },
                {
                    inputType: "dropDown",
                    label: "category",
                    description: "category drop down",
                    choices: {
                        api: {
                            searchUrl: "/categories?query=%s&limit=5",
                            singleUrl: "/categories/%s",
                            valueKey: "categoryID",
                            labelKey: "name",
                        },
                    },
                    conditions: [{ fieldName: "recordType", values: ["category"] }],
                },
            ],
        },
        maxUsers: {
            type: "string",
            enum: ["2", "4", "6", "8", "10"],
            "x-control": {
                inputType: "dropDown",
                label: "maxUsers",
                description: "max users drop down",
                choices: {
                    staticOptions: {
                        "2": "2",
                        "4": "4",
                        "6": "6",
                        "8": "8",
                        "10": "10",
                    },
                },
            },
        },
        isEntireSite: {
            type: "boolean",
            "x-control": {
                inputType: "checkBox",
                label: "isEntireSite",
                description: "is entire site check box",
            },
        },
    },
};

const makeMockGetSingleQuery = (data, idField) => ({ url }) => {
    const routeStr = url?.split("?")[0];
    const id = routeStr?.split("/").slice(-1)[0];
    const found = data.find((item) => String(item[idField]) === id);
    return [200, found];
};

const makeMockGetAllQuery = (data) => ({ url }) => {
    const paramStr = url?.split("?")[1];
    const params = mapValues(
        keyBy(
            paramStr?.split("&").map((param) => param.split("=")),
            "0",
        ),
        "1",
    );
    const { query, limit } = params || {};
    const filtered = query ? data.filter((item) => item.name.toLowerCase().includes(query.toLowerCase())) : data;
    const limited = limit ? filtered.slice(0, limit) : filtered;
    return [200, limited];
};

export function StoryWidgetFormGenerator() {
    const mock = mockAPI();

    mock.onGet(/^categories\/(.+)$/).reply(makeMockGetSingleQuery(mockCategories, "categoryID"));
    mock.onGet(/^groups\/(.+)$/).reply(makeMockGetSingleQuery(mockGroups, "groupID"));
    mock.onGet(/^categories\?query=(.*)&limit=\d+$/).reply(makeMockGetAllQuery(mockCategories));
    mock.onGet(/^groups\?query=(.*)&limit=\d+$/).reply(makeMockGetAllQuery(mockGroups));

    const [instance, setInstance] = useState({
        name: undefined,
        recordType: "category",
        recordID: 6,
        maxUsers: "2",
        isEntireSite: false,
    });
    return <WidgetFormGenerator schema={widgetSchema} instance={instance} onChange={setInstance} />;
}

StoryWidgetFormGenerator.name = "WidgetFormGenerator";
