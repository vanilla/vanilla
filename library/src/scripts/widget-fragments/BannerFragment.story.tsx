/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import BannerFragment from "@library/widget-fragments/BannerFragment.template";
import "./BannerFragment.template.css";

export default {
    title: "Fragments/Banner",
};

export function Template() {
    return (
        <BannerFragment
            titleType={"static"}
            title={"Banner Title"}
            descriptionType={"static"}
            description={
                "Sample Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat."
            }
            showSearch={true}
            alignment={"center"}
            background={{
                color: "rgb(3, 108, 163)",
                useOverlay: true,
                imageSource: "https://us.v-cdn.net/5022541/uploads/726/MNT0DAGT2S4K.jpg",
            }}
            textColor={"rgba(255, 255, 255, 1)"}
        />
    );
}
