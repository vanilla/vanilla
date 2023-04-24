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
import { embedContainerClasses } from "@library/embeddedContent/components/embedStyles";
import { EmbedContainerSize } from "@library/embeddedContent/components/EmbedContainerSize";
import { t } from "@vanilla/i18n";
import { EmbedInlineContent } from "@library/embeddedContent/components/EmbedInlineContent";

interface IProps extends IBaseEmbedProps {
    photoUrl?: string;
    body?: string;
}

export function LinkEmbed(props: IProps) {
    const { name, url, photoUrl, body, embedStyle, faviconUrl } = props;
    const classesMetas = metasClasses();
    const title = name ? <EmbedTitle>{name}</EmbedTitle> : null;

    const source = <span className={cx("embedLink-source", classesMetas.metaStyle)}>{url}</span>;

    let linkImage: JSX.Element | null = null;
    if (photoUrl) {
        linkImage = <img src={photoUrl} className="embedLink-image" aria-hidden="true" loading="lazy" />;
    }

    return embedStyle === "rich_embed_inline" ? (
        <EmbedInlineContent type="link">
            <SmartLink
                className={cx(
                    "embedLink-link",
                    embedContainerClasses().makeRootClass(EmbedContainerSize.INLINE, !!props.inEditor),
                    { [embedContainerClasses().inlineWithFavicon]: !!faviconUrl },
                    classesMetas.noUnderline,
                )}
                to={url}
                rel="nofollow noopener ugc"
                tabIndex={props.inEditor && !props.disableFocus ? -1 : 0}
                aria-label={name}
                onClick={(e) => {
                    if (props.inEditor) {
                        e.preventDefault();
                    }
                }}
            >
                {faviconUrl && (
                    <img
                        style={{ height: "1em", width: "1em", marginRight: 6 }}
                        src={faviconUrl}
                        role="decoration"
                        alt={t("Site favicon")}
                        tabIndex={-1}
                    ></img>
                )}
                {name || url}
            </SmartLink>
        </EmbedInlineContent>
    ) : (
        <EmbedContainer className="embedText embedLink">
            <EmbedContent type="link">
                <SmartLink
                    className={cx("embedLink-link", classesMetas.noUnderline)}
                    to={url}
                    rel="nofollow noopener ugc"
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
