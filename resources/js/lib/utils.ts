import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export type UrlLike = string | { url: string };

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(url1: UrlLike, url2: UrlLike) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: UrlLike): string {
    return typeof url === 'string' ? url : url.url;
}
