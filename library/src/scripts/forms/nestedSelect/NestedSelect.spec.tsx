/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { mockAPI } from "@library/__tests__/utility";
import { flattenOptions, getSearchMatch, NestedSelect, useNestedOptions } from "@library/forms/nestedSelect";
import {
    MOCK_DEEP_NESTED_LIST,
    MOCK_DEFAULT_RESPONSE,
    MOCK_DEFAULT_RESULT,
    MOCK_NESTED_LIST,
    MOCK_SEARCH_ALL_RESPONSE,
    MOCK_SEARCH_FILTERED_RESULT,
    MOCK_SEARCH_NESTED_RESULT,
    MOCK_SEARCH_RESPONSE,
    MOCK_SEARCH_SIMPLE_RESULT,
    MOCK_SIMPLE_LIST,
} from "@library/forms/nestedSelect/NestedSelect.fixtures";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen, waitFor, within } from "@testing-library/react";
import { renderHook } from "@testing-library/react-hooks";
import userEvent from "@testing-library/user-event";
import { RecordID } from "@vanilla/utils";
import MockAdapter from "axios-mock-adapter";
import get from "lodash-es/get";
import set from "lodash-es/set";
import { useState } from "react";
import { Select } from "@vanilla/json-schema-forms";
import { ApiV2Context } from "@library/apiv2";

function queryClientWrapper() {
    const queryClient = new QueryClient();
    const Wrapper = ({ children }) => (
        <QueryClientProvider client={queryClient}>
            <ApiV2Context>{children}</ApiV2Context>
        </QueryClientProvider>
    );
    return Wrapper;
}

describe("Hooks", async () => {
    let mockAdapter: MockAdapter;

    beforeEach(() => {
        mockAdapter = mockAPI();
    });

    afterEach(() => {
        mockAdapter.reset();
    });

    it("Converts a simple list with no groups", () => {
        const { result } = renderHook(() => useNestedOptions({ options: MOCK_SIMPLE_LIST }), {
            wrapper: queryClientWrapper(),
        });
        const options = MOCK_SIMPLE_LIST.map((option) => ({
            ...option,
            isHeader: false,
            group: undefined,
            depth: 0,
        }));
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.map((option) => [option.value, option])),
            optionsByGroup: {},
        };
        expect(result.current).toStrictEqual(expected);
    });

    it("Converts a nested list with groups", () => {
        const { result } = renderHook(() => useNestedOptions({ options: MOCK_DEEP_NESTED_LIST }), {
            wrapper: queryClientWrapper(),
        });
        const options = flattenOptions(MOCK_DEEP_NESTED_LIST);
        const optionsByGroup: any = {};
        options.forEach((itm) => {
            if (itm.group) {
                if (!optionsByGroup[itm.group]) {
                    optionsByGroup[itm.group] = [];
                }
                optionsByGroup[itm.group].push(itm);
            }
        });
        const expected = {
            options,
            optionsByValue: Object.fromEntries(
                options.filter(({ value }) => value !== undefined).map((itm) => [itm.value, itm]),
            ),
            optionsByGroup,
        };
        expect(result.current).toStrictEqual(expected);
    });

    it("Converts API lookup with no additional processing into a simple list with no groups", async () => {
        mockAdapter.onGet("/test/search/").reply(200, MOCK_SEARCH_ALL_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            labelKey: "name",
            valueKey: "itemID",
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess);

        const options = MOCK_SEARCH_SIMPLE_RESULT;
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.map((itm) => [itm.value, itm])),
            optionsByGroup: {},
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Converts API look with additional processing into a nested list with groups", async () => {
        mockAdapter.onGet("/test/search/").reply(200, MOCK_SEARCH_ALL_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            labelKey: "name",
            valueKey: "itemID",
            processOptions,
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess, { timeout: 2000 });

        const options = MOCK_SEARCH_NESTED_RESULT;
        const optionsByGroup: any = {};
        options.forEach((option) => {
            if (option.group) {
                if (!optionsByGroup[option.group]) {
                    optionsByGroup[option.group] = [];
                }
                optionsByGroup[option.group].push(option);
            }
        });
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.filter(({ value }) => value).map((itm) => [itm.value, itm])),
            optionsByGroup,
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Converts API lookup using the default URL instead", async () => {
        mockAdapter.onGet("/test/default").reply(200, MOCK_DEFAULT_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            defaultListUrl: "/test/default",
            labelKey: "name",
            valueKey: "itemID",
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup, searchQuery: "" }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess);

        const options = MOCK_DEFAULT_RESULT;
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.map((itm) => [itm.value, itm])),
            optionsByGroup: {},
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Converts API lookup with search query into filtered list, ignoring initialOptions", async () => {
        mockAdapter.onGet("/test/search/ar").reply(200, MOCK_SEARCH_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            labelKey: "name",
            valueKey: "itemID",
            initialOptions: MOCK_SIMPLE_LIST,
            processOptions,
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup, searchQuery: "ar" }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess);

        const options = MOCK_SEARCH_FILTERED_RESULT;
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.filter(({ value }) => value).map((itm) => [itm.value, itm])),
            optionsByGroup: {},
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Uses initialOptions when not searching and default not defined", async () => {
        mockAdapter.onGet("/test/search/ar").reply(200, MOCK_SEARCH_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            labelKey: "name",
            valueKey: "itemID",
            initialOptions: MOCK_SIMPLE_LIST,
            processOptions,
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess);

        const options = MOCK_SIMPLE_LIST.map((option) => ({
            ...option,
            isHeader: false,
            group: undefined,
            depth: 0,
        }));
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.map((option) => [option.value, option])),
            optionsByGroup: {},
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Excludes specific values from the API lookup", async () => {
        mockAdapter.onGet("/test/search/").reply(200, MOCK_SEARCH_ALL_RESPONSE);
        const optionsLookup: Select.LookupApi = {
            searchUrl: "/test/search/%s",
            singleUrl: "/test/%s",
            labelKey: "name",
            valueKey: "itemID",
            excludeLookups: [2, 6],
        };
        const { result, waitFor } = renderHook(() => useNestedOptions({ optionsLookup }), {
            wrapper: queryClientWrapper(),
        });

        await waitFor(() => result.current.isSuccess);

        const options = MOCK_SEARCH_SIMPLE_RESULT.filter(({ value }) => ![2, 6].includes(value as number));
        const expected = {
            options,
            optionsByValue: Object.fromEntries(options.map((itm) => [itm.value, itm])),
            optionsByGroup: {},
            isSuccess: true,
        };

        expect(result.current).toStrictEqual(expected);
    });

    it("Finds a match to a search query in a string", () => {
        const actual = getSearchMatch("Stitch is Experiment 626", "ex");
        const expected = {
            isMatch: true,
            parts: ["Stitch is ", "Ex", "periment 626"],
        };
        expect(actual).toStrictEqual(expected);
    });

    it("Does not find a match to a search query in a string", () => {
        const actual = getSearchMatch("Toothless is a Night Fury", "ex");
        const expected = {
            isMatch: false,
            parts: ["Toothless is a Night Fury"],
        };
        expect(actual).toStrictEqual(expected);
    });
});

describe("Rendered Component", () => {
    const QueryClientWrapper = queryClientWrapper();

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it("Renders the component with a label and extra notes", () => {
        render(
            <QueryClientWrapper>
                <NestedSelect
                    options={MOCK_NESTED_LIST}
                    label="Input Label"
                    labelNote="Input label note"
                    noteAfterInput="Note after the input"
                    onChange={() => null}
                />
            </QueryClientWrapper>,
        );

        expect(screen.getByText(/Input Label/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Input Label/)).toBeInTheDocument();
        expect(screen.getByText(/Input label note/)).toBeInTheDocument();
        expect(screen.getByText(/Note after the input/)).toBeInTheDocument();
    });

    it("Opens menu when the input is focused", async () => {
        render(
            <QueryClientWrapper>
                <NestedSelect label="Test Input" options={MOCK_SIMPLE_LIST} onChange={() => null} />
            </QueryClientWrapper>,
        );

        const input = screen.getByRole("textbox", { name: "Test Input" });
        expect(input).toBeInTheDocument();
        await userEvent.click(input);

        // make sure the menu is there
        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();
        // check for some of the expected list items
        const banana = within(menu).getByRole("menuitem", { name: "Banana" });
        const kiwi = within(menu).getByRole("menuitem", { name: "Kiwi" });
        expect(banana).toBeInTheDocument();
        expect(kiwi).toBeInTheDocument();
    });

    it("Nested menu includes headers", async () => {
        render(
            <QueryClientWrapper>
                <NestedSelect options={MOCK_DEEP_NESTED_LIST} onChange={() => null} />
            </QueryClientWrapper>,
        );

        const input = screen.getByRole("textbox");
        await userEvent.click(input);
        const menu = await screen.getByRole("menu");
        const fruitHeader = within(menu).getByRole("menuitem", { name: "Fruit" });
        const apple = within(menu).getByRole("menuitem", { name: "Apple" });
        const shavedIce = within(menu).getByRole("heading", { name: "Shaved Ice" });
        expect(fruitHeader).toBeInTheDocument();
        expect(apple).toBeInTheDocument();
        expect(shavedIce).toBeInTheDocument();
    });

    it("Filters the menu", async () => {
        render(
            <QueryClientWrapper>
                <NestedSelect options={MOCK_DEEP_NESTED_LIST} onChange={() => null} />
            </QueryClientWrapper>,
        );

        const input = screen.getByRole("textbox");
        input.focus();
        await userEvent.keyboard("ch");

        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();

        const options = await within(menu).getAllByRole("menuitem");
        expect(options.length).toEqual(7);
        expect(options[0]).toHaveTextContent("Shaved Ice > Flavors Cherry");
        expect(options[1]).toHaveTextContent("Shaved Ice > Topping Cherry");
        expect(options[2]).toHaveTextContent("Chocolate Bar");
    });

    it("Navigates the menu", async () => {
        render(
            <QueryClientWrapper>
                <NestedSelect options={MOCK_SIMPLE_LIST} onChange={() => null} />
            </QueryClientWrapper>,
        );

        const input = screen.getByTestId("inputContainer");
        await userEvent.click(input);
        const menu = await screen.getByRole("menu");

        // Go down 2 should land on Banana option
        await userEvent.keyboard("{ArrowDown>2}");
        const banana = within(menu).getByRole("menuitem", { name: "Banana" });
        expect(banana).toHaveClass("highlighted");

        // Go up from Banana should land on Orange option
        await userEvent.keyboard("{ArrowUp}");
        const orange = within(menu).getByRole("menuitem", { name: "Orange" });
        expect(orange).toHaveClass("highlighted");
        expect(banana).not.toHaveClass("highlighted");

        // Arrow up past the top option should remain on top option
        await userEvent.keyboard("{ArrowUp>2}");
        const apple = within(menu).getByRole("menuitem", { name: "Apple" });
        expect(apple).toHaveClass("highlighted");

        // Arrow down past the top option should remain on last option
        await userEvent.keyboard("{ArrowDown>9}");
        const watermelon = within(menu).getByRole("menuitem", { name: "Watermelon" });
        expect(watermelon).toHaveClass("highlighted");
    });

    it("Selects an option", async () => {
        const select = {
            onChange: (newValue?: string, data?: any) => null,
        };
        const spy = vi.spyOn(select, "onChange");
        render(
            <QueryClientWrapper>
                <NestedSelect options={MOCK_SIMPLE_LIST} onChange={select.onChange} />
            </QueryClientWrapper>,
        );

        const input = screen.getByRole("textbox");
        input.focus();
        await userEvent.keyboard("ba");
        const banana = screen.getByRole("menuitem", { name: "Banana" });
        expect(banana).toHaveClass("highlighted");

        await userEvent.keyboard("{Enter}");
        expect(spy).toHaveBeenCalledWith("banana", {
            value: "banana",
            label: "Banana",
            group: undefined,
            depth: 0,
            isHeader: false,
        });

        await userEvent.click(input);
        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();
        const kiwi = within(menu).getByRole("menuitem", { name: "Kiwi" });
        await userEvent.click(kiwi);
        expect(spy).toHaveBeenCalledWith("kiwi", {
            value: "kiwi",
            label: "Kiwi",
            group: undefined,
            depth: 0,
            isHeader: false,
        });
    });

    it("Deselects an option", async () => {
        const deselect = {
            onChange: (newValue?: string, data?: any) => null,
        };
        const spy = vi.spyOn(deselect, "onChange");
        render(
            <QueryClientWrapper>
                <NestedSelect options={MOCK_SIMPLE_LIST} onChange={deselect.onChange} value={"kiwi"} />
            </QueryClientWrapper>,
        );

        const input = screen.getByTestId("inputContainer");
        await userEvent.click(input);
        const menu = await screen.getByRole("menu");
        expect(menu).toBeInTheDocument();
        const kiwi = within(menu).getByRole("menuitem", { name: "Kiwi" });
        await userEvent.click(kiwi);
        expect(spy).toHaveBeenCalledWith(undefined, undefined);
    });

    it("Clears selected options on demand and replaces with default values", async () => {
        const clear = {
            onChange: (newValue?: RecordID[], data?: any) => null,
        };
        const spy = vi.spyOn(clear, "onChange");
        const MockSelect = () => {
            const [value, setValue] = useState<RecordID[] | undefined>(["watermelon", "banana", "grape"]);

            return (
                <QueryClientWrapper>
                    <NestedSelect
                        options={MOCK_SIMPLE_LIST}
                        onChange={(newValue, data) => {
                            setValue(newValue as RecordID[]);
                            clear.onChange(newValue as RecordID[], data);
                        }}
                        value={value}
                        defaultValue={["strawberry", "kiwi"]}
                        multiple
                        isClearable
                    />
                </QueryClientWrapper>
            );
        };
        render(<MockSelect />);

        const initialTokens = await screen.findAllByText((content, element) => {
            return element ? element.classList.contains("token") : false;
        });
        expect(initialTokens.length).toEqual(3);
        expect(
            screen.getByText((content, element) => {
                return element ? element.classList.contains("tokenText") && content === "Watermelon" : false;
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText((content, element) => {
                return element ? element.classList.contains("tokenText") && content === "Banana" : false;
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText((content, element) => {
                return element ? element.classList.contains("tokenText") && content === "Grape" : false;
            }),
        ).toBeInTheDocument();

        const clearBtn = screen.getByRole("button", { name: "Clear All" });
        expect(clearBtn).toBeInTheDocument();

        await userEvent.click(clearBtn);

        await waitFor(() => {
            expect(spy).toHaveBeenCalledWith(
                ["strawberry", "kiwi"],
                [
                    {
                        value: "strawberry",
                        label: "Strawberry",
                        isHeader: false,
                        group: undefined,
                        depth: 0,
                    },
                    {
                        value: "kiwi",
                        label: "Kiwi",
                        isHeader: false,
                        group: undefined,
                        depth: 0,
                    },
                ],
            );
        });

        const defaultTokens = await screen.findAllByText((content, element) => {
            return element ? element.classList.contains("token") : false;
        });
        expect(defaultTokens.length).toEqual(2);
        expect(
            screen.getByText((content, element) => {
                return element ? element.classList.contains("tokenText") && content === "Strawberry" : false;
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText((content, element) => {
                return element ? element.classList.contains("tokenText") && content === "Kiwi" : false;
            }),
        ).toBeInTheDocument();
    });
});

function processOptions(options: Select.Option[]): Select.Option[] {
    const mapping: any = {};

    options.forEach((option) => {
        const { data } = option;
        if (!data.animal && !data.type) {
            set(mapping, data.name, option);
        } else {
            const animal = data.animal ? get(mapping, data.animal, { label: data.animal, children: {} }) : undefined;

            if (animal) {
                if (data.type) {
                    const type = get(animal, `children.${data.type}`, { label: data.type, children: {} });
                    set(type, `children.${data.name}`, option);
                    set(animal, `children.${data.type}`, type);
                } else {
                    set(animal, `children.${data.name}`, option);
                }
                set(mapping, data.animal, animal);
            }
        }
    });

    return Object.values(mapping).map((lvl1: any) => ({
        ...lvl1,
        children: lvl1.children
            ? Object.values(lvl1.children).map((lvl2: any) => ({
                  ...lvl2,
                  children: lvl2.children ? Object.values(lvl2.children) : undefined,
              }))
            : undefined,
    })) as Select.Option[];
}
