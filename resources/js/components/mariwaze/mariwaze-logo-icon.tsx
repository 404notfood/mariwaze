import type { SVGAttributes } from 'react';

export default function MariwazeLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <circle cx="24" cy="24" r="22" stroke="currentColor" strokeWidth="1.5" opacity="0.25" />
            <path
                d="M10 28C14 22 18 20 24 20C30 20 34 22 38 28"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
            />
            <path
                d="M12 32C16 26 20 24 24 24C28 24 32 26 36 32"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                opacity="0.6"
            />
            <circle cx="14" cy="18" r="3" fill="currentColor" />
            <path
                d="M14 18C18 16 22 15 28 16C32 17 36 19 38 22"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeDasharray="3 3"
            />
            <path
                d="M36 22L40 20L38 24"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
