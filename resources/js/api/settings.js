import apiClient from '@/api/client'

export async function getSettings() {
    const response = await apiClient.get('/settings')

    return response.data?.data ?? {}
}

export async function updateSettings(payload) {
    const response = await apiClient.put('/settings', payload)

    return response.data?.data ?? {}
}

export async function testSettingsConnection(service) {
    const response = await apiClient.post(`/settings/test/${service}`)

    return response.data?.data ?? null
}
