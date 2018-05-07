/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { registerEmbed, IEmbedData } from "@core/embeds";

export default function init() {
    registerEmbed("link", renderer);
}

export async function renderer(node: HTMLElement, data: IEmbedData) {
    node.classList.add("embed-link");
    node.classList.add("embedLink");
    node.setAttribute("href", data.url);

    node.setAttribute("target", "_blank");
    node.setAttribute("rel", "noopener noreferrer");

    let title;
    if (data.name) {
        title = document.createElement("h3");
        title.classList.add("embedLink-title");
        title.innerHTML = data.name;
    }

    let userPhoto;
    if (data.attributes.userPhoto) {
        userPhoto = document.createElement("span");
        userPhoto.classList.add("embedLink-userPhoto");
        userPhoto.classList.add("PhotoWrap");
        userPhoto.innerHTML =
            '<img src="' +
            data.attributes.userPhoto +
            '" alt="' +
            data.attributes.userName +
            '" class="ProfilePhoto ProfilePhotoMedium" tabindex=-1 />';
    }

    let source;
    if (data.url) {
        source = document.createElement("span");
        source.classList.add("embedLink-source");
        source.classList.add("meta");
        source.innerHTML = data.url;
    }

    let linkImage;
    if (data.photoUrl) {
        linkImage = document.createElement("div");
        linkImage.classList.add("embedLink-image");
        linkImage.setAttribute("aria-hidden", "true");
        linkImage.setAttribute("style", "background-image: url(" + data.photoUrl + ");");
    }

    let userName;
    if (data.attributes.userName) {
        userName = document.createElement("span");
        userName.classList.add("embedLink-userName");
        userName.innerHTML = data.attributes.userName;
    }

    let dateTime;
    if (data.attributes.timestamp) {
        dateTime = document.createElement("time");
        dateTime.classList.add("embedLink-dateTime");
        dateTime.classList.add("meta");
        dateTime.setAttribute("datetime", data.attributes.timestamp);
        dateTime.innerHTML = data.attributes.humanTime;
    }

    const article = document.createElement("article");
    article.classList.add("embedLink-body");

    const main = document.createElement("div");
    main.classList.add("embedLink-main");

    const header = document.createElement("div");
    header.classList.add("embedLink-header");

    const excerpt = document.createElement("div");
    excerpt.classList.add("embedLink-excerpt");
    excerpt.innerHTML = data.body || "";

    // Assemble header
    if (title) {
        header.appendChild(title);
    }

    if (userPhoto) {
        header.appendChild(userPhoto);
    }

    if (userName) {
        header.appendChild(userName);
    }

    if (dateTime) {
        header.appendChild(dateTime);
    }

    if (source) {
        header.appendChild(source);
    }

    // Assemble main
    main.appendChild(header);
    main.appendChild(excerpt);

    // Assemble Article
    if (linkImage) {
        article.appendChild(linkImage);
    }
    article.appendChild(main);

    // Assemble component
    node.appendChild(article);
}
