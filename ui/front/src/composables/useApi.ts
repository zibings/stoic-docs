import axios from "axios";
import type { AxiosInstance } from "axios";

let api: AxiosInstance | null = null;

export function createApi(baseUrl: string | null): AxiosInstance {
	api = axios.create({
		baseURL: baseUrl ?? "",
		withCredentials: true,
	});

	return api;
}

export function useApi(): AxiosInstance {
	if (!api) {
		api = createApi(null);
	}

	return api;
}
