import Search from "@vanilla/injectables/SearchFragment";
import Components from "@vanilla/injectables/Components";
import Utils from "@vanilla/injectables/Utils";
import React from "react";
export default function SearchFragment(props: Search.Props) {
    const {
        /** The title for the search box */
        title,
        /** The description for the search box */
        description,
        /** The subtitle for the search box */
        subtitle,
        /** The border radius of the search widget */
        borderRadius,
        /** If applicable, selection to search all (the hub) or only the specific community (node)*/
        scope,
        /** Placeholder text which appears inside of an empty search box */
        placeholder,
        /** Parameters which should be set on the search page URL after the form is submitted */
        initialParams,
        /** Whether or not to show the search button */
        hideButton,
        /** The types of content to filter the search query by. i.e "Posts", "Articles", "Events", "Members" */
        domain,
        /** The types of posts to filter the search query, if filtering the domain by posts */
        postType,
    } = props;

    // Some state for the search bar.
    const [query, setQuery] = React.useState("");
    const [type, setType] = React.useState<string>();

    // Debounce is important for performance.
    // Too many queries in a short amount of time can lead to performance issues and can overload the server with requests.
    const debouncedQuery = useDebouncedInput(query, 300);

    const availableTypes = ["article", "discussion", "comment", "question", "answer", "group", "poll"];

    // Utility hook for fetching search results.
    // It will automatically refetch the when the typed text changes.
    const searchQuery: Search.SearchQuery = {
        query,
        limit: 5,
        expands: ["-body"],
        ...(type && type.length > 0 && { types: [type] }),
    };

    const search = Search.useSearchQuery(searchQuery);
    React.useEffect(() => {
        const performSearch = async () => {
            await search.refetch();
        };

        // It's often not worth performing an auto-complete lookup if there isn't enough of a query to narrow down results.
        if (debouncedQuery.length > 3) {
            void performSearch();
        }
    }, [debouncedQuery]);

    const navigateToUrl = Utils.useLinkNavigator();

    return (
        <Components.LayoutWidget className={"searchFragment__root"}>
            {(title || description) && (
                <div className={"searchFragment__heading"}>
                    <h1>{title && Utils.t(title)}</h1>
                    <p>{description && Utils.t(description)}</p>
                </div>
            )}
            <form
                className={"searchFragment__searchBar"}
                onSubmit={(e) => {
                    e.preventDefault();
                    const url = Search.makeSearchPageUrl(searchQuery);
                    navigateToUrl(url);
                }}
            >
                <div className={"searchFragment__scope-wrapper"}>
                    <select
                        className={"searchFragment__scope-selector"}
                        onChange={(selectedType) => setType(selectedType.target.value)}
                    >
                        {availableTypes.map((type) => (
                            <option key={type} value={type}>
                                {Utils.t(type)}
                            </option>
                        ))}
                    </select>
                </div>
                <input
                    className={"searchFragment__input"}
                    type="text"
                    placeholder="Search"
                    onChange={(e) => setQuery(e.target.value)}
                    value={query}
                />
                <Components.Button type={"submit"} className={"searchFragment__button"} buttonType={"primary"}>
                    {Utils.t("Search")}
                </Components.Button>
            </form>
            {query.length > 0 && (
                <div className={"searchFragment__results"}>
                    <Components.Link to={"#"} className={"searchFragment__result-perform-search"}>
                        <Components.Translate source={"Search for <0/>"} c0={() => <span>{query}</span>} />
                    </Components.Link>
                    {search.isFetching && <li className={"searchFragment__result"}>Loading...</li>}
                    {search.data?.map((result) => (
                        <li key={result.recordID} className={"searchFragment__result"}>
                            <Components.Link to={result.url}>
                                <div className={"searchFragment__result-title"}>{result.name}</div>
                                <div className={"searchFragment__result-meta"}>
                                    <span>
                                        {new Intl.DateTimeFormat(navigator.languages, {
                                            day: "numeric",
                                            month: "long",
                                            year: "numeric",
                                        }).format(new Date(`${result.dateInserted}`))}
                                    </span>
                                    <ul className={"searchFragment__result-breadcrumbs"}>
                                        {result?.breadcrumbs?.map((crumb) => (
                                            <li key={crumb.name}>{crumb.name}</li>
                                        ))}
                                    </ul>
                                </div>
                            </Components.Link>
                        </li>
                    ))}
                </div>
            )}
        </Components.LayoutWidget>
    );
}

function useDebouncedInput<T>(value: T, delay: number) {
    const [debouncedValue, setDebouncedValue] = React.useState(value);

    React.useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value]);

    return debouncedValue;
}
