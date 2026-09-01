<script setup lang="ts">
import { h } from "vue";
import { NuxtLink } from "#components";
import { createColumnHelper, type ColumnDef } from "@tanstack/vue-table";

definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Product {
    id: number;
    name: string;
    price: number;
    commission_rate: string;
    commission_type: string;
}
interface Creator {
    id: number;
    name: string;
    email: string;
}
interface Application {
    id: number;
    status: string;
    message: string | null;
    pitch_message: string;
    created_at: string;
    product: Product;
    creator: Creator;
    rejection_reason?: string;
}

const { $api } = useApi();
const route = useRoute();
const status = ref((route.query.status as string) || "");
const showRejectModal = ref(false);
const rejectAppId = ref<number | null>(null);
const rejectReason = ref("");
const confirmingId = ref<number | null>(null);

const { data: applications, refresh } = await useAsyncData(
    "brand-applications-list",
    () =>
        $api<{ data: Application[] }>("/brand/applications", {
            params: status.value ? { status: status.value } : {},
        }).catch(() => null),
);

const counts = computed(() => {
    const all = applications.value?.data || [];
    return {
        all: all.length,
        pending: all.filter((a) => a.status === "pending").length,
        approved: all.filter((a) => a.status === "approved").length,
        rejected: all.filter((a) => a.status === "rejected").length,
    };
});

const filters = [
    { key: "", label: "All" },
    { key: "pending", label: "Pending" },
    { key: "approved", label: "Approved" },
    { key: "rejected", label: "Rejected" },
];

function avatarColor(name: string): string {
    const colors = [
        "bg-red-400",
        "bg-blue-400",
        "bg-green-400",
        "bg-yellow-400",
        "bg-purple-400",
        "bg-pink-400",
        "bg-indigo-400",
        "bg-teal-400",
    ];
    let hash = 0;
    for (let i = 0; i < (name || "").length; i++)
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length]!;
}

async function approve(id: number) {
    await $api(`/brand/applications/${id}/approve`, { method: "PATCH" });
    confirmingId.value = null;
    await refresh();
}

function openReject(app: Application) {
    rejectAppId.value = app.id;
    rejectReason.value = "";
    showRejectModal.value = true;
}

async function confirmReject() {
    if (!rejectReason.value.trim()) return;
    await $api(`/brand/applications/${rejectAppId.value}/reject`, {
        method: "PATCH",
        body: { reason: rejectReason.value },
    });
    showRejectModal.value = false;
    rejectAppId.value = null;
    rejectReason.value = "";
    await refresh();
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    return (
        d.toLocaleDateString("en-MY", {
            day: "numeric",
            month: "short",
            year: "numeric",
        }) +
        ", " +
        d.toLocaleTimeString("en-MY", { hour: "2-digit", minute: "2-digit" })
    );
}

function formatCommission(a: Application): string {
    const rate = a.product.commission_rate;
    return a.product.commission_type === "percentage"
        ? `${rate}%`
        : `RM ${rate}`;
}

const statusClasses: Record<string, string> = {
    pending: "bg-yellow-50 text-yellow-700 border border-yellow-200",
    approved: "bg-green-50 text-green-700 border border-green-200",
    rejected: "bg-red-50 text-red-700 border border-red-200",
};

const columnHelper = createColumnHelper<Application>();

const columns: ColumnDef<Application, any>[] = [
    columnHelper.accessor((row) => row.creator?.name, {
        id: "creator",
        header: "Creator",
        cell: (info) => {
            const app = info.row.original;
            return h("div", { class: "flex items-center gap-3" }, [
                h(
                    "div",
                    {
                        class: `w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0 ${avatarColor(app.creator?.name || "")}`,
                    },
                    (app.creator?.name || "?").charAt(0).toUpperCase(),
                ),
                h("div", { class: "min-w-0" }, [
                    h(
                        NuxtLink,
                        {
                            to: `/brand/creators/${app.creator?.id}`,
                            class: "font-semibold text-gray-900 text-sm hover:underline block",
                        },
                        () => app.creator?.name,
                    ),
                    h(
                        "span",
                        { class: "text-gray-400 text-xs" },
                        app.creator?.email,
                    ),
                ]),
            ]);
        },
    }),
    columnHelper.accessor((row) => row.created_at, {
        id: "applied",
        header: "Applied",
        cell: (info) =>
            h(
                "span",
                { class: "text-xs text-gray-500 whitespace-nowrap" },
                formatDate(info.getValue()),
            ),
    }),
    columnHelper.accessor((row) => row.product?.name, {
        id: "product",
        header: "Product",
        cell: (info) => {
            const app = info.row.original;
            return h("div", [
                h(
                    "p",
                    { class: "font-medium text-gray-700 text-sm" },
                    app.product?.name,
                ),
                h(
                    "p",
                    { class: "text-xs text-gray-400 mt-0.5" },
                    `RM ${app.product?.price} · `,
                ),
                h(
                    "span",
                    { class: "text-xs text-green-600 font-medium" },
                    formatCommission(app),
                ),
            ]);
        },
    }),
    columnHelper.accessor("status", {
        header: "Status",
        cell: (info) =>
            h(
                "span",
                {
                    class: `text-xs px-2.5 py-0.5 rounded-full font-medium ${statusClasses[info.getValue()] || ""}`,
                },
                info.getValue(),
            ),
    }),
    columnHelper.accessor((row) => row.pitch_message, {
        id: "pitch",
        header: "Pitch",
        enableSorting: false,
        cell: (info) =>
            h("div", { class: "max-w-xs" }, [
                h(
                    "p",
                    { class: "text-sm text-gray-500 line-clamp-2" },
                    info.getValue(),
                ),
                info.row.original.status === "rejected" &&
                info.row.original.message
                    ? h(
                          "p",
                          { class: "text-xs text-red-500 mt-1" },
                          `Reason: ${info.row.original.message}`,
                      )
                    : null,
            ]),
    }),
    columnHelper.display({
        id: "actions",
        header: "",
        enableSorting: false,
        cell: (info) => {
            const app = info.row.original;
            if (app.status !== "pending") return null;

            if (confirmingId.value === app.id) {
                return h(
                    "div",
                    { class: "flex items-center gap-2 whitespace-nowrap" },
                    [
                        h(
                            "span",
                            { class: "text-xs text-gray-500" },
                            "Confirm?",
                        ),
                        h(
                            "button",
                            {
                                class: "bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
                                onClick: () => approve(app.id),
                            },
                            "Yes",
                        ),
                        h(
                            "button",
                            {
                                class: "text-gray-400 hover:text-gray-600 text-xs px-2 py-1.5",
                                onClick: () => {
                                    confirmingId.value = null;
                                },
                            },
                            "Cancel",
                        ),
                    ],
                );
            }

            return h(
                "div",
                { class: "flex items-center gap-2 whitespace-nowrap" },
                [
                    h(
                        "button",
                        {
                            class: "bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
                            onClick: () => {
                                confirmingId.value = app.id;
                            },
                        },
                        "Approve",
                    ),
                    h(
                        "button",
                        {
                            class: "border border-red-200 text-red-500 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors",
                            onClick: () => openReject(app),
                        },
                        "Reject",
                    ),
                ],
            );
        },
    }),
];
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Applications</h1>
            <p class="text-gray-500 mt-1">
                Review creators who want to promote your products
            </p>
        </div>

        <!-- Filter tabs with count badges -->
        <div class="flex gap-2 mb-6 flex-wrap">
            <button
                v-for="f in filters"
                :key="f.key"
                @click="
                    status = f.key;
                    refresh();
                "
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium transition-all"
                :class="
                    status === f.key
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 hover:border-gray-300'
                "
            >
                {{ f.label }}
                <span
                    v-if="counts[f.key as keyof typeof counts] > 0"
                    class="text-xs px-1.5 py-0.5 rounded-full"
                    :class="
                        status === f.key
                            ? 'bg-white/20'
                            : 'bg-gray-100 text-gray-500'
                    "
                >
                    {{ counts[f.key as keyof typeof counts] }}
                </span>
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-if="!applications?.data?.length"
            class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center"
        >
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="applications" class="text-blue-500" />
            </div>
            <p class="font-medium text-gray-600">
                No {{ status || "" }} applications
            </p>
            <p class="text-sm text-gray-400 mt-1">
                {{
                    status === "pending"
                        ? "No one has applied to your products yet."
                        : ""
                }}
                {{
                    status === "approved" ? "No approved applications yet." : ""
                }}
                {{ status === "rejected" ? "No rejected applications." : "" }}
                {{
                    !status
                        ? "Applications from creators will appear here."
                        : ""
                }}
            </p>
        </div>

        <!-- Applications table -->
        <DataTable v-else :columns="columns" :data="applications?.data || []" />

        <!-- Reject modal -->
        <Teleport to="body">
            <div
                v-if="showRejectModal"
                class="fixed inset-0 z-50 flex items-center justify-center"
            >
                <div
                    class="absolute inset-0 bg-black/30"
                    @click="showRejectModal = false"
                />
                <div
                    class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4"
                >
                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                        Reject Application
                    </h3>
                    <p class="text-sm text-gray-400 mb-4">
                        Let the creator know why their application was rejected.
                    </p>
                    <textarea
                        v-model="rejectReason"
                        rows="4"
                        placeholder="e.g. We are looking for creators with a different audience demographic..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                    />
                    <p class="text-xs text-gray-400 mt-1 text-right">
                        {{ rejectReason.length }}/500
                    </p>
                    <div class="flex gap-3 mt-4">
                        <button
                            @click="showRejectModal = false"
                            class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmReject"
                            :disabled="!rejectReason.trim()"
                            class="flex-1 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
