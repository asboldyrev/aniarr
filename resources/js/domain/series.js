const ACTIVE_DOWNLOAD_STATUSES = new Set([
    'pending',
    'preparing',
    'downloading',
    'importing',
])

export function getSeriesEpisodes(series) {
    return (series?.seasons ?? []).flatMap((season) => season.episodes ?? [])
}

export function getSeriesDownloads(series) {
    return (series?.seasons ?? []).flatMap((season) => season.downloads ?? [])
}

export function getActiveDownload(series) {
    return getSeriesDownloads(series)
        .filter((download) => ACTIVE_DOWNLOAD_STATUSES.has(download.status))
        .sort((left, right) => right.id - left.id)[0] ?? null
}

export function getSeriesUiStatus(series) {
    const activeDownload = getActiveDownload(series)
    if (activeDownload) {
        return activeDownload.status
    }

    return series?.monitored ? 'monitoring' : 'unmonitored'
}

export function hasCodec(series, codec) {
    return getSeriesEpisodes(series).some(
        (episode) => episode.hasFile && episode.fileCodec === codec,
    )
}

export function getLastEpisodeLabel(series) {
    let latest = null

    for (const season of series?.seasons ?? []) {
        for (const episode of season.episodes ?? []) {
            if (! episode.hasFile) {
                continue
            }

            if (
                latest === null
                || season.number > latest.seasonNumber
                || (
                    season.number === latest.seasonNumber
                    && episode.episodeNumber > latest.episodeNumber
                )
            ) {
                latest = {
                    seasonNumber: season.number,
                    episodeNumber: episode.episodeNumber,
                }
            }
        }
    }

    if (latest === null) {
        return null
    }

    return `S${String(latest.seasonNumber).padStart(2, '0')}E${String(latest.episodeNumber).padStart(2, '0')}`
}
