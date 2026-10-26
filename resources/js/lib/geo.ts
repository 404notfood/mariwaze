export function normalizeLongitude(longitude: number): number {
    return ((longitude + 180) % 360 + 360) % 360 - 180;
}

export function unwrapPath(points: { latitude: number; longitude: number }[]): [number, number][] {
    let previous = points[0]?.longitude ?? 0;

    return points.map((point) => {
        const longitude = point.longitude + 360 * Math.round((previous - point.longitude) / 360);
        previous = longitude;

        return [point.latitude, longitude];
    });
}
