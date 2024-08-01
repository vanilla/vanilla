/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { BrightcoveEmbed } from "@library/embeddedContent/BrightcoveEmbed";
import { CodePenEmbed } from "@library/embeddedContent/CodePenEmbed";
import { EmbedErrorBoundary } from "@library/embeddedContent/components/EmbedErrorBoundary";
import { FileEmbed } from "@library/embeddedContent/FileEmbed";
import { GettyImagesEmbed } from "@library/embeddedContent/GettyImagesEmbed";
import { GiphyEmbed } from "@library/embeddedContent/GiphyEmbed";
import { IFrameEmbed, supportsFrames } from "@library/embeddedContent/IFrameEmbed";
import { MuralEmbed } from "@library/embeddedContent/MuralEmbed";
import { ImageEmbed } from "@library/embeddedContent/ImageEmbed";
import { ImgurEmbed } from "@library/embeddedContent/ImgurEmbed";
import { InstagramEmbed } from "@library/embeddedContent/InstagramEmbed";
import { LinkEmbed } from "@library/embeddedContent/LinkEmbed";
import { PanoptoEmbed } from "@library/embeddedContent/PanoptoEmbed";
import { QuoteEmbed } from "@library/embeddedContent/QuoteEmbed";
import { SoundCloudEmbed } from "@library/embeddedContent/SoundCloudEmbed";
import { convertTwitterEmbeds, TwitterEmbed } from "@library/embeddedContent/TwitterEmbed";
import { VideoEmbed } from "@library/embeddedContent/VideoEmbed";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { onContent, t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { mountReact } from "@vanilla/react-utils";
import { logWarning } from "@vanilla/utils";
import { EmbedContext } from "./IEmbedContext";
import { EmbedComponentType, registeredEmbeds, IBaseEmbedProps, registerEmbed } from "./embedService.register";

const EMBED_DESCRIPTION_ID = uniqueIDFromPrefix("embed-description");

export function getEmbedForType(embedType: string): EmbedComponentType | null {
    return registeredEmbeds.get(embedType) || null;
}

export async function mountEmbed(mountPoint: HTMLElement, data: IBaseEmbedProps, inEditor: boolean) {
    ensureBuiltinEmbeds();
    return new Promise<void>((resolve, reject) => {
        const type = data.embedType || null;
        if (type === null) {
            logWarning(`Found embed with data`, data, `and no type on element`, mountPoint);
            return;
        }
        const exception: string | null = "exception" in data ? data["exception"] : null;
        if (exception !== null) {
            logWarning(`Found embed with data`, data, `and and exception`, exception, ` on element`, mountPoint);
            return;
        }
        const EmbedClass = getEmbedForType(type);
        if (EmbedClass === null) {
            logWarning(
                `Attempted to mount embed type ${type} on element`,
                mountPoint,
                `but could not find registered embed.`,
            );
            return;
        }

        const isAsync = EmbedClass.async;
        const onMountComplete = () => resolve();
        ensureEmbedDescription();

        data = {
            ...data,
            descriptionID: EMBED_DESCRIPTION_ID,
        };

        // If the component is flagged as async, then it will confirm when the render is complete.
        mountReact(
            <EmbedErrorBoundary url={data.url}>
                <EmbedContext.Provider value={data}>
                    <EmbedClass
                        {...data}
                        inEditor={inEditor}
                        onRenderComplete={isAsync ? onMountComplete : undefined}
                    />
                </EmbedContext.Provider>
            </EmbedErrorBoundary>,
            mountPoint,
            !isAsync ? onMountComplete : undefined,
            { bypassPortalManager: true },
        );
    });
}

function EmbedDescription() {
    return (
        <ScreenReaderContent id={EMBED_DESCRIPTION_ID} aria-hidden={true}>
            {t("richEditor.externalEmbed.description")}
        </ScreenReaderContent>
    );
}

function ensureEmbedDescription() {
    // Ensure we have our modal container.
    let description = document.getElementById(EMBED_DESCRIPTION_ID);

    if (!description) {
        description = document.createElement("div");
        document.body.append(description);
        mountReact(<EmbedDescription />, description);
    }
}

let builtinsRegistered = false;

/**
 * Mount the built-in embeds if they aren't already.
 */
export function ensureBuiltinEmbeds() {
    if (builtinsRegistered) {
        return;
    }
    builtinsRegistered = true;
    // Default embed registration
    registerEmbed("codepen", CodePenEmbed);
    registerEmbed("file", FileEmbed);
    registerEmbed("gettyimages", GettyImagesEmbed);
    registerEmbed("getty", GettyImagesEmbed);
    registerEmbed("giphy", GiphyEmbed);
    registerEmbed("imgur", ImgurEmbed);
    registerEmbed("instagram", InstagramEmbed);
    registerEmbed("link", LinkEmbed);
    registerEmbed("quote", QuoteEmbed);
    registerEmbed("soundcloud", SoundCloudEmbed);
    registerEmbed("twitch", VideoEmbed);
    registerEmbed("twitter", TwitterEmbed);
    registerEmbed("vimeo", VideoEmbed);
    registerEmbed("wistia", VideoEmbed);
    registerEmbed("youtube", VideoEmbed);
    registerEmbed("kaltura", VideoEmbed);
    registerEmbed("panopto", PanoptoEmbed);
    registerEmbed("image", ImageEmbed);
    registerEmbed("brightcove", BrightcoveEmbed);
    registerEmbed("mural", MuralEmbed);

    if (supportsFrames()) {
        registerEmbed("iframe", IFrameEmbed);
    }
}

// This is specifically required because of some legacy formats that don't render
// The embed json format. Twitter was converted out of global JS and merged here.
onContent(convertTwitterEmbeds);
