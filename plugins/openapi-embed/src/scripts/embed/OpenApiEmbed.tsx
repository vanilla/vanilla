/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IBaseEmbedProps } from "@library/embeddedContent/embedService";
import { OpenApiEmbedPlaceholder } from "@openapi-embed/embed/OpenApiEmbedPlaceholder";
import { EmbedContainer, EmbedContainerSize } from "@vanilla/library/src/scripts/embeddedContent/EmbedContainer";
import { EmbedContent } from "@vanilla/library/src/scripts/embeddedContent/EmbedContent";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import { EditIcon } from "@vanilla/library/src/scripts/icons/common";
import React, { useState } from "react";
import { OpenApiModal } from "@openapi-embed/embed/OpenApiModal";

interface IProps extends IBaseEmbedProps {}

export function OpenApiEmbed(props: IProps) {
    const [showEditModal, setShowEditModal] = useState(false);

    return (
        <EmbedContainer size={EmbedContainerSize.FULL_WIDTH}>
            <EmbedContent
                type="OpenApi"
                embedActions={
                    <Button
                        baseClass={ButtonTypes.ICON}
                        onClick={() => {
                            setShowEditModal(true);
                        }}
                    >
                        <EditIcon />
                    </Button>
                }
            >
                <OpenApiEmbedPlaceholder name="test" embedUrl={props.url} />
            </EmbedContent>
            {showEditModal && (
                <OpenApiModal
                    onDismiss={() => {
                        setShowEditModal(false);
                    }}
                />
            )}
        </EmbedContainer>
    );

    // return (
    //     // <EmbedContent type="OpenApi" inEditor={props.inEditor}>
    //     // <div className="u-excludeFromPointerEvents" className={FOCUS_CLASS}>
    //         {/* <TextInput value={url} onChange={e => setUrl(e.target.value)} className={FOCUS_CLASS} /> */}
    //         {/* <Button onClick={() => setSavedUrl(url)}>Check API definition</Button> */}
    //         // <div ref={swaggerRef} />
    //         {/* {savedUrl && <SwaggerUI url={savedUrl} docExpansion="list" />} */}
    //     // </div>
    //     // </EmbedContent>
    // );
}
