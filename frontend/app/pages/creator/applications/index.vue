<script setup lang="ts">
import { h } from 'vue'
import { NuxtLink } from '#components'
import { createColumnHelper, type ColumnDef } from '@tanstack/vue-table'

definePageMeta({
  middleware: 'creator',
  layout:     'creator',
})

interface Application
{ id: number;
  status: string;
  message: string;
  pitch_message: string;
  rejected_reason: string;
  product:
            { id: number;
              name: string;
              commission_rate:
              string;
              commission_type: string;
              category:
              string;
              price: number
            }
  }

const { $api } = useApi()
const route    = useRoute()
const status   = ref(route.query.status as string || '')

const { data: applications, refresh } = await useAsyncData(
  'creator-applications-list',
  () => $api<{ data: Application[] }>('/creator/applications', {
    params: status.value ? { status: status.value } : {}
  }).catch(() => null)
)

const filters = ['', 'pending', 'approved', 'rejected']

watch(status, () => refresh())

const statusClasses: Record<string, string> = {
  pending:  'bg-yellow-100 text-yellow-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
}

const columnHelper = createColumnHelper<Application>()

const columns: ColumnDef<Application, any>[] = [
  columnHelper.accessor(row => row.product?.name, {
    id: 'product',
    header: 'Product',
    cell: info => h('div', [
      h('p', { class: 'font-semibold text-gray-900' }, info.row.original.product?.name),
      h('p', { class: 'text-xs text-gray-400 mt-0.5' },
        `${info.row.original.product?.category} · RM ${info.row.original.product?.price}`),
    ]),
  }),
  columnHelper.accessor(row => Number(row.product?.commission_rate), {
    id: 'commission',
    header: 'Commission',
    cell: info => h('span', { class: 'text-green-600 font-medium' },
      `${info.row.original.product?.commission_rate}${info.row.original.product?.commission_type === 'percentage' ? '%' : ' RM'}`),
  }),
  columnHelper.accessor('status', {
    header: 'Status',
    cell: info => h('span', {
      class: `text-xs px-3 py-1 rounded-full font-medium ${statusClasses[info.getValue()] || ''}`,
    }, info.getValue()),
  }),
  columnHelper.accessor(row => row.pitch_message, {
    id: 'pitch',
    header: 'Pitch',
    enableSorting: false,
    cell: info => h('div', { class: 'max-w-xs' }, [
      h('p', { class: 'text-sm text-gray-500 line-clamp-2' }, info.getValue()),
      info.row.original.status === 'rejected' && info.row.original.rejected_reason
        ? h('p', { class: 'text-xs text-red-500 mt-1' }, `Reason: ${info.row.original.rejected_reason}`)
        : null,
    ]),
  }),
  columnHelper.display({
    id: 'action',
    header: '',
    cell: info => info.row.original.status === 'approved'
      ? h(NuxtLink, {
          to: '/creator/collaborations',
          class: 'inline-block bg-green-50 text-green-700 px-4 py-2 rounded-xl text-xs font-medium hover:bg-green-100 transition-colors',
        }, () => 'View Collab →')
      : null,
  }),
]
</script>

<template>
  <div>

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
      <p class="text-gray-500 mt-1">Track the status of your product applications</p>
    </div>

    <!-- Filter tabs -->
    <div class="flex gap-2 mb-6">
      <button
        v-for="f in filters"
        :key="f"
        @click="status = f"
        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
        :class="status === f
          ? 'bg-green-600 text-white'
          : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'"
      >
        {{ f === '' ? 'All' : f.charAt(0).toUpperCase() + f.slice(1) }}
      </button>
    </div>

    <!-- Empty state -->
    <div
      v-if="!applications?.data?.length"
      class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400"
    >
      <p class="text-4xl mb-3">📋</p>
      <p class="font-medium text-gray-600">No applications yet</p>
      <p class="text-sm mt-1">Browse the marketplace and apply to products you want to promote</p>
      <NuxtLink
        to="/creator/marketplace"
        class="inline-block mt-4 bg-green-500 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-green-600"
      >
        Browse Marketplace
      </NuxtLink>
    </div>

    <!-- Applications table -->
    <DataTable v-else :columns="columns" :data="applications?.data || []" />

  </div>
</template>
