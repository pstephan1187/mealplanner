import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export type ResourceProp<T extends object> = T | { data: T };

export function resolveResource<T extends object>(
    resource: ResourceProp<T>,
): T {
    if (resource && typeof resource === 'object' && 'data' in resource) {
        const wrapped = resource as { data?: T };

        if (wrapped.data) {
            return wrapped.data;
        }
    }

    return resource as T;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

/**
 * Extract the XSRF-TOKEN value from document.cookie.
 */
export function getXsrfToken(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

/**
 * Fetch wrapper that automatically sets JSON headers and the X-XSRF-TOKEN cookie.
 * Returns the raw Response so callers can handle redirects, status codes, etc.
 */
export async function apiFetch(
    url: string,
    options: RequestInit = {},
): Promise<Response> {
    const headers = new Headers(options.headers);

    if (!headers.has('Content-Type')) {
        headers.set('Content-Type', 'application/json');
    }
    if (!headers.has('Accept')) {
        headers.set('Accept', 'application/json');
    }
    headers.set('X-XSRF-TOKEN', getXsrfToken());

    return fetch(url, {
        ...options,
        headers,
        credentials: options.credentials ?? 'same-origin',
    });
}

/**
 * Format a date string to a short readable format (e.g., "Jan 15").
 * Returns empty string for null/undefined, or the original value if unparseable.
 */
export function formatDateShort(value?: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}
