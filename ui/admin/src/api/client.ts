import axios from "axios";
import type { AxiosInstance } from "axios";

let instance: AxiosInstance | null = null;

export function createClient(baseUrl: string): AxiosInstance {
	instance = axios.create({ baseURL: baseUrl, withCredentials: true });

	return instance;
}

export function client(): AxiosInstance {
	if (!instance) {
		instance = createClient("/api/");
	}

	return instance;
}

/** The API returns a plain string message on errors; surface it, or the HTTP status text. */
export function errorMessage(error: unknown): string {
	if (typeof error === "object" && error !== null && "response" in error) {
		const response = (error as { response?: { data?: unknown; status?: number; statusText?: string } }).response;

		if (typeof response?.data === "string" && response.data) {
			return response.data;
		}

		return response?.statusText ? `${response.status} ${response.statusText}` : "Request failed";
	}

	return error instanceof Error ? error.message : String(error);
}

export function errorStatus(error: unknown): number | null {
	if (typeof error === "object" && error !== null && "response" in error) {
		return (error as { response?: { status?: number } }).response?.status ?? null;
	}

	return null;
}
