/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Translate from "@library/content/Translate";
import Paragraph from "@library/layout/Paragraph";
import { t } from "@vanilla/i18n/src";
import { List } from "@library/lists/List";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { PageBox } from "@library/layout/PageBox";
import Result from "@library/result/Result";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: any[];
    resultComponent?: React.ComponentType<any>;
    emptyMessage?: string;
    ResultWrapper?: React.ComponentType<any>;
    rel?: string;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default function ResultList(props: IProps) {
    const {
        className,
        searchTerm,
        results,
        emptyMessage = t("No results found."),
        resultComponent = Result,
        ResultWrapper,
    } = props;

    const hasResults = results && results.length > 0;

    let content;

    if (hasResults) {
        const ResultComponent = resultComponent;
        content = results.map((result, i) => {
            return <ResultComponent {...result} key={i} rel={props.rel} />;
        });
    } else {
        content = (
            <PageBox>
                <Paragraph>
                    {searchTerm ? <Translate source="No results for '<0/>'." c0={searchTerm} /> : emptyMessage}
                </Paragraph>
            </PageBox>
        );
    }

    if (ResultWrapper) {
        return <ResultWrapper>{content}</ResultWrapper>;
    } else {
        const tag = hasResults ? `ul` : `div`;
        return (
            <List as={tag} options={{ itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION }} className={className}>
                {content}
            </List>
        );
    }
}
