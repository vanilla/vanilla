/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import { metasClasses } from "@library/styles/metasStyles";
import { EmbedContainer } from "@library/embeddedContent/EmbedContainer";
import TruncatedText from "@library/content/TruncatedText";
import { EmbedTitle } from "@library/embeddedContent/EmbedTitle";
import { EmbedContent } from "@library/embeddedContent/EmbedContent";

interface IProps extends IBaseEmbedProps {
    photoUrl?: string;
    body: string;
}

export function LinkEmbed(props: IProps) {
    const { name, url, photoUrl, body } = props;
    const classesMetas = metasClasses();
    const title = name ? <EmbedTitle>{name}</EmbedTitle> : null;

    const source = <span className={classNames("embedLink-source", classesMetas.metaStyle)}>{url}</span>;

    let linkImage: JSX.Element | null = null;
    if (photoUrl) {
        linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" />;
    }

    return (
        <EmbedContainer className="embedText embedLink">
            <EmbedContent type="link" inEditor={props.inEditor}>
                <SmartLink className="embedLink-link" to={url} rel="noreferrer">
                    <article className="embedText-body embedLink-body">
                        {linkImage}
                        <div className="embedText-main embedLink-main">
                            <div className="embedText-header embedLink-header">
                                {title}
                                {source}
                            </div>
                            <TruncatedText tag="div" className="embedLink-excerpt" useMaxHeight={true}>
                                {body}
                            </TruncatedText>
                        </div>
                    </article>
                </SmartLink>
            </EmbedContent>
        </EmbedContainer>
    );
}
