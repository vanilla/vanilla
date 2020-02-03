/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import { bannerClasses, bannerVariables } from "@library/banner/bannerStyles";
import { colorOut } from "@library/styles/styleHelpers";

export function DefaultBannerBg() {
    const classes = bannerClasses();
    const vars = bannerVariables();

    return (
        <svg
            className={classes.defaultBannerSVG}
            xmlns="http://www.w3.org/2000/svg"
            xmlnsXlink="http://www.w3.org/1999/xlink"
            viewBox="0 0 1600 250"
            preserveAspectRatio="xMidYMid slice"
        >
            <defs>
                <linearGradient
                    id="a"
                    x1="399.6"
                    x2="1238.185"
                    y1="-398.455"
                    y2="440.13"
                    gradientTransform="matrix(1 0 0 -1 0 252)"
                    gradientUnits="userSpaceOnUse"
                >
                    <stop offset="0" stopColor="#9fa2a4"></stop>
                    <stop offset="1" stopColor="#dcddde"></stop>
                </linearGradient>
                <linearGradient
                    id="b"
                    x1="-8455.753"
                    x2="-5370.533"
                    y1="-1501.49"
                    y2="1583.73"
                    gradientTransform="matrix(-.264 0 0 -1 -1028.524 252)"
                    xlinkHref="#a"
                ></linearGradient>
                <linearGradient
                    id="c"
                    x1="390.247"
                    x2="1197.197"
                    y1="-389.102"
                    y2="417.848"
                    xlinkHref="#a"
                ></linearGradient>
                <linearGradient
                    id="d"
                    x1="399.6"
                    x2="1246.556"
                    y1="-398.455"
                    y2="448.501"
                    xlinkHref="#a"
                ></linearGradient>
                <linearGradient
                    id="e"
                    x1="-10482.125"
                    x2="-7325.674"
                    y1="-1392.28"
                    y2="1764.172"
                    gradientTransform="matrix(-.264 0 0 -1 -1550.139 311.401)"
                    xlinkHref="#a"
                ></linearGradient>
                <linearGradient
                    id="f"
                    x1="2590.443"
                    x2="5029.843"
                    y1="-1082.229"
                    y2="1357.171"
                    gradientTransform="matrix(.339 0 0 -1 -489.358 311.401)"
                    xlinkHref="#a"
                ></linearGradient>
                <clipPath id="g">
                    <path fill="none" d="M-1.2 0H1598.8V250H-1.2z"></path>
                </clipPath>
            </defs>
            <g style={{ isolation: "isolate" }}>
                <path fill={colorOut(vars.outerBackground.color)} d="M-0 0H1600V250H-0z"></path>
                <path
                    fill="url(#a)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply" }}
                    d="M-.4 250s157.2-125.2 321.9-125 217.6 87.3 488.1 87.3 408-149.6 565.9-149.6 224.1 118.4 224.1 118.4v68.9z"
                ></path>
                <path
                    fill="url(#b)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply", isolation: "isolate" }}
                    d="M1601.2 205.755s-157.2-125.2-321.9-125-217.6 87.3-488.1 87.3-408-149.5-565.9-149.5-224.1 118.3-224.1 118.3l-1.6 113.6h1600z"
                    opacity="0.43"
                ></path>
                <path
                    fill="url(#c)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply", isolation: "isolate" }}
                    d="M-.2 212.755s162.4-169.7 496-149.6c282.8 17 373.6 129.5 566.1 140.7 192.4 11.2 531.8 26.8 531.8 26.8l6 19.8H-.4z"
                    opacity="0.4"
                ></path>
                <path
                    fill="url(#d)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply", isolation: "isolate" }}
                    d="M-.4 250s176.8-94.5 537.2-94.5 363.8 74.6 525 74.6 218-203.1 356.4-203.1 181.4 223 181.4 223H-.4z"
                    opacity="0.4"
                ></path>
                <path
                    fill="url(#e)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply", isolation: "isolate" }}
                    d="M1600.4 116.955l-.8-116.5c-17.382 0-372.332-3.194-388.112 1.777C1153.205 20.59 1016.513 118 770.388 116.5 572.8 115.3 458.1 27.455 380.173-.555L-.4.455l.8 77.1-.8 172.9h1600z"
                    opacity="0.43"
                ></path>
                <path
                    fill="url(#f)"
                    fillRule="evenodd"
                    style={{ mixBlendMode: "multiply", isolation: "isolate" }}
                    d="M.5 116.955s156.8-71.6 321.1-71.5 168.6 70.758 438.5 70.758S1215.5 9.955 1373 9.955s223.6 67.7 223.6 67.7l.8 172.9H1.3z"
                    opacity="0.43"
                ></path>
                <g
                    fill="none"
                    stroke={colorOut(vars.outerBackground.color.darken("50%"))}
                    strokeDasharray="1,11"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    clipPath="url(#g)"
                    opacity="0.5"
                >
                    <path strokeWidth="3" d="M1609.4 230.155c-567.3 297.5-677.1-176.9-1531 344.9"></path>
                    <path strokeWidth="2.935" d="M1609.2 213.955c-582.9 317.6-702.3-174.1-1536.9 332.1"></path>
                    <path strokeWidth="2.871" d="M1608.9 197.755c-598.4 337.7-727.4-171.2-1542.7 319.4"></path>
                    <path strokeWidth="2.806" d="M1608.6 181.555c-613.9 357.8-752.5-168.3-1548.5 306.7"></path>
                    <path strokeWidth="2.742" d="M1608.4 165.355C979 543.255 830.7-.145 54 459.355"></path>
                    <path strokeWidth="2.677" d="M1608.1 149.155c-644.9 398-802.8-162.6-1560.2 281.3"></path>
                    <path strokeWidth="2.613" d="M1607.9 132.955c-660.5 418.1-828-159.8-1566 268.5"></path>
                    <path strokeWidth="2.548" d="M1607.6 116.755c-676 438.2-853.1-156.9-1571.8 255.8"></path>
                    <path strokeWidth="2.484" d="M1607.3 100.555c-691.5 458.3-878.2-154-1577.6 243.1"></path>
                    <path strokeWidth="2.419" d="M1607.1 84.355c-707 478.4-903.4-151.2-1583.5 230.4"></path>
                    <path strokeWidth="2.355" d="M1606.8 68.155c-722.5 498.5-928.5-148.3-1589.3 217.6"></path>
                    <path strokeWidth="2.29" d="M1606.6 51.955c-738.1 518.6-953.7-145.5-1595.2 204.9"></path>
                    <path strokeWidth="2.226" d="M1606.3 35.755c-753.6 538.7-978.8-142.6-1601 192.2"></path>
                    <path strokeWidth="2.161" d="M1606 19.555c-769.1 558.8-1003.8-139.7-1606.7 179.5"></path>
                    <path strokeWidth="2.097" d="M1605.8 3.355c-784.6 578.9-1029-136.9-1612.6 166.8"></path>
                    <path strokeWidth="2.032" d="M1605.5-12.845c-800.1 598.9-1054.2-134-1618.4 154"></path>
                    <path strokeWidth="1.968" d="M1605.3-29.045C789.6 590.055 526-160.145-19 112.255"></path>
                    <path strokeWidth="1.903" d="M1605-45.245c-831.2 639.2-1104.4-128.3-1630.1 128.6"></path>
                    <path strokeWidth="1.839" d="M1604.7-61.445C758 597.855 475.2-186.845-31.2 54.455"></path>
                    <path strokeWidth="1.774" d="M1604.5-77.645c-862.3 679.4-1154.7-122.6-1641.8 103.1"></path>
                    <path strokeWidth="1.71" d="M1604.2-93.845c-877.7 699.5-1179.8-119.7-1647.5 90.4"></path>
                    <path strokeWidth="1.645" d="M1604-110.045c-893.3 719.6-1205-116.8-1653.4 77.7"></path>
                    <path strokeWidth="1.581" d="M1603.7-126.245c-908.8 739.7-1230.1-114-1659.2 65"></path>
                    <path strokeWidth="1.516" d="M1603.4-142.345c-924.3 759.7-1255.2-111.2-1665 52.2"></path>
                    <path strokeWidth="1.452" d="M1603.2-158.545c-939.9 779.8-1280.4-108.3-1670.9 39.4"></path>
                    <path strokeWidth="1.387" d="M1602.9-174.745c-955.3 799.9-1305.5-105.5-1676.7 26.7"></path>
                    <path strokeWidth="1.323" d="M1602.7-190.945c-970.9 820-1330.7-102.6-1682.6 14"></path>
                    <path strokeWidth="1.258" d="M1602.4-207.145c-986.4 840.1-1355.8-99.8-1688.3 1.3"></path>
                    <path strokeWidth="1.194" d="M1602.1-223.345c-1001.9 860.2-1380.9-96.9-1694.1-11.5"></path>
                    <path strokeWidth="1.129" d="M1601.9-239.545c-1017.5 880.3-1406.1-94-1700-24.2"></path>
                    <path strokeWidth="1.064" d="M1601.6-255.745c-1032.9 900.4-1431.2-91.2-1705.8-36.9"></path>
                    <path d="M1601.4-271.945c-1048.5 920.5-1456.4-88.3-1711.7-49.6"></path>
                </g>
            </g>
        </svg>
    );
}
