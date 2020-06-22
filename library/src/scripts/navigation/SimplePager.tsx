/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import classNames from "classnames";
import React, { useEffect } from "react";
import { simplePagerClasses } from "@library/navigation/simplePagerStyles";
import { ILinkPages } from "@library/navigation/SimplePagerModel";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import ConditionalWrap from "@library/layout/ConditionalWrap";

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
            <ConditionalWrap className={classes.root} condition={!!prev || !!next}>
                {prev && (
                    <>
                        <LinkAsButton className={classNames(classes.button, { isSingle })} to={this.makeUrl(prev)}>
                            {t("Previous")}
                        </LinkAsButton>
                        <LinkMeta rel={"prev"} url={this.makeUrl(prev)} />
                    </>
                )}
                {next && (
                    <>
                        <LinkAsButton className={classNames(classes.button, { isSingle })} to={this.makeUrl(next)}>
                            {t("Next")}
                        </LinkAsButton>
                        <LinkMeta rel={"next"} url={this.makeUrl(next)} />
                    </>
                )}
            </ConditionalWrap>
        );
    }

    private makeUrl(page: number): string {
        const { url } = this.props;
        return url.replace(":page:", page.toString());
    }
}

interface ILinkMeta {
    url: string;
    rel: "next" | "prev";
}

function LinkMeta(props: ILinkMeta) {
    const { url, rel } = props;

    useEffect(() => {
        let existingRel = document.querySelector(`link[rel=${rel}]`);

        const newRel = document.createElement("link");
        newRel.setAttribute("rel", rel);
        newRel.setAttribute("href", url);
        newRel.setAttribute("data-testid", "link-rel-" + rel);

        if (existingRel) {
            existingRel.parentNode?.replaceChild(newRel, existingRel);
        } else {
            document.head.appendChild(newRel);
        }
    }, [url, rel]);

    return <></>;
}
