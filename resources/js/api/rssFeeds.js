import apiClient from './client'

export async function updateRssFeed(id, payload) {
    const response = await apiClient.patch(`/rss-feeds/${id}`, payload)

    return response.data?.data ?? response.data ?? null
}

export async function deleteRssFeed(id) {
    await apiClient.delete(`/rss-feeds/${id}`)
}
