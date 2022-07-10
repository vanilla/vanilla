/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import SmartLink from "@library/routing/links/SmartLink";
import classNames from "classnames";
import { metasClasses } from "@library/metas/Metas.styles";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import TruncatedText from "@library/content/TruncatedText";
import { EmbedTitle } from "@library/embeddedContent/components/EmbedTitle";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";

interface IProps extends IBaseEmbedProps {
    photoUrl?: string;
    body?: string;
}

export function LinkEmbed(props: IProps) {
    const { name, url, photoUrl, body } = props;
    const classesMetas = metasClasses();
    const title = name ? <EmbedTitle>{name}</EmbedTitle> : null;

    const source = <span className={classNames("embedLink-source", classesMetas.metaStyle)}>{url}</span>;

    let linkImage: JSX.Element | null = null;
    if (photoUrl) {
        linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" loading="lazy" />;
    }

    return (
        <EmbedContainer className="embedText embedLink">
            <EmbedContent type="link">
                <SmartLink
                    className={classNames("embedLink-link", classesMetas.noUnderline)}
                    to={url}
                    rel="nofollow noreferrer ugc"
                    tabIndex={props.inEditor ? -1 : 0}
                    aria-label={name}
                >
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
