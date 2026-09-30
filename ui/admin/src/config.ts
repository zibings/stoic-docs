export interface AdminConfig {
	environment: string;
	api: { baseUrl: string };
	/** Public site origin for "view on site" links. */
	siteUrl: string;
}

export async function loadConfig(): Promise<AdminConfig> {
	const defaults: AdminConfig = { environment: "development", api: { baseUrl: "/api/" }, siteUrl: "" };

	try {
		const response = await fetch(`${import.meta.env.BASE_URL}config.json`);

		if (!response.ok) {
			return defaults;
		}

		const raw = (await response.json()) as Partial<AdminConfig> & { api?: Partial<AdminConfig["api"]> };

		return {
			environment: raw.environment ?? defaults.environment,
			api: { baseUrl: raw.api?.baseUrl ?? defaults.api.baseUrl },
			siteUrl: (raw.siteUrl ?? "").replace(/\/+$/, ""),
		};
	} catch {
		return defaults;
	}
}
