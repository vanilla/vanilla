/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import classNames from "classnames";

const currentColorFill = {
    fill: "currentColor",
};

export function BoldIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Bold")}</title>
            <path
                d="M6.511,18v-.62a4.173,4.173,0,0,0,.845-.093.885.885,0,0,0,.736-.79,5.039,5.039,0,0,0,.063-.884V8.452a6.585,6.585,0,0,0-.047-.876,1.116,1.116,0,0,0-.194-.527.726.726,0,0,0-.4-.263,3.658,3.658,0,0,0-.674-.1v-.62h4.975a7.106,7.106,0,0,1,3.6.752A2.369,2.369,0,0,1,16.68,8.964q0,1.843-2.651,2.6v.062a4.672,4.672,0,0,1,1.542.24,3.39,3.39,0,0,1,1.171.674,3.036,3.036,0,0,1,.744,1.023,3.125,3.125,0,0,1,.263,1.287,2.49,2.49,0,0,1-.38,1.379,3.05,3.05,0,0,1-1.092.992,7.794,7.794,0,0,1-3.8.775Zm6.076-.945q2.5,0,2.5-2.248a2.3,2.3,0,0,0-.9-2.015,3.073,3.073,0,0,0-1.2-.465,9.906,9.906,0,0,0-1.806-.139h-.744v3.1a1.664,1.664,0,0,0,.5,1.364A2.659,2.659,0,0,0,12.587,17.055Zm-1.24-5.8a4.892,4.892,0,0,0,1.21-.131,2.69,2.69,0,0,0,.868-.38,1.8,1.8,0,0,0,.743-1.6,2.107,2.107,0,0,0-.557-1.635,2.645,2.645,0,0,0-1.8-.5h-1.1q-.279,0-.279.264v3.983Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function ItalicIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Italic")}</title>
            <path
                d="M11.472,15.4a4.381,4.381,0,0,0-.186,1.085.744.744,0,0,0,.333.713,2.323,2.323,0,0,0,1.077.186L12.51,18H7.566l.17-.62a3.8,3.8,0,0,0,.791-.07,1.282,1.282,0,0,0,.566-.271,1.62,1.62,0,0,0,.41-.558,5.534,5.534,0,0,0,.326-.93L11.642,8.7a5.332,5.332,0,0,0,.233-1.271.577.577,0,0,0-.349-.612,3.714,3.714,0,0,0-1.186-.132l.171-.62h5.038l-.171.62a3.058,3.058,0,0,0-.852.1,1.246,1.246,0,0,0-.59.38,2.578,2.578,0,0,0-.441.774,11.525,11.525,0,0,0-.4,1.287Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function StrikeIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Strikethrough")}</title>
            <path
                d="M12.258,13H6V12h4.2l-.05-.03a4.621,4.621,0,0,1-1.038-.805,2.531,2.531,0,0,1-.55-.892A3.285,3.285,0,0,1,8.4,9.2a3.345,3.345,0,0,1,.256-1.318,3.066,3.066,0,0,1,.721-1.046,3.242,3.242,0,0,1,1.1-.682,3.921,3.921,0,0,1,1.4-.24,3.641,3.641,0,0,1,1.271.217,4.371,4.371,0,0,1,1.194.7l.4-.7h.357l.171,3.085h-.574A3.921,3.921,0,0,0,13.611,7.32a2.484,2.484,0,0,0-1.7-.619,2.269,2.269,0,0,0-1.5.465,1.548,1.548,0,0,0-.558,1.255,1.752,1.752,0,0,0,.124.674,1.716,1.716,0,0,0,.4.574,4.034,4.034,0,0,0,.729.542,9.854,9.854,0,0,0,1.116.566,20.49,20.49,0,0,1,1.906.953q.232.135.435.27h4.6v1H15.675a2.263,2.263,0,0,1,.3.544,3.023,3.023,0,0,1,.186,1.093,3.236,3.236,0,0,1-1.177,2.541,4.014,4.014,0,0,1-1.334.721,5.393,5.393,0,0,1-1.7.256,4.773,4.773,0,0,1-1.588-.248,4.885,4.885,0,0,1-1.434-.837l-.434.76H8.132L7.9,14.358h.573a3.886,3.886,0,0,0,.411,1.255A3.215,3.215,0,0,0,10.7,17.155a3.872,3.872,0,0,0,1.294.21,2.786,2.786,0,0,0,1.813-.543,1.8,1.8,0,0,0,.667-1.473,1.752,1.752,0,0,0-.573-1.34,4.04,4.04,0,0,0-.83-.6Q12.723,13.217,12.258,13Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function CodeIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Paragraph Code Block")}</title>
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M4.818,14.032q0-1.27-1.818-1.271V11.455a2.429,2.429,0,0,0,1.371-.311,1.075,1.075,0,0,0,.447-.947V8.071A1.763,1.763,0,0,1,5.56,6.5,4,4,0,0,1,7.785,6V7.244a1.949,1.949,0,0,0-1.131.318,1.019,1.019,0,0,0-.352.844v2.031q0,1.359-1.566,1.626v.083q1.566.246,1.566,1.62V15.81a1.034,1.034,0,0,0,.348.847,1.836,1.836,0,0,0,1.135.3v1.251a3.89,3.89,0,0,1-2.273-.537,1.982,1.982,0,0,1-.694-1.692ZM9.009,15.14a1.05,1.05,0,0,1,.26-.766,1,1,0,0,1,.752-.267.992.992,0,0,1,.758.277,1.063,1.063,0,0,1,.26.756,1.089,1.089,0,0,1-.263.769.98.98,0,0,1-.755.283.973.973,0,0,1-.752-.28A1.088,1.088,0,0,1,9.009,15.14Zm3.848,0a1.054,1.054,0,0,1,.26-.766,1.185,1.185,0,0,1,1.511.01,1.063,1.063,0,0,1,.26.756,1.089,1.089,0,0,1-.263.769,1.151,1.151,0,0,1-1.508,0A1.093,1.093,0,0,1,12.857,15.14Zm6.105.991a1.811,1.811,0,0,1-.68,1.565,3.788,3.788,0,0,1-2.178.513V16.958a1.765,1.765,0,0,0,1.012-.263,1.009,1.009,0,0,0,.363-.885V14.019a1.994,1.994,0,0,1,.362-1.279,1.879,1.879,0,0,1,1.2-.594v-.083q-1.566-.266-1.565-1.626V8.406a1.084,1.084,0,0,0-.312-.844A1.69,1.69,0,0,0,16.1,7.244V6a3.583,3.583,0,0,1,2.191.523,2.044,2.044,0,0,1,.667,1.712V10.2a1.077,1.077,0,0,0,.434.971,2.356,2.356,0,0,0,1.289.287v1.306a2.342,2.342,0,0,0-1.282.29,1.087,1.087,0,0,0-.441.981Z"
            />
        </svg>
    );
}

export function LinkIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Link")}</title>
            <path
                d="M13.575,10.4a3.824,3.824,0,0,1,.266,5.107c-.17.211-.076.106-2.318,2.348a3.821,3.821,0,0,1-5.4-5.4l1.731-1.731a.327.327,0,0,1,.559.223,4.735,4.735,0,0,0,.129,1.033.325.325,0,0,1-.087.3L7.2,13.53a2.293,2.293,0,0,0,3.243,3.242L12.5,14.72a2.293,2.293,0,0,0-.623-3.685.329.329,0,0,1-.179-.337,1.412,1.412,0,0,1,.4-.829l.12-.12a.324.324,0,0,1,.375-.062A3.792,3.792,0,0,1,13.575,10.4ZM17.853,6.12a3.819,3.819,0,0,0-5.4,0C10.207,8.362,10.3,8.257,10.131,8.468a3.82,3.82,0,0,0,1.25,5.818.325.325,0,0,0,.375-.063l.12-.119a1.412,1.412,0,0,0,.4-.83.329.329,0,0,0-.179-.337,2.291,2.291,0,0,1-.623-3.684L13.53,7.2a2.293,2.293,0,1,1,3.242,3.243l-1.251,1.251a.322.322,0,0,0-.087.3,4.726,4.726,0,0,1,.129,1.032.328.328,0,0,0,.559.224c.316-.315.836-.837,1.731-1.732A3.819,3.819,0,0,0,17.853,6.12Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function EmojiIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Emoji")}</title>
            <path
                fill="currentColor"
                d="M12,4a8,8,0,1,0,8,8A8,8,0,0,0,12,4Zm0,14.644A6.644,6.644,0,1,1,18.644,12,6.651,6.651,0,0,1,12,18.644ZM10.706,10.2a1.25,1.25,0,1,0-1.249,1.25A1.249,1.249,0,0,0,10.706,10.2Zm3.837-1.249a1.25,1.25,0,1,0,1.25,1.249A1.249,1.249,0,0,0,14.543,8.953Zm.2,5.237a.357.357,0,0,0-.493.1,2.825,2.825,0,0,1-4.494,0,.355.355,0,1,0-.593.392,3.532,3.532,0,0,0,5.68,0A.354.354,0,0,0,14.74,14.19Z"
            />
        </svg>
    );
}

export function EmbedErrorIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <title>{t("Warning")}</title>
            <path
                d="M11.651,3.669,2.068,21.75H21.234Zm.884-1,10,18.865A1,1,0,0,1,21.649,23h-20a1,1,0,0,1-.884-1.468l10-18.865a1,1,0,0,1,1.767,0Zm.232,13.695H10.547L10.2,10h2.905Zm-2.535,2.354a1.239,1.239,0,0,1,.363-.952,1.491,1.491,0,0,1,1.056-.34,1.443,1.443,0,0,1,1.039.34,1.258,1.258,0,0,1,.353.952,1.224,1.224,0,0,1-.367.945A1.452,1.452,0,0,1,11.65,20a1.5,1.5,0,0,1-1.042-.34A1.207,1.207,0,0,1,10.231,18.716Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function PilcrowIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Paragraph")}</title>
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M15,6 L17,6 L17,18 L15,18 L15,6 Z M11,6 L13.0338983,6 L13.0338983,18 L11,18 L11,6 Z M11,13.8666667 C8.790861,13.8666667 7,12.1056533 7,9.93333333 C7,7.76101332 8.790861,6 11,6 C11,7.68571429 11,11.6190476 11,13.8666667 Z"
            />
        </svg>
    );
}

export function Heading2Icon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("H2")}</title>
            <path
                d="M12.3,17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607V7.006H12.3Zm8,0H13.526V15.783L16.1,13.192a22.007,22.007,0,0,0,1.514-1.657,3.978,3.978,0,0,0,.543-.92,2.475,2.475,0,0,0,.171-.923,1.4,1.4,0,0,0-.407-1.066,1.557,1.557,0,0,0-1.124-.39,3,3,0,0,0-1.111.212,5.239,5.239,0,0,0-1.241.766l-.868-1.06a5.612,5.612,0,0,1,1.62-1,4.744,4.744,0,0,1,1.675-.294,3.294,3.294,0,0,1,2.235.728,2.46,2.46,0,0,1,.841,1.959,3.453,3.453,0,0,1-.242,1.285,5.212,5.212,0,0,1-.746,1.254,17.041,17.041,0,0,1-1.671,1.747l-1.736,1.682v.068H20.3Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function Heading3Icon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("H3")}</title>
            <path
                d="M10.658,7.006H12.3V17H10.658V12.5H6.051V17H4.417V7.006H6.051v4.088h4.607Zm8.93,5.533a3.016,3.016,0,0,0-1.806-.748v-.055a2.789,2.789,0,0,0,1.56-.851A2.315,2.315,0,0,0,19.9,9.3a2.131,2.131,0,0,0-.848-1.791,3.8,3.8,0,0,0-2.36-.65,5.251,5.251,0,0,0-3.2,1.012l.786,1.121a5.226,5.226,0,0,1,1.245-.625,3.76,3.76,0,0,1,1.1-.161,1.881,1.881,0,0,1,1.232.349,1.22,1.22,0,0,1,.417.991q0,1.654-2.4,1.654H14.99v1.306h.869a4.066,4.066,0,0,1,2,.376,1.267,1.267,0,0,1,.636,1.176,1.559,1.559,0,0,1-.574,1.333,2.89,2.89,0,0,1-1.738.43,5.794,5.794,0,0,1-1.369-.171,6.372,6.372,0,0,1-1.347-.485V16.6a6.532,6.532,0,0,0,2.8.54,4.676,4.676,0,0,0,2.9-.783,2.637,2.637,0,0,0,1.019-2.225A2.143,2.143,0,0,0,19.588,12.539Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function Heading4Icon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("H4")}</title>
            <path style={currentColorFill} d="M12.3,17h-1.6v-4.5H6.1V17H4.4V7h1.6v4.1h4.6V7h1.6V17z" />
            <path
                style={currentColorFill}
                d="M20.8,14.9h-1.3v2.2h-1.6v-2.2h-4.6v-1.2l4.6-6.6h1.6v6.5h1.3V14.9z M17.9,13.6v-2.5c0-0.9,0-1.6,0.1-2.2h-0.1
		c-0.1,0.3-0.3,0.7-0.6,1.1l-2.5,3.6H17.9z"
            />
        </svg>
    );
}

export function Heading5Icon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("H5")}</title>
            <path style={currentColorFill} d="M12.3,17H10.7V12.5H6.1V17H4.4V7H6v4.1h4.6V7h1.6V17Z" />
            <path
                style={currentColorFill}
                d="M17,10.7a3.649,3.649,0,0,1,2.4.8,2.792,2.792,0,0,1,.9,2.2,3.04,3.04,0,0,1-1,2.5,4.1,4.1,0,0,1-2.8.9,5.991,5.991,0,0,1-2.6-.5V15.2a5.023,5.023,0,0,0,1.3.5,6.882,6.882,0,0,0,1.3.2,2.348,2.348,0,0,0,1.6-.5,1.678,1.678,0,0,0,.6-1.4c0-1.2-.8-1.8-2.3-1.8a2.485,2.485,0,0,0-.8.1c-.3,0-.6.1-.8.1l-.8-.5L14.4,7h5.2V8.4H15.8l-.2,2.5c.2,0,.4-.1.6-.1S16.6,10.7,17,10.7Z"
            />
        </svg>
    );
}

export function BlockquoteIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Quote")}</title>
            <path
                d="M10.531,17.286V12.755H8.122a9.954,9.954,0,0,1,.1-1.408,4.22,4.22,0,0,1,.388-1.286,2.62,2.62,0,0,1,.735-.918A1.815,1.815,0,0,1,10.49,8.8V6.755a3.955,3.955,0,0,0-2,.49A4.164,4.164,0,0,0,7.082,8.551a5.84,5.84,0,0,0-.817,1.9A9.65,9.65,0,0,0,6,12.755v4.531Zm7.469,0V12.755H15.592a9.954,9.954,0,0,1,.1-1.408,4.166,4.166,0,0,1,.388-1.286,2.606,2.606,0,0,1,.734-.918A1.819,1.819,0,0,1,17.959,8.8V6.755a3.958,3.958,0,0,0-2,.49,4.174,4.174,0,0,0-1.408,1.306,5.86,5.86,0,0,0-.816,1.9,9.649,9.649,0,0,0-.266,2.306v4.531Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function CodeBlockIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Paragraph Code Block")}</title>
            <path
                fill="currentColor"
                fillRule="evenodd"
                d="M9.11588626,16.5074223 L3.14440918,12.7070466 L3.14440918,11.6376386 L9.11588626,7.32465415 L9.11588626,9.04808032 L4.63575044,12.0883808 L9.11588626,14.7663199 L9.11588626,16.5074223 Z M14.48227,5.53936141 L11.1573124,18.4606386 L9.80043634,18.4606386 L13.131506,5.53936141 L14.48227,5.53936141 Z M15.1729321,14.7663199 L19.6530679,12.0883808 L15.1729321,9.04808032 L15.1729321,7.32465415 L21.1444092,11.6376386 L21.1444092,12.7070466 L15.1729321,16.5074223 L15.1729321,14.7663199 Z"
            />
        </svg>
    );
}

export function SpoilerIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    const spoilerClasses = props.className;
    return (
        <svg className={classNames(spoilerClasses, classesRichEditor.icon, props.className)} viewBox="0 0 24 24">
            <title>{t("Spoiler")}</title>
            <path
                d="M8.138,16.569l.606-.606a6.677,6.677,0,0,0,1.108.562,5.952,5.952,0,0,0,2.674.393,7.935,7.935,0,0,0,1.008-.2,11.556,11.556,0,0,0,5.7-4.641.286.286,0,0,0-.02-.345c-.039-.05-.077-.123-.116-.173a14.572,14.572,0,0,0-2.917-3.035l.6-.6a15.062,15.062,0,0,1,2.857,3.028,1.62,1.62,0,0,0,.154.245,1.518,1.518,0,0,1,.02,1.5,12.245,12.245,0,0,1-6.065,4.911,6.307,6.307,0,0,1-1.106.22,4.518,4.518,0,0,1-.581.025,6.655,6.655,0,0,1-2.383-.466A8.023,8.023,0,0,1,8.138,16.569Zm-.824-.59a14.661,14.661,0,0,1-2.965-3.112,1.424,1.424,0,0,1,0-1.867A13.69,13.69,0,0,1,8.863,6.851a6.31,6.31,0,0,1,6.532.123c.191.112.381.231.568.356l-.621.621c-.092-.058-.184-.114-.277-.168a5.945,5.945,0,0,0-3.081-.909,6.007,6.007,0,0,0-2.868.786,13.127,13.127,0,0,0-4.263,3.929c-.214.271-.214.343,0,.639a13.845,13.845,0,0,0,3.059,3.153ZM13.9,9.4l-.618.618a2.542,2.542,0,0,0-3.475,3.475l-.61.61A3.381,3.381,0,0,1,12,8.822,3.4,3.4,0,0,1,13.9,9.4Zm.74.674a3.3,3.3,0,0,1,.748,2.138,3.382,3.382,0,0,1-5.515,2.629l.6-.6a2.542,2.542,0,0,0,3.559-3.559Zm-3.146,3.146L13.008,11.7a1.129,1.129,0,0,1-1.516,1.516Zm-.6-.811a1.061,1.061,0,0,1-.018-.2A1.129,1.129,0,0,1,12,11.079a1.164,1.164,0,0,1,.2.017Z"
                style={currentColorFill}
            />
            <polygon
                points="19.146 4.146 19.854 4.854 4.854 19.854 4.146 19.146 19.146 4.146"
                style={currentColorFill}
            />
        </svg>
    );
}

export function EmbedIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Embed")}</title>
            <path
                fill="currentColor"
                d="M4.5,5.5a1,1,0,0,0-1,1v11a1,1,0,0,0,1,1h15a1,1,0,0,0,1-1V6.5a1,1,0,0,0-1-1ZM4.5,4h15A2.5,2.5,0,0,1,22,6.5v11A2.5,2.5,0,0,1,19.5,20H4.5A2.5,2.5,0,0,1,2,17.5V6.5A2.5,2.5,0,0,1,4.5,4Zm5.592,12.04-1.184.92-3.5-4.5v-.92l3.5-4.5,1.184.92L6.95,12Zm3.816,0L17.05,12,13.908,7.96l1.184-.92,3.5,4.5v.92l-3.5,4.5Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function ImageIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Image")}</title>
            <path
                fill="currentColor"
                fillRule="nonzero"
                d="M5,17V15l2.294-3.212a.3.3,0,0,1,.418-.07h0c.013.01.025.021.037.032L10,14l4.763-5.747a.3.3,0,0,1,.422-.041h0l.02.018L19,12v5ZM4.5,5.5a1,1,0,0,0-1,1v11a1,1,0,0,0,1,1h15a1,1,0,0,0,1-1V6.5a1,1,0,0,0-1-1ZM4.5,4h15A2.5,2.5,0,0,1,22,6.5v11A2.5,2.5,0,0,1,19.5,20H4.5A2.5,2.5,0,0,1,2,17.5V6.5A2.5,2.5,0,0,1,4.5,4Zm3,6.2A1.7,1.7,0,1,1,9.2,8.5h0A1.7,1.7,0,0,1,7.5,10.2Z"
            />
        </svg>
    );
}

export function AttachmentIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Attachment")}</title>
            <path
                fill="currentColor"
                d="M17.25,9.045a.75.75,0,0,1,1.5,0v6.91A6.63,6.63,0,0,1,12,22.75a6.63,6.63,0,0,1-6.75-6.795V7.318A4.811,4.811,0,0,1,10.286,2.25a4.81,4.81,0,0,1,5.035,5.068v7.773c0,2.308-1.254,4.2-3.321,4.2s-3.321-1.9-3.321-4.2V9.045a.75.75,0,0,1,1.5,0v6.046c0,1.578.745,2.7,1.821,2.7s1.821-1.126,1.821-2.7V7.318A3.319,3.319,0,0,0,10.286,3.75,3.319,3.319,0,0,0,6.75,7.318v8.637A5.132,5.132,0,0,0,12,21.25a5.132,5.132,0,0,0,5.25-5.295Z"
                style={currentColorFill}
            />
        </svg>
    );
}

export function ListUnorderedIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("List Unordered")}</title>
            <path
                fill="currentColor"
                fillRule="nonzero"
                d="M10.5 7a1 1 0 1 1 0-2h8a1 1 0 0 1 0 2h-8zm0 6a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2h-8zm0 6a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2h-8zM4.7 7c-.11 0-.2-.448-.2-1s.09-1 .2-1h1.6c.11 0 .2.448.2 1s-.09 1-.2 1H4.7zm0 6c-.11 0-.2-.448-.2-1s.09-1 .2-1h1.6c.11 0 .2.448.2 1s-.09 1-.2 1H4.7zm0 6c-.11 0-.2-.448-.2-1s.09-1 .2-1h1.6c.11 0 .2.448.2 1s-.09 1-.2 1H4.7z"
            />
        </svg>
    );
}

export function ListOrderedIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("List Ordered")}</title>
            <path
                fill="currentColor"
                d="M10.5 7a1 1 0 1 1 0-2h8a1 1 0 0 1 0 2h-8zM6.479 8h-.906V5.521l.009-.407.015-.445c-.15.15-.255.249-.314.296l-.492.395-.437-.545 1.38-1.098h.745V8zm4.021 5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2h-8zm-3.266 1H4.24v-.63l1.075-1.087a9.93 9.93 0 0 0 .624-.678c.098-.126.168-.243.211-.35a.891.891 0 0 0 .065-.334c0-.172-.048-.3-.142-.384a.551.551 0 0 0-.38-.126c-.166 0-.327.038-.483.114-.156.077-.32.185-.49.326l-.491-.583c.21-.18.385-.307.524-.381.139-.075.29-.132.454-.172.164-.04.348-.06.55-.06.268 0 .505.05.71.147.205.097.364.234.477.41.114.176.17.377.17.603 0 .198-.034.383-.104.556-.07.172-.177.35-.322.531-.146.182-.402.44-.77.777l-.55.518v.041h1.866V14zm3.266 5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2h-8zm-3.433-2.325c0 .267-.08.495-.243.682-.162.188-.39.317-.682.387v.018c.345.043.607.148.785.315.177.167.266.392.266.675 0 .412-.15.733-.448.962-.299.23-.725.345-1.28.345-.465 0-.877-.078-1.236-.232v-.77c.166.084.348.152.547.205.2.052.397.079.592.079.299 0 .52-.051.662-.153.143-.101.214-.264.214-.489 0-.201-.082-.344-.246-.428-.164-.084-.426-.125-.785-.125h-.325v-.695h.33c.333 0 .575-.043.729-.13.153-.087.23-.236.23-.447 0-.324-.203-.486-.61-.486-.14 0-.283.023-.429.07a2.097 2.097 0 0 0-.485.243l-.419-.624c.391-.281.857-.422 1.398-.422.443 0 .793.09 1.05.27.257.18.385.43.385.75z"
            />
        </svg>
    );
}

export function IndentIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Indent")}</title>
            <path
                fill="currentColor"
                d="M10.5 11a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2h-8zm0 4a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2h-8zM5.438 19h13.125c.517 0 .937-.448.937-1s-.42-1-.938-1H5.438c-.517 0-.937.448-.937 1s.42 1 .938 1zM5.438 7h13.125c.517 0 .937-.448.937-1s-.42-1-.938-1H5.438C4.92 5 4.5 5.448 4.5 6s.42 1 .938 1zM8.5 12l-3 3V9z"
            />
        </svg>
    );
}
export function OutdentIcon(props: { className?: string }) {
    const classesRichEditor = richEditorClasses(false);
    return (
        <svg
            className={classNames("richEditorButton-icon", classesRichEditor.icon, props.className)}
            viewBox="0 0 24 24"
        >
            <title>{t("Outdent")}</title>
            <path
                fill="currentColor"
                d="M10.5 11a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2h-8zm0 4a1 1 0 1 1 0-2h8a1 1 0 1 1 0 2h-8zM5.438 19h13.125c.517 0 .937-.448.937-1s-.42-1-.938-1H5.438c-.517 0-.937.448-.937 1s.42 1 .938 1zM5.438 7h13.125c.517 0 .937-.448.937-1s-.42-1-.938-1H5.438C4.92 5 4.5 5.448 4.5 6s.42 1 .938 1zM4.5 12l3-3v6z"
            />
        </svg>
    );
}
