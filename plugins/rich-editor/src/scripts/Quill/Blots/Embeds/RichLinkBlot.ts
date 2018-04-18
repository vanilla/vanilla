/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import { setData, getData } from "@core/dom";

export default class RichLinkBlot extends FocusableEmbedBlot {
    public static lineHeight = false;
    public static blotName = "embed-link";
    public static className = "embed-link";
    public static tagName = "a";
    public static PROTOCOL_WHITELIST = ["http", "https", "mailto", "tel"];

    public static create(data) {
        const node = super.create(data) as HTMLElement;
        node.classList.add("embed");
        node.classList.add("embed-link");
        node.classList.add("embedLink");
        node.setAttribute("href", this.sanitize(data.url));
        node.setAttribute("tabindex", -1);

        node.setAttribute("target", "_blank");
        node.setAttribute("rel", "noopener noreferrer");

        let title;
        if (data.name) {
            title = document.createElement("h3");
            title.classList.add("embedLink-title");
            title.innerHTML = data.name;
        }

        let userPhoto;
        if (data.userPhoto) {
            userPhoto = document.createElement("span");
            userPhoto.classList.add("embedLink-userPhoto");
            userPhoto.classList.add("PhotoWrap");
            userPhoto.innerHTML =
                '<img src="' +
                data.userPhoto +
                '" alt="' +
                data.userName +
                '" class="ProfilePhoto ProfilePhotoMedium" tabindex=-1 />';
        }

        let source;
        if (data.source) {
            source = document.createElement("span");
            source.classList.add("embedLink-source");
            source.classList.add("meta");
            source.innerHTML = data.source;
        }

        let linkImage;
        if (data.linkImage) {
            linkImage = document.createElement("div");
            linkImage.classList.add("embedLink-image");
            linkImage.setAttribute("aria-hidden", "true");
            linkImage.setAttribute("style", "background-image: url(" + data.linkImage + ");");
        }

        let userName;
        if (data.userName) {
            userName = document.createElement("span");
            userName.classList.add("embedLink-userName");
            userName.innerHTML = data.userName;
        }

        let dateTime;
        if (data.timestamp) {
            dateTime = document.createElement("time");
            dateTime.classList.add("embedLink-dateTime");
            dateTime.classList.add("meta");
            dateTime.setAttribute("datetime", data.timestamp);
            dateTime.innerHTML = data.humanTime;
        }

        const article = document.createElement("article");
        article.classList.add("embedLink-body");

        const main = document.createElement("div");
        main.classList.add("embedLink-main");

        const header = document.createElement("div");
        header.classList.add("embedLink-header");

        const excerpt = document.createElement("div");
        excerpt.classList.add("embedLink-excerpt");
        excerpt.innerHTML = data.excerpt;

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

        setData(node, "data", data);
        return node;
    }

    public static value(node) {
        return getData(node, "data");
    }

    private static sanitize(url) {
        return sanitize(url, this.PROTOCOL_WHITELIST) ? url : "TODO FIX THIS LATER";
    }
}

function sanitize(url, protocols) {
    const anchor = document.createElement("a");
    anchor.href = url;
    const protocol = anchor.href.slice(0, anchor.href.indexOf(":"));
    return protocols.indexOf(protocol) > -1;
}
