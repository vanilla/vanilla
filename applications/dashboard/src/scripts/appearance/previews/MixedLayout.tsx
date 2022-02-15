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
                <path fill="#F5296D" d="M0 13h310v10H0z" />
                <path fill="#FFF" d="M49 17h16v2H49zm24 0h16v2H73z" />
                <path fill="#CED1D6" d="M25 33h194v23H25z" />
                <path
                    fill="#FFF"
                    d="M53 41.283c0-.709.577-1.283 1.29-1.283h119.12v8.57H54.29c-.712 0-1.29-.576-1.29-1.283v-6.004z"
                />
                <path
                    fill="#000"
                    fillOpacity={0.1}
                    stroke="#FFF"
                    strokeWidth={0.3}
                    d="M195.684 40.15a1.132 1.132 0 011.135 1.135h0v6a1.131 1.131 0 01-1.135 1.135h0-22.341v-8.27h22.34z"
                />
                <path fill="#FFF" d="M178 43.5h14v2h-14zM26 17h16v2H26z" />
                <g transform="translate(26 33)">
                    <path fill="#ADB2BB" d="M15 65h22v4H15z" />
                    <path fill="#ADB2BB" d="M0 51h192v8H0z" opacity={0.604} />
                    <path
                        fill="#ADB2BB"
                        d="M0 34h60v8H0zm217 39h22v4h-22zm0 25h22v4h-22zM31 73h80v2H31zm186-57h37v2h-37zm0 6h43v2h-43zm0 6h25v2h-25zm0 6h37v2h-37zm0 6h49v2h-49zm0 6h33v2h-33zm0 6h32v2h-32zm0 6h38v2h-38zm0 6h32v2h-32z"
                    />
                    <circle cx={6} cy={93} r={5} stroke="#F86395" strokeWidth={0.8} />
                    <circle cx={6} cy={116} r={5} stroke="#F86395" strokeWidth={0.8} />
                    <circle cx={6} cy={70} r={5} stroke="#F86395" strokeWidth={0.8} />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 58.5h191" />
                    <circle cx={220} cy={109} r={3} stroke="#F86395" strokeWidth={0.6} />
                    <circle cx={230} cy={109} r={3} stroke="#F86395" strokeWidth={0.6} />
                    <circle cx={240} cy={109} r={3} stroke="#F86395" strokeWidth={0.6} />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 126.5h191" />
                    <path fill="#ADB2BB" d="M15 88h26v4H15zm0 8h40v2H15zm44 0h80v2H59z" />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 80.5h191" />
                    <path fill="#ADB2BB" d="M15 111h21v4H15zm0 8h80v2H15z" />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 103.5h191" />
                    <rect width={50} height={8} x={216} fill="#F5296D" rx={2} />
                    <rect width={13} height={4} x={217} y={81} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <rect width={13} height={4} x={15} y={72} stroke="#979797" strokeWidth={0.4} rx={1} />
                    <path fill="#ADB2BB" d="M15 153h22v4H15z" />
                    <path fill="#ADB2BB" d="M0 139h192v8H0z" opacity={0.604} />
                    <path fill="#ADB2BB" d="M31 161h80v2H31z" />
                    <circle cx={6} cy={181} r={5} stroke="#F86395" strokeWidth={0.8} />
                    <circle cx={6} cy={158} r={5} stroke="#F86395" strokeWidth={0.8} />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 146.5h191" />
                    <path fill="#ADB2BB" d="M15 176h26v4H15zm0 8h40v2H15zm44 0h80v2H59z" />
                    <path stroke="#ADB2BB" strokeLinecap="square" strokeWidth={0.5} d="M.5 168.5h191" />
                    <rect width={13} height={4} x={15} y={160} stroke="#979797" strokeWidth={0.4} rx={1} />
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
