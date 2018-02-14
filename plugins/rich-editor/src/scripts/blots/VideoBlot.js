import Inline from "quill/blots/Inline";
import Block from "quill/blots/Block";
import Embed from "quill/blots/embed";
import { t } from "@core/utility";
import { htmlStringToElement } from "@core/utility";

export default class VideoBlot extends Embed {
    static create(data) {
        // console.log("Video Data: ", data);
        const node = super.create();
        node.classList.add('embedVideo');

        const ratioContainer = document.createElement('div');
        ratioContainer.classList.add('embedVideo-ratio');

        const videoPlaceholder = htmlStringToElement("<button aria-label='" + t('Play Video') + "' data-url='" + data.url + "' class=\"embedVideo-playButton iconButton\" type=\"button\" style=\"background-image: url('" + data.photoUrl + "')\">\n" +
            "<svg class=\"embedVideo-playIcon\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"-1 -1 24 24\">\n" +
            "    <title>" + t('Play Video') + "</title>\n" +
            "    <path class=\"embedVideo-playIconPath embedVideo-playIconPath-circle\" style=\"fill: currentColor; stroke-width: .3;\" d=\"M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z\"/>\n" +
            "    <polygon class=\"embedVideo-playIconPath embedVideo-playIconPath-triangle\" style=\"fill: currentColor; stroke-width: .3;\" points=\"8.609 6.696 8.609 15.304 16.261 11 8.609 6.696\"/>\n" +
            "  </svg>\n" +
            "</button>");

        switch(data.ratio) {
        case "21:9":
            ratioContainer.classList.add('is21by9');
            break;
        case "16:9":
            ratioContainer.classList.add('is16by9');
            break;
        case "4:3":
            ratioContainer.classList.add('is4by3');
            break;
        case "1:1":
            ratioContainer.classList.add('is1by1');
            break;
        default:
            ratioContainer.style.paddingTop = data.height / data.width + "%";
        }

        ratioContainer.appendChild(videoPlaceholder);
        node.appendChild(ratioContainer);
        return node;
    }
}

VideoBlot.blotName = 'video-placeholder';
VideoBlot.tagName = 'div';
