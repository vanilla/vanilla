import * as React from "react";

function BestOfPreviewThumbnail(props: React.SVGProps<SVGSVGElement>) {
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
                <use fill="#000" filter="url(#prefix__a)" xlinkHref="#prefix__b" />
                <use fill="#FFF" xlinkHref="#prefix__b" />
                <path fill="#0291DB" d="M0 13h310v10H0z" opacity={0.761} />
                <path fill="#FFF" d="M49 17h16v2H49zm24 0h16v2H73z" />
                <path fill="#CED1D6" d="M26 33h44v10H26z" />
                <path fill="#FFF" d="M26 17h16v2H26z" />
                <path fill="#ADB2BB" d="M31 63h48v6H31z" />
                <path
                    fill="#CED1D6"
                    d="M24 119h80v7a2 2 0 01-2 2H26a2 2 0 01-2-2v-7zm92 13h80v7a2 2 0 01-2 2h-76a2 2 0 01-2-2v-7zm91-25h80v7a2 2 0 01-2 2h-76a2 2 0 01-2-2v-7z"
                />
                <path
                    fill="#ADB2BB"
                    d="M31 108h60v3H31zm0-5h66v3H31zm0-5h60v3H31zm0-5h66v3H31zm0-5h64v3H31zm0-5h66v3H31zm0-5h66v3H31zm0-5h60v3H31zm92-10h48v6h-48zm0 15h66v3h-66zm0-5h60v3h-60zm91-10h48v6h-48z"
                />
                <path fill="#CED1D6" d="M214 86h66v16h-66z" />
                <path fill="#ADB2BB" d="M214 78h66v3h-66zm0-5h60v3h-60z" />
                <rect width={80} height={72} x={24} y={56} stroke="#D6D8DD" rx={2} />
                <path fill="#ADB2BB" d="M123 156h48v6h-48z" />
                <path fill="#CED1D6" d="M116 206h80v7a2 2 0 01-2 2h-76a2 2 0 01-2-2v-7z" />
                <path
                    fill="#ADB2BB"
                    d="M123 196h66v3h-66zm0-5h60v3h-60zm0-5h66v3h-66zm0-5h64v3h-64zm0-5h66v3h-66zm0-5h66v3h-66zm0-5h60v3h-60z"
                />
                <rect width={80} height={66} x={116} y={149} stroke="#D6D8DD" rx={2} />
                <rect width={80} height={85} x={116} y={56} stroke="#D6D8DD" rx={2} />
                <rect width={80} height={60} x={207} y={56} stroke="#D6D8DD" rx={2} />
                <g stroke="#ADB2BB" transform="translate(123 88)">
                    <rect width={66} height={41} rx={2} />
                    <circle cx={9} cy={10} r={4} />
                    <path d="M3 36V25.73h0l15.79-7.807 15.789 8.923L63 9v27a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </g>
                <path
                    fill="#CED1D6"
                    d="M207 200h80v7a2 2 0 01-2 2h-76a2 2 0 01-2-2v-7zm-183 4h80v7a2 2 0 01-2 2H26a2 2 0 01-2-2v-7z"
                />
                <path fill="#ADB2BB" d="M214 131h48v6h-48zM31 143h48v6H31zm183 3h66v3h-66zm0-5h60v3h-60z" />
                <rect width={80} height={85} x={207} y={124} stroke="#D6D8DD" rx={2} />
                <rect width={80} height={77} x={24} y={136} stroke="#D6D8DD" rx={2} />
                <g stroke="#ADB2BB" transform="translate(214 156)">
                    <rect width={66} height={41} rx={2} />
                    <circle cx={9} cy={10} r={4} />
                    <path d="M3 36V25.73h0l15.79-7.807 15.789 8.923L63 9v27a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </g>
                <g stroke="#ADB2BB" transform="translate(31 157)">
                    <rect width={66} height={41} rx={2} />
                    <circle cx={9} cy={10} r={4} />
                    <path d="M3 36V25.73h0l15.79-7.807 15.789 8.923L63 9v27a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </g>
            </g>
        </svg>
    );
}

export default BestOfPreviewThumbnail;
