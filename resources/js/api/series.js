import apiClient from './client'

export async function getSeriesList() {
    const response = await apiClient.get('/series')

    return response.data.data ?? []
}

export async function getSeries(id) {
    const response = await apiClient.get(`/series/${id}`)

    return response.data.data ?? null
}
