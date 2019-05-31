/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import * as React from "react";
import { simplePagerClasses } from "@library/navigation/simplePagerStyles";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";

interface IProps {
    url: string;
    pages: ILinkPages;
}

/**
 * Basic pagination. Only previous/next buttons are included.
 */
export default class SimplePager extends React.Component<IProps> {
    public render() {
        const { next, prev } = this.props.pages;
        const buttons = [] as JSX.Element[];
        const isSingle = (prev && !next) || (!prev && next);
        const classes = simplePagerClasses();

        return (
            <div className={classNames("simplePager", classes.root)}>
                {prev && (
                    <LinkAsButton className={classNames(classes.button, { isSingle })} to={this.makeUrl(prev)}>
                        {t("Previous")}
                    </LinkAsButton>
                )}
                {next && (
                    <LinkAsButton className={classNames(classes.button, { isSingle })} to={this.makeUrl(next)}>
                        {t("Next")}
                    </LinkAsButton>
                )}
            </div>
        );
    }

    private makeUrl(page: number): string {
        const { url } = this.props;
        return url.replace(":page:", page.toString());
    }
}
