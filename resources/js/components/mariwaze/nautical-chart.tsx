import type {
    ChartCurrent,
    ChartData,
    ChartMarker,
    ChartMarkerType,
} from '@/lib/mariwaze-routes';
import { cn } from '@/lib/utils';

type Props = {
    data: ChartData;
    className?: string;
    highlightedMarker?: string;
    showCompass?: boolean;
    showDepths?: boolean;
    showRoute?: boolean;
    showCurrents?: boolean;
    interactive?: boolean;
    onMarkerClick?: (marker: ChartMarker) => void;
};

const markerColors: Record<ChartMarkerType, { fill: string; stroke: string }> =
    {
        port: {
            fill: 'var(--chart-marker-port)',
            stroke: 'var(--chart-marker-port-stroke)',
        },
        anchorage: {
            fill: 'var(--chart-marker-anchorage)',
            stroke: 'var(--chart-marker-anchorage-stroke)',
        },
        waypoint: {
            fill: 'var(--chart-marker-waypoint)',
            stroke: 'var(--chart-marker-waypoint-stroke)',
        },
        danger: {
            fill: 'var(--chart-marker-danger)',
            stroke: 'var(--chart-marker-danger-stroke)',
        },
    };

export default function NauticalChart({
    data,
    className,
    highlightedMarker,
    showCompass = true,
    showDepths = true,
    showRoute = true,
    showCurrents = false,
    interactive = false,
    onMarkerClick,
}: Props) {
    const { route, markers, currents = [] } = data;
    const routePath = route
        ?.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x * 8} ${p.y * 4}`)
        .join(' ');

    return (
        <div
            className={cn(
                'nautical-chart relative overflow-hidden rounded-2xl border border-border/60',
                className,
            )}
        >
            <svg
                viewBox="0 0 800 400"
                className="h-full w-full"
                role="img"
                aria-label="Carte marine stylisée"
            >
                <defs>
                    <linearGradient
                        id="chart-water"
                        x1="0%"
                        y1="0%"
                        x2="100%"
                        y2="100%"
                    >
                        <stop offset="0%" stopColor="var(--chart-water-1)" />
                        <stop offset="50%" stopColor="var(--chart-water-2)" />
                        <stop offset="100%" stopColor="var(--chart-water-3)" />
                    </linearGradient>
                    <linearGradient
                        id="chart-land"
                        x1="0%"
                        y1="0%"
                        x2="0%"
                        y2="100%"
                    >
                        <stop offset="0%" stopColor="var(--chart-land-1)" />
                        <stop offset="100%" stopColor="var(--chart-land-2)" />
                    </linearGradient>
                    <pattern
                        id="chart-grid"
                        width="40"
                        height="40"
                        patternUnits="userSpaceOnUse"
                    >
                        <path
                            d="M 40 0 L 0 0 0 40"
                            fill="none"
                            stroke="var(--chart-grid)"
                            strokeWidth="0.5"
                        />
                    </pattern>
                    <filter id="chart-glow">
                        <feGaussianBlur stdDeviation="2" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                    <filter id="chart-current-glow">
                        <feGaussianBlur stdDeviation="1.5" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>

                <rect width="800" height="400" fill="url(#chart-water)" />
                <rect
                    width="800"
                    height="400"
                    fill="url(#chart-grid)"
                    opacity="0.6"
                />

                {showDepths && (
                    <g
                        opacity="0.45"
                        stroke="var(--chart-depth)"
                        strokeWidth="1"
                        fill="none"
                    >
                        <path
                            d="M 0 200 Q 200 180 400 190 T 800 170"
                            strokeDasharray="4 6"
                        />
                        <path
                            d="M 0 260 Q 250 240 500 250 T 800 230"
                            strokeDasharray="4 6"
                        />
                        <path
                            d="M 0 320 Q 300 300 600 310 T 800 290"
                            strokeDasharray="4 6"
                        />
                        <text
                            x="20"
                            y="195"
                            fill="var(--chart-depth-label)"
                            fontSize="10"
                            fontFamily="Manrope, sans-serif"
                        >
                            20 m
                        </text>
                        <text
                            x="20"
                            y="255"
                            fill="var(--chart-depth-label)"
                            fontSize="10"
                            fontFamily="Manrope, sans-serif"
                        >
                            50 m
                        </text>
                        <text
                            x="20"
                            y="315"
                            fill="var(--chart-depth-label)"
                            fontSize="10"
                            fontFamily="Manrope, sans-serif"
                        >
                            100 m
                        </text>
                    </g>
                )}

                <g>
                    <path
                        d="M 520 0 L 800 0 L 800 180 Q 720 200 650 160 L 580 120 Q 540 80 520 40 Z"
                        fill="url(#chart-land)"
                        stroke="var(--chart-coastline)"
                        strokeWidth="1.5"
                    />
                    <path
                        d="M 600 200 Q 680 220 750 200 L 800 210 L 800 400 L 580 400 Q 620 350 640 300 Q 620 250 600 200 Z"
                        fill="url(#chart-land)"
                        stroke="var(--chart-coastline)"
                        strokeWidth="1.5"
                    />
                    <path
                        d="M 0 280 Q 80 260 120 290 L 180 320 Q 220 340 200 400 L 0 400 Z"
                        fill="url(#chart-land)"
                        stroke="var(--chart-coastline)"
                        strokeWidth="1.5"
                    />
                    <ellipse
                        cx="350"
                        cy="100"
                        rx="45"
                        ry="25"
                        fill="url(#chart-land)"
                        stroke="var(--chart-coastline)"
                        strokeWidth="1"
                        opacity="0.9"
                    />
                </g>

                {markers
                    .filter((m) => m.type === 'danger')
                    .map((marker) => (
                        <g
                            key={marker.id}
                            transform={`translate(${marker.x * 8}, ${marker.y * 4})`}
                        >
                            <circle
                                r="18"
                                fill="var(--chart-danger-zone)"
                                opacity="0.35"
                            />
                            <circle
                                r="18"
                                fill="none"
                                stroke="var(--chart-marker-danger-stroke)"
                                strokeWidth="1"
                                strokeDasharray="3 2"
                            />
                        </g>
                    ))}

                {showCurrents && currents.length > 0 && (
                    <g filter="url(#chart-current-glow)">
                        {currents.map((current) => (
                            <CurrentVector key={current.id} current={current} />
                        ))}
                    </g>
                )}

                {showRoute && routePath && (
                    <g filter="url(#chart-glow)">
                        <path
                            d={routePath}
                            fill="none"
                            stroke="var(--chart-route-glow)"
                            strokeWidth="6"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            opacity="0.4"
                        />
                        <path
                            d={routePath}
                            fill="none"
                            stroke="var(--chart-route)"
                            strokeWidth="2.5"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeDasharray="8 4"
                        />
                    </g>
                )}

                {markers.map((marker) => {
                    const colors = markerColors[marker.type];
                    const isHighlighted = highlightedMarker === marker.id;
                    const cx = marker.x * 8;
                    const cy = marker.y * 4;

                    return (
                        <g
                            key={marker.id}
                            transform={`translate(${cx}, ${cy})`}
                            className={cn(interactive && 'cursor-pointer')}
                            onClick={() =>
                                interactive && onMarkerClick?.(marker)
                            }
                        >
                            {isHighlighted && (
                                <circle
                                    r="16"
                                    fill="var(--chart-highlight)"
                                    opacity="0.3"
                                >
                                    <animate
                                        attributeName="r"
                                        values="12;18;12"
                                        dur="2s"
                                        repeatCount="indefinite"
                                    />
                                </circle>
                            )}
                            <circle
                                r={marker.type === 'danger' ? 6 : 8}
                                fill={colors.fill}
                                stroke={colors.stroke}
                                strokeWidth="2"
                            />
                            <text
                                x="12"
                                y="4"
                                fill="var(--chart-label)"
                                fontSize="11"
                                fontWeight="600"
                                fontFamily="Manrope, sans-serif"
                            >
                                {marker.label}
                            </text>
                        </g>
                    );
                })}

                {showCompass && (
                    <g transform="translate(720, 50)">
                        <circle
                            r="28"
                            fill="var(--chart-compass-bg)"
                            stroke="var(--chart-coastline)"
                            strokeWidth="1"
                            opacity="0.9"
                        />
                        <polygon
                            points="0,-18 4,0 0,18 -4,0"
                            fill="var(--chart-compass-north)"
                        />
                        <text
                            y="-22"
                            textAnchor="middle"
                            fill="var(--chart-label)"
                            fontSize="10"
                            fontWeight="700"
                            fontFamily="Manrope, sans-serif"
                        >
                            N
                        </text>
                    </g>
                )}

                <g transform="translate(30, 360)">
                    <line
                        x1="0"
                        y1="0"
                        x2="80"
                        y2="0"
                        stroke="var(--chart-label)"
                        strokeWidth="2"
                    />
                    <line
                        x1="0"
                        y1="-4"
                        x2="0"
                        y2="4"
                        stroke="var(--chart-label)"
                        strokeWidth="1.5"
                    />
                    <line
                        x1="80"
                        y1="-4"
                        x2="80"
                        y2="4"
                        stroke="var(--chart-label)"
                        strokeWidth="1.5"
                    />
                    <text
                        x="40"
                        y="14"
                        textAnchor="middle"
                        fill="var(--chart-label)"
                        fontSize="9"
                        fontFamily="Manrope, sans-serif"
                    >
                        50 M
                    </text>
                </g>
            </svg>

            <div className="absolute right-3 bottom-3 flex flex-wrap justify-end gap-x-2 gap-y-1 rounded-lg bg-[var(--chart-legend-bg)] px-3 py-2 text-[10px] backdrop-blur-sm">
                <LegendDot color="var(--chart-marker-port)" label="Port" />
                <LegendDot
                    color="var(--chart-marker-anchorage)"
                    label="Mouillage"
                />
                <LegendDot color="var(--chart-marker-danger)" label="Danger" />
                {showCurrents && currents.length > 0 && <LegendCurrent />}
            </div>
        </div>
    );
}

function CurrentVector({ current }: { current: ChartCurrent }) {
    const cx = current.x * 8;
    const cy = current.y * 4;
    const length = 26 + current.speed * 16;
    const strokeWidth = current.speed >= 1 ? 3 : 2;

    return (
        <g>
            <g
                transform={`translate(${cx}, ${cy}) rotate(${current.direction})`}
            >
                <line
                    x1={-length / 2}
                    y1="0"
                    x2={length / 2}
                    y2="0"
                    stroke="var(--chart-current)"
                    strokeWidth={strokeWidth}
                    strokeLinecap="round"
                    opacity="0.9"
                />
                <path
                    d={`M ${length / 2 - 8} -5 L ${length / 2} 0 L ${length / 2 - 8} 5`}
                    fill="none"
                    stroke="var(--chart-current-strong)"
                    strokeWidth={strokeWidth}
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </g>
            <text
                x={cx}
                y={cy + 18}
                textAnchor="middle"
                fill="var(--chart-current-label)"
                fontSize="9"
                fontWeight="700"
                fontFamily="Manrope, sans-serif"
            >
                {current.speed.toFixed(1)} nd {current.label}
            </text>
        </g>
    );
}

function LegendDot({ color, label }: { color: string; label: string }) {
    return (
        <span className="flex items-center gap-1 text-[var(--chart-label)]">
            <span
                className="size-2 rounded-full"
                style={{ backgroundColor: color }}
            />
            {label}
        </span>
    );
}

function LegendCurrent() {
    return (
        <span className="flex items-center gap-1 text-[var(--chart-label)]">
            <svg width="16" height="8" viewBox="0 0 16 8" aria-hidden="true">
                <path
                    d="M 1 4 H 13 M 10 1 L 13 4 L 10 7"
                    fill="none"
                    stroke="var(--chart-current)"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
            Courant
        </span>
    );
}
