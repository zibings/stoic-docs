<template>
	<div class="page">
		<div class="page__head">
			<div>
				<h1 class="page__title">Users</h1>
				<p class="page__subtitle">Accounts on this install. Authoring requires the Administrator role; grant it with <span class="mono">php scripts/add-user.php --make-admin</span> or the ZSF role endpoints.</p>
			</div>
		</div>

		<ResourceTable title="Users" :rows="users" :columns="columns" :loading="loading" :editable="false" :deletable="false" sort-field="email" />
	</div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";

import { adminApi } from "api/admin";
import ResourceTable from "components/ResourceTable.vue";
import type { ColumnDef } from "components/ResourceTable.vue";

const users = ref<{ id: number; email: string; emailConfirmed: boolean; joined: string; lastLogin: string | null }[]>([]);
const loading = ref(false);

const columns: ColumnDef[] = [
	{ field: "id", header: "ID", kind: "mono", width: "6%" },
	{ field: "email", header: "Email" },
	{ field: "emailConfirmed", header: "Confirmed", kind: "bool", width: "10%" },
	{ field: "joined", header: "Joined", width: "18%" },
	{ field: "lastLogin", header: "Last login", width: "18%" },
];

onMounted(async () => {
	loading.value = true;

	try {
		users.value = await adminApi.users();
	} finally {
		loading.value = false;
	}
});
</script>
