import * as React from "react";

function SvgComponent(props: React.SVGProps<SVGSVGElement>) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            xmlnsXlink="http://www.w3.org/1999/xlink"
            width={310}
            height={225}
            viewBox="0 0 310 225"
            {...props}
        >
            <defs>
                <filter
                    id="prefix__a"
                    width="103.2%"
                    height="104.4%"
                    x="-1.6%"
                    y="-1.8%"
                    filterUnits="objectBoundingBox"
                >
                    <feOffset dy={1} in="SourceAlpha" result="shadowOffsetOuter1" />
                    <feGaussianBlur in="shadowOffsetOuter1" result="shadowBlurOuter1" stdDeviation={1.5} />
                    <feColorMatrix
                        in="shadowBlurOuter1"
                        values="0 0 0 0 0.333333333 0 0 0 0 0.352941176 0 0 0 0 0.384313725 0 0 0 0.304660184 0"
                    />
                </filter>
                <rect id="prefix__b" width={310} height={225} x={0} y={0} rx={2} />
            </defs>
            <g fill="none" fillRule="evenodd">
                <g>
                    <use fill="#000" filter="url(#prefix__a)" xlinkHref="#prefix__b" />
                    <use fill="#FFF" xlinkHref="#prefix__b" />
                </g>
                <path fill="#52A1D7" d="M0 13h310v61H0z" />
                <path fill="#034C81" d="M0 13h310v10H0z" />
                <path
                    fill="#FFF"
                    d="M49 17h16v2H49zm24 0h16v2H73zm27 24h110v4H100zM83 57.283c0-.709.577-1.283 1.29-1.283h119.12v8.57H84.29c-.711 0-1.29-.576-1.29-1.283v-6.004z"
                />
                <path
                    fill="#000"
                    fillOpacity={0.1}
                    stroke="#FFF"
                    strokeWidth={0.3}
                    d="M225.684 56.15a1.132 1.132 0 011.135 1.135h0v6a1.131 1.131 0 01-1.135 1.135h0-22.341v-8.27h22.34z"
                />
                <path fill="#FFF" d="M208 59.5h14v2h-14zM26 17h16v2H26z" />
                <g transform="translate(26 91)">
                    <path
                        fill="#ADB2BB"
                        d="M1 31h22v4H1zm0-15h26v4H1zM1 0h60v8H1zm72 16h26v4H73zm80 0h26v4h-26zm-25 0h13v4h-13zm89 57h22v4h-22zm0 25h22v4h-22zM17 39h42v2H17zm70 0h30v2H87zm43 0h10v2h-10zm0 25h10v2h-10zm0 25h10v2h-10zm0 25h10v2h-10zm-43 0h30v2H87zm0-6h26v2H87zm0-44h30v2H87zm0-31h20v2H87zm0 25h20v2H87zm0 31h30v2H87zm0-6h20v2H87zm130-67h37v2h-37zm0 6h43v2h-43zm0 6h25v2h-25zm0 6h37v2h-37zm0 6h49v2h-49zm0 6h33v2h-33zm0 6h32v2h-32zm0 6h38v2h-38zm0 6h32v2h-32z"
                    />
                    <circle cx={78} cy={61} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <circle cx={78} cy={86} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <circle cx={78} cy={111} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <circle cx={78} cy={36} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <path fill="#ADB2BB" d="M167 39h22v2h-22zm0-6h15v2h-15z" />
                    <circle cx={158} cy={36} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <path fill="#ADB2BB" d="M167 64h22v2h-22zm0-6h15v2h-15z" />
                    <circle cx={158} cy={61} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <path fill="#ADB2BB" d="M167 89h22v2h-22zm0-6h15v2h-15z" />
                    <circle cx={158} cy={86} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <path fill="#ADB2BB" d="M167 114h22v2h-22zm0-6h15v2h-15z" />
                    <circle cx={158} cy={111} r={5} stroke="#034C81" strokeWidth={0.8} />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 23.5h191" />
                    <path fill="#ADB2BB" d="M1 106h22v4H1zm17 8h43v2H18z" />
                    <circle cx={220} cy={109} r={3} stroke="#034C81" strokeWidth={0.6} />
                    <circle cx={230} cy={109} r={3} stroke="#034C81" strokeWidth={0.6} />
                    <circle cx={240} cy={109} r={3} stroke="#034C81" strokeWidth={0.6} />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 98.5h191" />
                    <path fill="#ADB2BB" d="M1 56h26v4H1zm0 8h40v2H1zm44 0h15v2H45z" />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 48.5h191" />
                    <path fill="#ADB2BB" d="M1 81h21v4H1zm0 8h66v2H1z" />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 73.5h191" />
                    <rect width={50} height={8} x={216} fill="#034C81" rx={2} />
                    <rect width={13} height={4} x={217} y={81} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={13} height={4} x={1} y={38} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <g stroke="#979797" transform="translate(134.5)">
                        <rect width={56} height={8} x={0.5} strokeWidth={0.4} rx={1} />
                        <path
                            strokeLinecap="square"
                            strokeWidth={0.5}
                            d="M10.5.2v7.7m12-7.7v7.7m12-7.7v7.7m12-7.7v7.7"
                        />
                    </g>
                    <rect width={13} height={4} x={1} y={113} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={13} height={4} x={232} y={81} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={11} height={4} x={247} y={81} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={11} height={4} x={238} y={87} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={19} height={4} x={217} y={87} stroke="#979797" strokeWidth={0.4} rx={1} />
                </g>
            </g>
        </svg>
    );
}

export default SvgComponent;
