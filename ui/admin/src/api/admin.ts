import { client } from "api/client";
import { RESOURCE_PATHS } from "types/admin";
import type { RecordTypes, Resource, SymbolLink } from "types/admin";
import type { SiteInfo } from "types/docs";

const base = "/1.1/Docs/Admin";

export function resourcePath(resource: Resource, id?: number): string {
	return id === undefined ? `${base}/${RESOURCE_PATHS[resource]}` : `${base}/${RESOURCE_PATHS[resource]}/${id}`;
}

export const adminApi = {
	async list<R extends Resource>(resource: R, filters: Record<string, string | number | null | undefined> = {}): Promise<RecordTypes[R][]> {
		const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== null && v !== undefined && v !== ""));

		return (await client().get<RecordTypes[R][]>(resourcePath(resource), { params })).data;
	},

	async get<R extends Resource>(resource: R, id: number): Promise<RecordTypes[R]> {
		return (await client().get<RecordTypes[R]>(resourcePath(resource, id))).data;
	},

	async create<R extends Resource>(resource: R, data: Partial<RecordTypes[R]>): Promise<RecordTypes[R]> {
		return (await client().post<RecordTypes[R]>(resourcePath(resource), data)).data;
	},

	async update<R extends Resource>(resource: R, id: number, data: Partial<RecordTypes[R]>): Promise<RecordTypes[R]> {
		return (await client().put<RecordTypes[R]>(resourcePath(resource, id), data)).data;
	},

	async remove(resource: Resource, id: number): Promise<void> {
		await client().delete(resourcePath(resource, id));
	},

	async reorder<R extends Resource>(resource: R, ids: number[]): Promise<RecordTypes[R][]> {
		return (await client().post<RecordTypes[R][]>(`${resourcePath(resource)}/Reorder`, { ids })).data;
	},

	async markLatest(versionId: number) {
		return (await client().post(`${resourcePath("versions", versionId)}/Latest`)).data;
	},

	async pageSymbols(pageId: number): Promise<SymbolLink[]> {
		return (await client().get<SymbolLink[]>(`${resourcePath("pages", pageId)}/Symbols`)).data;
	},

	async setPageSymbols(pageId: number, links: { symbolId?: number; ref?: string; role: string }[]): Promise<SymbolLink[]> {
		return (await client().put<SymbolLink[]>(`${resourcePath("pages", pageId)}/Symbols`, links)).data;
	},

	async changeSymbols(changeId: number): Promise<SymbolLink[]> {
		return (await client().get<SymbolLink[]>(`${resourcePath("changes", changeId)}/Symbols`)).data;
	},

	async setChangeSymbols(changeId: number, symbolIds: number[]): Promise<SymbolLink[]> {
		return (await client().put<SymbolLink[]>(`${resourcePath("changes", changeId)}/Symbols`, symbolIds)).data;
	},

	async importBundle(bundle: unknown): Promise<Record<string, number>> {
		return (await client().post<Record<string, number>>(`${base}/Import`, bundle)).data;
	},

	async exportBundle(): Promise<unknown> {
		return (await client().get(`${base}/Export`)).data;
	},

	async site(): Promise<SiteInfo> {
		return (await client().get<SiteInfo>("/1.1/Docs/Site")).data;
	},

	async login(email: string, key: string): Promise<void> {
		await client().post("/1.1/Account/Login", { email, key, provider: 1 });
	},

	async logout(): Promise<void> {
		await client().post("/1.1/Account/Logout");
	},

	async isAdministrator(): Promise<boolean> {
		const response = await client().post<boolean>("/1.1/Roles/UserInRole", { name: "Administrator" });

		return response.status === 200 && Boolean(response.data);
	},

	async users(): Promise<{ id: number; email: string; emailConfirmed: boolean; joined: string; lastLogin: string | null }[]> {
		return (await client().get("/1.1/Users")).data;
	},
};
