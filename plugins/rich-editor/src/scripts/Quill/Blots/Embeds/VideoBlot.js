/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import FocusableEmbedBlot from "../Abstract/FocusableEmbedBlot";
import { t } from "@core/application";
import { setData, getData } from "@core/dom";

function simplifyFraction(numerator, denominator){
    let gcd = (a, b) => {
        return b ? gcd(b, a%b) : a;
    };
    gcd = gcd(numerator, denominator);

    numerator = numerator/gcd;
    denominator = denominator/gcd;

    return {
        numerator,
        denominator,
        shorthand: denominator + ":" + numerator,
    };
}

export default class VideoBlot extends FocusableEmbedBlot {

    static blotName = 'embed-video';
    static className = 'embed-video';
    static tagName = 'div';

    static create(data) {
        // console.log("Video Data: ", data);
        const node = super.create();
        node.classList.add('embed');
        node.classList.add('embed-video');
        node.classList.add('embedVideo');
        data.name = data.name || '';

        const ratioContainer = document.createElement('div');
        ratioContainer.classList.add('embedVideo-ratio');

        if(!data.simplifiedRatio) {
            data.simplifiedRatio = simplifyFraction(data.height, data.width);
        }

        switch(data.simplifiedRatio.shorthand) {
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
            ratioContainer.style.paddingTop = data.height / data.width * 100 + "%";
        }

        const playIcon = `<svg class="embedVideo-playIcon" xmlns="http://www.w3.org/2000/svg" viewBox="-1 -1 24 24"><title>${t("Play Video")}</title><path class="embedVideo-playIconPath embedVideo-playIconPath-circle" style="fill: currentColor; stroke-width: .3;" d="M11,0A11,11,0,1,0,22,11,11,11,0,0,0,11,0Zm0,20.308A9.308,9.308,0,1,1,20.308,11,9.308,9.308,0,0,1,11,20.308Z"/><polygon class="embedVideo-playIconPath embedVideo-playIconPath-triangle" style="fill: currentColor; stroke-width: .3;" points="8.609 6.696 8.609 15.304 16.261 11 8.609 6.696"/></svg>`;

        ratioContainer.innerHTML = `<button type="button" data-url="${data.url}" aria-label="${data.name}" class="embedVideo-playButton iconButton js-playVideo" style="background-image: url(${data.photoUrl});">${playIcon}</button>`;

        node.appendChild(ratioContainer);

        setData(node, "data", data);
        return node;
    }

    static value(node) {
        return getData(node, "data");
    }
}
