/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import SearchBar from "@library/components/forms/select/SearchBar";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import classNames from "classnames";
import { search } from "@library/components/icons/header";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import SearchOption from "@library/components/search/SearchOption";
import { withApi, IApiProps } from "@library/contexts/ApiContext";
import { Redirect } from "react-router-dom";
import AsyncCreatableSelect from "react-select/lib/AsyncCreatable";

export interface ICompactSearchProps extends IApiProps {
    className?: string;
    placeholder?: string;
    open: boolean;
    onOpenSearch: () => void;
    onCloseSearch: () => void;
    cancelButtonClassName?: string;
    buttonClass?: string;
}

interface IState {
    query: string;
    redirectTo: string | null;
}

/**
 * Implements Compact Search component for header
 */
export class CompactSearch extends React.Component<ICompactSearchProps, IState> {
    private id = uniqueIDFromPrefix("compactSearch");
    private openSearchButton: React.RefObject<HTMLButtonElement> = React.createRef();
    private searchInputRef: React.RefObject<SearchBar> = React.createRef();
    public state: IState = {
        query: "",
        redirectTo: null,
    };

    public render() {
        if (this.state.redirectTo) {
            return <Redirect to={this.state.redirectTo} />;
        }

        return (
            <div className={classNames("compactSearch", this.props.className)}>
                {!this.props.open && (
                    <Button
                        onClick={this.props.onOpenSearch}
                        className={classNames("compactSearch-open", this.props.buttonClass)}
                        title={t("Search")}
                        aria-expanded={false}
                        aria-haspopup="true"
                        baseClass={ButtonBaseClass.CUSTOM}
                        aria-controls={this.id}
                        buttonRef={this.openSearchButton}
                    >
                        <div className="compactSearch-buttonContent">{search()}</div>
                    </Button>
                )}
                {this.props.open && (
                    <div className={classNames("compactSearch-contents")}>
                        <SearchBar
                            id={this.id}
                            placeholder={this.props.placeholder}
                            optionComponent={SearchOption}
                            noHeading={true}
                            title={t("Search")}
                            value={this.state.query}
                            disabled={!this.props.open}
                            hideSearchButton={true}
                            onChange={this.searchChangeHandler}
                            onSearch={this.submitHandler}
                            loadOptions={this.props.searchOptionProvider.autocomplete}
                            ref={this.searchInputRef}
                            triggerSearchOnAllUpdates={false}
                        />
                        <Button
                            onClick={this.props.onCloseSearch}
                            className={classNames("compactSearch-close", this.props.cancelButtonClassName)}
                            title={t("Search")}
                            aria-expanded={true}
                            aria-haspopup="true"
                            aria-controls={this.id}
                            baseClass={ButtonBaseClass.CUSTOM}
                        >
                            {t("Cancel")}
                        </Button>
                    </div>
                )}
            </div>
        );
    }

    private searchChangeHandler = (newQuery: string) => {
        this.setState({ query: newQuery });
    };

    private submitHandler = () => {
        const { searchOptionProvider } = this.props;
        const { query } = this.state;
        this.setState({ redirectTo: searchOptionProvider.makeSearchUrl(query) });
    };

    public componentDidUpdate(prevProps) {
        if (!prevProps.open && this.props.open) {
            this.searchInputRef.current!.focus();
        } else if (prevProps.open && !this.props.open) {
            this.openSearchButton.current!.focus();
        }
    }
}

export default withApi<ICompactSearchProps>(CompactSearch);
