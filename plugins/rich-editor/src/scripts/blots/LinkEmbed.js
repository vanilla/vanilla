import { BlockEmbed } from "quill/blots/block";
import shave from 'shave';
import { setData, getData } from "@core/dom-utility";

export default class LinkEmbedBlock extends BlockEmbed {

    static lineHeight = false;

    static create(data) {
        // console.log("Video Data: ", data);
        const node = super.create();
        node.classList.add('embed');
        node.classList.add('embedLink');
        node.setAttribute('href', this.sanitize(data.url));

        node.setAttribute('target', '_blank');
        node.setAttribute('rel', 'noopener noreferrer');

        let userPhoto = false
        if (data.userPhoto) {
            userPhoto = document.createElement('span');
            userPhoto.classList.add('embedLink-userPhoto');
            userPhoto.classList.add('PhotoWrap');
            userPhoto.innerHTML = '<img src="' + data.userPhoto + '" alt="' + data.userName + '" class="ProfilePhoto ProfilePhotoMedium" />';
        }

        let source = false;
        if (data.source) {
            source = document.createElement('span');
            source.classList.add('embedLink-source');
            source.classList.add('meta');
            source.innerHTML = data.source;
        }

        let linkImage = false;
        if (data.linkImage) {
            linkImage = document.createElement('div');
            linkImage.classList.add('embedLink-image');
            linkImage.setAttribute('aria-hidden', 'true');
            linkImage.setAttribute('style', 'background-image: url(' + data.linkImage + ');');
        }

        let userName = false;
        if (data.userName) {
            userName = document.createElement('span');
            userName.classList.add('embedLink-userName');
            userName.innerHTML = data.userName;
        }

        let dateTime = false;
        if (data.timestamp) {
            dateTime = document.createElement('time');
            dateTime.classList.add('embedLink-dateTime');
            dateTime.classList.add('meta');
            dateTime.setAttribute('datetime', data.timestamp);
            dateTime.innerHTML = data.humanTime;
        }

        let title = false;
        if (data.name) {
            title = document.createElement('h3');
            title.classList.add('embedLink-title');
            title.innerHTML = data.name;
        }

        const article = document.createElement('article');
        article.classList.add('embedLink-body');

        const main = document.createElement('div');
        main.classList.add('embedLink-main');

        const header = document.createElement('div');
        header.classList.add('embedLink-header');

        const excerpt = document.createElement('div');
        excerpt.classList.add('embedLink-excerpt');
        excerpt.innerHTML = data.excerpt;

        // Assemble header
        if (userPhoto) {
            header.appendChild(userPhoto);
        }

        if (userName) {
            header.appendChild(userName);
        }

        if (dateTime) {
            header.appendChild(dateTime);
        }

        if (title) {
            header.appendChild(title);
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

    static sanitize(url) {
        return sanitize(url, this.PROTOCOL_WHITELIST) ? url : this.SANITIZED_URL;
    }

    static value(node) {
        return getData(node, "data");
    }
}

function sanitize(url, protocols) {
    const anchor = document.createElement('a');
    anchor.href = url;
    const protocol = anchor.href.slice(0, anchor.href.indexOf(':'));
    return protocols.indexOf(protocol) > -1;
}

LinkEmbedBlock.blotName = 'link-embed';
LinkEmbedBlock.tagName = 'a';
LinkEmbedBlock.PROTOCOL_WHITELIST = ['http', 'https', 'mailto', 'tel'];
