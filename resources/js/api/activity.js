import apiClient from '@/api/client'

export async function getActivity(params = {}) {
    const response = await apiClient.get('/activity', { params })
    const payload = response.data ?? {}

    return {
        items: Array.isArray(payload.data) ? payload.data : [],
        meta: payload.meta ?? {},
    }
}
