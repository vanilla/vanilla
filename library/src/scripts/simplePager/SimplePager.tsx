/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import SimplePagerModel, { LinkPages } from "@library/simplePager/SimplePagerModel";
import LinkAsButton from "@library/components/LinkAsButton";
import classNames from "classnames";

interface IProps {
    url: string;
    pages: LinkPages;
}

/**
 * Basic pagination. Only previous/next buttons are included.
 */
export default class SimplePager extends React.Component<IProps> {
    public render() {
        const { next, prev } = this.props.pages;

        const buttons = [] as JSX.Element[];
        const isSingle = (prev && !next) || (!prev && next);

        if (prev) {
            buttons.push(
                <LinkAsButton
                    className={classNames(["simplePager-button", "simplePager-prev", { isSingle }])}
                    key="simplePagerPrev"
                    to={this.makeUrl(prev)}
                >
                    {t("Previous")}
                </LinkAsButton>,
            );
        }
        if (next) {
            buttons.push(
                <LinkAsButton
                    className={classNames(["simplePager-button", "simplePager-next", { isSingle }])}
                    key="simplePagerNext"
                    to={this.makeUrl(next)}
                >
                    {t("Next")}
                </LinkAsButton>,
            );
        }

        return <div className="simplePager">{buttons}</div>;
    }

    private makeUrl(page: number): string {
        const { url } = this.props;
        return url.replace(":page:", page.toString());
    }
}
