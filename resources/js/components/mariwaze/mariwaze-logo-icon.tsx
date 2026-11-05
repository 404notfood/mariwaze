import type { SVGAttributes } from 'react';

/** Marque Passerelle : une voile sur une ligne de houle, trait de 1,6. */
export default function MariwazeLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M3 17c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 3 2" />
            <path d="M12 3v9l6-1z" />
        </svg>
    );
}
