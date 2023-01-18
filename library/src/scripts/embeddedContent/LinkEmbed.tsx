/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import SmartLink from "@library/routing/links/SmartLink";
import { metasClasses } from "@library/metas/Metas.styles";
import { EmbedContainer } from "@library/embeddedContent/components/EmbedContainer";
import TruncatedText from "@library/content/TruncatedText";
import { EmbedTitle } from "@library/embeddedContent/components/EmbedTitle";
import { EmbedContent } from "@library/embeddedContent/components/EmbedContent";
import { cx } from "@emotion/css";

interface IProps extends IBaseEmbedProps {
    photoUrl?: string;
    body?: string;
}

export function LinkEmbed(props: IProps) {
    const { name, url, photoUrl, body } = props;
    const classesMetas = metasClasses();
    const title = name ? <EmbedTitle>{name}</EmbedTitle> : null;

    const source = <span className={cx("embedLink-source", classesMetas.metaStyle)}>{url}</span>;

    let linkImage: JSX.Element | null = null;
    if (photoUrl) {
        linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" loading="lazy" />;
    }

    return (
        <EmbedContainer className="embedText embedLink">
            <EmbedContent type="link">
                <SmartLink
                    className={cx("embedLink-link", classesMetas.noUnderline)}
                    to={url}
                    rel="nofollow noreferrer ugc"
                    tabIndex={props.inEditor && !props.disableFocus ? -1 : 0}
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
