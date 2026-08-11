import apiClient from '@/api/client'

export async function getDownloads(params = {}) {
    const response = await apiClient.get('/downloads', { params })
    const payload = response.data ?? {}

    return {
        items: Array.isArray(payload.data) ? payload.data : [],
        meta: payload.meta ?? {},
        links: payload.links ?? {},
    }
}

export async function getDownload(id) {
    const response = await apiClient.get(`/downloads/${id}`)

    return response.data?.data ?? response.data ?? null
}
