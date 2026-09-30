// Runtime configuration lives in public/config.json (copied from docker/front-config.json by the dev loop) so one
// build can point at different API hosts. In the browser it is fetched; during pre-rendering it is read from disk.

export interface SiteConfig {
	environment: "development" | "staging" | "production";
	api: {
		baseUrl: string;
	};
}

const defaults: SiteConfig = {
	environment: "development",
	api: { baseUrl: "/api/" },
};

function normalize(raw: unknown): SiteConfig {
	const source = (raw ?? {}) as Partial<SiteConfig> & { api?: Partial<SiteConfig["api"]> };

	return {
		environment: source.environment ?? defaults.environment,
		api: {
			baseUrl: source.api?.baseUrl ?? defaults.api.baseUrl,
		},
	};
}

export async function loadConfig(): Promise<SiteConfig> {
	if (import.meta.env.SSR) {
		const proc = (globalThis as { process?: { cwd(): string; env: Record<string, string | undefined> } }).process;
		const fs = await import(/* @vite-ignore */ "node:" + "fs");
		const path = await import(/* @vite-ignore */ "node:" + "path");
		const file = path.resolve(proc?.cwd() ?? ".", "public/config.json");
		const raw = fs.existsSync(file) ? JSON.parse(fs.readFileSync(file, "utf8")) : {};
		const config = normalize(raw);
		const override = proc?.env.DOCS_API_BASE_URL;

		if (override) {
			config.api.baseUrl = override;
		}

		return config;
	}

	try {
		const response = await fetch(`${import.meta.env.BASE_URL}config.json`);

		return normalize(response.ok ? await response.json() : {});
	} catch {
		return normalize({});
	}
}
