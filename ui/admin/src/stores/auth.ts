import { defineStore } from "pinia";

import { adminApi } from "api/admin";
import { errorMessage } from "api/client";

export const useAuthStore = defineStore("auth", {
	state: () => ({
		checked: false,
		loggedIn: false,
		email: null as string | null,
	}),

	actions: {
		/** Verifies the session cookie grants the Administrator role. */
		async check(): Promise<boolean> {
			try {
				this.loggedIn = await adminApi.isAdministrator();
			} catch {
				this.loggedIn = false;
			}

			this.checked = true;

			return this.loggedIn;
		},

		async login(email: string, key: string): Promise<string | null> {
			try {
				await adminApi.login(email, key);
			} catch (error) {
				return errorMessage(error);
			}

			if (!(await this.check())) {
				await this.logout();

				return "This account cannot access authoring.";
			}

			this.email = email;

			return null;
		},

		async logout(): Promise<void> {
			try {
				await adminApi.logout();
			} catch {
				// Session may already be gone.
			}

			this.loggedIn = false;
			this.email = null;
		},
	},
});
