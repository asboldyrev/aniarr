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

export function getSeriesEpisodeStats(series) {
    const episodes = getSeriesEpisodes(series)
    const files = episodes.filter((episode) => episode.hasFile)

    return {
        total: episodes.length,
        files: files.length,
        missing: Math.max(0, episodes.length - files.length),
    }
}

export function isSeriesIncomplete(series) {
    const stats = getSeriesEpisodeStats(series)

    return stats.total > 0 && stats.files < stats.total
}

export function hasCodec(series, codec) {
    return getSeriesEpisodes(series).some(
        (episode) => episode.hasFile && episode.fileCodec === codec,
    )
}

export function getSeriesCodecState(series) {
    const hasAvc = hasCodec(series, 'avc')
    const hasHevc = hasCodec(series, 'hevc')

    if (hasAvc && hasHevc) return 'mixed'
    if (hasHevc) return 'hevc'
    if (hasAvc) return 'avc'

    return 'none'
}

export function getSeriesRssState(series) {
    const feeds = (series?.seasons ?? [])
        .map((season) => season.rssFeed)
        .filter(Boolean)

    if (feeds.length === 0) {
        return 'missing'
    }

    if (! series?.monitored) {
        return 'paused'
    }

    const hasCurrentError = feeds.some((feed) => {
        if (! feed.lastErrorAt || ! feed.lastError) {
            return false
        }

        if (! feed.lastRssSuccessAt) {
            return true
        }

        return new Date(feed.lastErrorAt) >= new Date(feed.lastRssSuccessAt)
    })

    if (hasCurrentError) {
        return 'error'
    }

    if (feeds.some((feed) => feed.enabled)) {
        return 'healthy'
    }

    return 'disabled'
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
