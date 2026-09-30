<template>
	<div class="login">
		<form class="login__card card stack" @submit.prevent="submit">
			<div>
				<h1 class="page__title">Docs authoring</h1>
				<p class="muted">Sign in with an administrator account.</p>
			</div>
			<Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
			<FormField v-slot="{ id }" label="Email" required>
				<InputText :id="id" v-model="email" type="email" autocomplete="username" required fluid />
			</FormField>
			<FormField v-slot="{ id }" label="Password" required>
				<Password :id="id" v-model="password" :feedback="false" toggle-mask autocomplete="current-password" required fluid input-class="w-full" />
			</FormField>
			<Button type="submit" label="Sign in" :loading="busy" />
		</form>
	</div>
</template>

<script setup lang="ts">
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Password from "primevue/password";
import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";

import FormField from "components/FormField.vue";
import { useAuthStore } from "stores/auth";

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const email = ref("");
const password = ref("");
const error = ref<string | null>(null);
const busy = ref(false);

async function submit(): Promise<void> {
	busy.value = true;
	error.value = await auth.login(email.value, password.value);
	busy.value = false;

	if (!error.value) {
		const next = typeof route.query.next === "string" ? route.query.next : "/";

		router.push(next);
	}
}
</script>

<style>
.login {
	min-height: 100vh;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 24px;
}

.login__card {
	width: 100%;
	max-width: 420px;
	padding: 28px;
}

.login .p-password,
.login .p-password input {
	width: 100%;
}
</style>
