import apiClient from '@/api/client'

export async function searchTvdbSeries(query) {
    const response = await apiClient.get('/tvdb/series/search', {
        params: { query },
    })

    return Array.isArray(response.data?.data) ? response.data.data : []
}
