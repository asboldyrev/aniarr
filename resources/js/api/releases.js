import apiClient from '@/api/client'

export async function downloadRelease(releaseId, episodeIds = null) {
    const payload = episodeIds === null
        ? {}
        : { episode_ids: episodeIds }

    const response = await apiClient.post(`/releases/${releaseId}/download`, payload)

    return response.data?.data ?? response.data ?? null
}
