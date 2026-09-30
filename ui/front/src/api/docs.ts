// Typed client for the public /Docs endpoints. Every call goes through the shared axios instance whose base URL
// comes from public/config.json, so the same code runs in the browser and during pre-rendering.
import { useApi } from "composables/useApi";
import type { DiffResponse, Manifest, PageMode, PageResponse, SiteInfo, SymbolResponse, TreeResponse } from "types/docs";

const prefix = "/1.1/Docs";

async function get<T>(path: string): Promise<T> {
	const response = await useApi().get<T>(`${prefix}${path}`);

	return response.data;
}

export const docsApi = {
	site: () => get<SiteInfo>("/Site"),

	tree: (version: string, includeRemoved = true) =>
		get<TreeResponse>(`/Tree/${encodeURIComponent(version)}${includeRemoved ? "" : "?removed=0"}`),

	symbol: (version: string, modulePath: string, name: string) =>
		get<SymbolResponse>(`/Symbol/${encodeURIComponent(version)}/${modulePath.split("/").map(encodeURIComponent).join("/")}/${encodeURIComponent(name)}`),

	page: (version: string, mode: PageMode, slug: string) =>
		get<PageResponse>(`/Page/${encodeURIComponent(version)}/${mode}/${slug.split("/").map(encodeURIComponent).join("/")}`),

	diff: (from: string, to: string) => get<DiffResponse>(`/Diff/${encodeURIComponent(from)}/${encodeURIComponent(to)}`),

	manifest: (version: string) => get<Manifest>(`/Manifest/${encodeURIComponent(version)}`),
};

/** True when an API error is a 404, so views can render "not at this version" instead of a generic failure. */
export function isNotFound(error: unknown): boolean {
	return axiosStatus(error) === 404;
}

export function axiosStatus(error: unknown): number | null {
	if (typeof error === "object" && error !== null && "response" in error) {
		const response = (error as { response?: { status?: number } }).response;

		return response?.status ?? null;
	}

	return null;
}
