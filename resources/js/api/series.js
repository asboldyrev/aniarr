import apiClient from './client'

export async function getSeriesList() {
    const response = await apiClient.get('/series')

    return response.data.data ?? []
}

export async function getSeries(id) {
    const response = await apiClient.get(`/series/${id}`)

    return response.data.data ?? null
}

export async function createSeries(payload) {
    const response = await apiClient.post('/series', payload)

    return response.data?.data ?? response.data ?? null
}

export async function updateSeriesMonitoring(id, monitored) {
    const response = await apiClient.patch(`/series/${id}/monitoring`, { monitored })

    return response.data?.data ?? response.data ?? null
}
