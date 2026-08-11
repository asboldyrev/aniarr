import apiClient from '@/api/client'

function unwrapResource(payload) {
    return payload?.data ?? payload ?? null
}

export async function getActivity(params = {}) {
    const response = await apiClient.get('/activity', { params })
    const payload = response.data ?? {}

    return {
        items: Array.isArray(payload.data) ? payload.data : [],
        meta: payload.meta ?? {},
        links: payload.links ?? {},
    }
}

export async function resolveActivity(id) {
    const response = await apiClient.patch(`/activity/${id}/resolve`)

    return unwrapResource(response.data)
}

export async function reopenActivity(id) {
    const response = await apiClient.patch(`/activity/${id}/reopen`)

    return unwrapResource(response.data)
}
