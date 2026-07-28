export type DashboardStep = {
    day: string;
    from: string;
    to: string;
    distance: string;
    status: 'done' | 'current' | 'upcoming';
};

export type DashboardChecklistItem = {
    label: string;
    done: boolean;
};

export type DashboardAnchorage = {
    slug: string;
    name: string;
    rating: number;
    tags: string[];
    photos: number;
};

export type DashboardAlert = {
    type: 'attention' | 'info' | 'action';
    message: string;
};

export type DashboardBriefing = {
    label: string;
    from: string;
    to: string;
    departure: string;
    arrival: string;
    wind: string;
    swell: string;
    summary: string;
    attention: string[];
};

export type DashboardData = {
    user: {
        name: string;
        boat: string;
    };
    cruise: {
        slug: string;
        title: string;
        route: string;
        departure: string;
        duration: string;
        status: string;
        progress: number;
        miles: number;
        stops: number;
        readiness: number;
    };
    weather: {
        summary: string;
        wind: string;
        swell: string;
        departure: string;
        confidence: number;
    };
    briefing: DashboardBriefing;
    steps: DashboardStep[];
    checklist: DashboardChecklistItem[];
    anchorages: DashboardAnchorage[];
    alerts: DashboardAlert[];
};
