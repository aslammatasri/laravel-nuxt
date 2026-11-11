<script setup lang="ts">
definePageMeta({ middleware: "brand", layout: "brand" });

interface Verification {
    company_name: string | null;
    industry: string | null;
    ssm_no: string | null;
    verification_status: "unverified" | "pending" | "verified" | "rejected";
    verification_note: string | null;
    submitted_at: string | null;
    verified_at: string | null;
}

const { $api } = useApi();

const { data, refresh } = await useAsyncData("brand-verification", () =>
    $api<Verification>("/brand/verification").catch(() => null),
);

const form = reactive({
    company_name: data.value?.company_name ?? "",
    industry: data.value?.industry ?? "",
    ssm_no: data.value?.ssm_no ?? "",
});
const saving = ref(false);
const error = ref<string | null>(null);
const saved = ref(false);

const status = computed(() => data.value?.verification_status ?? "unverified");

const banner = computed(() => {
    switch (status.value) {
        case "verified":
            return { cls: "bg-green-50 border-green-100 text-green-800", title: "Verified", text: "Your brand shows a verified badge to creators. Changing the company name or SSM number sends it back for review." };
        case "pending":
            return { cls: "bg-yellow-50 border-yellow-100 text-yellow-800", title: "Under review", text: "We are checking your details. You will get a notification once it is done." };
        case "rejected":
            return { cls: "bg-red-50 border-red-100 text-red-800", title: "Verification rejected", text: data.value?.verification_note ? `Reason: ${data.value.verification_note}` : "Please check your details and resubmit." };
        default:
            return { cls: "bg-gray-50 border-gray-200 text-gray-700", title: "Not verified yet", text: "Submit your company details so creators can trust your brand." };
    }
});

async function submit() {
    saving.value = true;
    error.value = null;
    saved.value = false;
    try {
        await $api("/brand/verification", {
            method: "PUT",
            body: { ...form, industry: form.industry || null },
        });
        await refresh();
        saved.value = true;
    } catch (err: any) {
        error.value =
            err?.data?.errors?.ssm_no?.[0] ||
            err?.data?.errors?.company_name?.[0] ||
            err?.data?.message ||
            "Could not submit your details";
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Brand verification</h1>
            <p class="text-gray-500 mt-1">Verified brands earn creators' trust and get more applications.</p>
        </div>

        <div class="rounded-2xl border px-5 py-4 mb-6" :class="banner.cls" role="status">
            <p class="font-semibold">{{ banner.title }}</p>
            <p class="text-sm mt-0.5">{{ banner.text }}</p>
        </div>

        <form class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5" @submit.prevent="submit">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="company">Company name</label>
                <input id="company" v-model="form.company_name" required maxlength="255" placeholder="e.g. Skintific Sdn Bhd"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="ssm">SSM registration number</label>
                <input id="ssm" v-model="form.ssm_no" required maxlength="20" placeholder="e.g. 202001012345"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="industry">
                    Industry <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input id="industry" v-model="form.industry" maxlength="100" placeholder="e.g. Beauty"
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <p v-if="error" class="text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3" role="alert">{{ error }}</p>
            <p v-if="saved && !error" class="text-sm text-green-700">Submitted. We will review it shortly.</p>

            <button type="submit" :disabled="saving"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
                {{ saving ? "Submitting..." : status === "unverified" ? "Submit for verification" : "Update and resubmit" }}
            </button>
        </form>
    </div>
</template>
