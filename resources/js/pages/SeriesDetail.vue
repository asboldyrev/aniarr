<template>
    <div v-if="series" class="p-4 md:p-6 space-y-6 overflow-x-hidden">

        <!-- Header -->
        <div class="flex items-start gap-4">
            <Button variant="ghost" size="icon" asChild>
                <RouterLink to="/">
                    <ArrowLeft class="h-4 w-4" />
                </RouterLink>
            </Button>

            <div class="flex flex-1 gap-6">
                <!-- Poster -->
                <div class="hidden sm:block h-40 w-28 shrink-0 overflow-hidden rounded-lg bg-muted">
                    <img v-if="series?.posterUrl" :src="series.posterUrl" :alt="series.title" class="h-full w-full object-cover" />
                    <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">TV</div>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold">{{ series.title }}</h1>
                            <p v-if="series.year" class="text-muted-foreground">{{ series.year }}</p>
                        </div>
                        <StatusBadge showIcon :status="series.status" />
                    </div>

                    <!-- Progress (if downloading) -->
                    <div v-if="hasDownloading" class="mt-4 max-w-xs">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Загрузка {{ series.status === 'downloading_avc' ? 'AVC' : 'HEVC' }}</span>
                            <span>{{ series.progress }}%</span>
                        </div>
                        <Progress class="relative h-4 w-full overflow-hidden rounded-full bg-secondary" v-model="series.progress" />
                    </div>

                    <!-- Error message -->
                    <div v-if="series.status === 'error' && series.errorMessage" class="mt-4 rounded-lg bg-destructive/10 border border-destructive/20 p-3">
                        <div class="flex gap-2 text-sm text-destructive">
                            <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
                            <span>{{ series.errorMessage }}</span>
                        </div>
                    </div>

                    <!-- Links -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs md:text-sm md:gap-2">
                            <RefreshCw class="h-3 w-3" />
                            <span class="hidden xs:inline">Проверить</span> RSS
                        </Button>
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs md:text-sm md:gap-2" asChild>
                            <a :href="series.rssUrl" target="_blank" rel="noopener noreferrer">
                                <Rss class="h-3 w-3" />
                                RSS
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs md:text-sm md:gap-2" asChild>
                            <a :href="`https://thetvdb.com/?id=${series.thetvdbId}&tab=series`" target="_blank" rel="noopener noreferrer">
                                <ExternalLink class="h-3 w-3" />
                                TheTVDB
                            </a>
                        </Button>
                    </div>

                    <!-- Format badges -->
                    <div class="mt-4 flex gap-2">
                        <Badge :variant="series.hasAvc ? 'default' : 'outline'" :class="series.hasAvc ? 'bg-blue-600' : ''">
                            AVC {{ series.hasAvc ? '✓' : '✗' }}
                        </Badge>
                        <Badge :variant="series.hasHevc ? 'default' : 'outline'" :class="series.hasHevc ? 'bg-purple-600' : ''">
                            HEVC {{ series.hasHevc ? '✓' : '✗' }}
                        </Badge>
                        <Badge :variant="series.sonarrConnected ? 'default' : 'outline'" :class="series.sonarrConnected ? 'bg-green-600' : ''">
                            Sonarr {{ series.sonarrConnected ? '✓' : '✗' }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Activity Log -->
            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="flex items-center gap-2 text-2xl font-semibold leading-none tracking-tight">
                        <Clock class="h-5 w-5" />
                        История действий
                    </CardTitle>
                    <CardDescription>Последние события по этому сериалу</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0">
                    <div v-if="activityLog?.length > 0" class="space-y-4">
                        <div v-for="entry in activityLog" :key="entry.id" class="flex gap-3">
                            <div class="mt-0.5">
                                <component :class="['h-4 w-4', getActivityIconClass(entry.type)]" :is="getActivityIcon(entry.type)" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm">{{ entry.message }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDistanceToNow(entry.timestamp, { addSuffix: true, locale: 'ru' }) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <EmptyState v-else :icon="Clock" title="История действий пуста" description="События появятся после первой проверки RSS" />
                </CardContent>
            </Card>

            <!-- Episodes List -->
            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="flex items-center gap-2 text-2xl font-semibold leading-none tracking-tight">
                        <Film class="h-5 w-5" />
                        Загруженные серии
                    </CardTitle>
                    <CardDescription>Список серий с информацией о форматах</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0">
                    <div v-if="episodes.length" class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="whitespace-nowrap">Серия</TableHead>
                                    <TableHead>AVC</TableHead>
                                    <TableHead>HEVC</TableHead>
                                    <TableHead class="whitespace-nowrap">Дата</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="episode in episodes" :key="episode.id">
                                    <TableCell class="font-mono text-xs md:text-sm">
                                        {{ String(episode.seasonNumber).padStart(2, '0') }}E{{ String(episode.episodeNumber).padStart(2, '0') }}
                                    </TableCell>
                                    <TableCell>
                                        <CheckCircle v-if="episode.hasAvc" class="h-4 w-4 text-green-500" />
                                        <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                                    </TableCell>
                                    <TableCell>
                                        <CheckCircle v-if="episode.hasHevc" class="h-4 w-4 text-green-500" />
                                        <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                                    </TableCell>
                                    <TableCell class="text-muted-foreground text-xs md:text-sm whitespace-nowrap">
                                        {{ episode.downloadedAt ? format(episode.downloadedAt, 'dd.MM.yyyy', { locale: 'ru' }) : '—' }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <EmptyState v-else :icon="Film" title="Серии ещё не загружены" description="Ожидание обновлений в RSS-ленте" />
                </CardContent>
            </Card>
        </div>

    </div>
    <div v-else class="flex h-full items-center justify-center p-8">
        <EmptyState :icon="AlertCircle" title="Сериал не найден" description="Запрашиваемый сериал не существует или был удалён" action="" />
    </div>
</template>

<script setup>
    import EmptyState from '@/components/EmptyState.vue';
    import StatusBadge from '@/components/StatusBadge.vue';
    import Progress from '@/components/ui/progress/Progress.vue';
    import Button from '@/components/ui/button/Button.vue';
    import Badge from '@/components/ui/badge/Badge.vue';
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
    import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';

    import { AlertCircle, ArrowLeft, Rss, ExternalLink, RefreshCw, Clock, CheckCircle, AlertTriangle, XCircle, Info, Film } from '@lucide/vue';

    import { computed, ref } from 'vue';

    const series = ref({
        id: '2',
        title: 'Дом Дракона',
        thetvdbId: 371572,
        rssUrl: 'https://example.com/rss/house-of-dragon',
        // posterUrl: 'https://artworks.thetvdb.com/banners/v4/series/371572/posters/628008e9ddd33.jpg',
        year: 2022,
        status: 'downloading_hevc',
        progress: 67,
        hasAvc: true,
        hasHevc: false,
        lastEpisodes: 'S02E04',
        lastUpdated: new Date(Date.now() - 30 * 60 * 1000),
        sonarrConnected: true,
    })

    const activityLog = ref([
        {
            id: 'a1',
            seriesId: '2',
            timestamp: new Date(Date.now() - 30 * 60 * 1000),
            message: 'Начата загрузка HEVC-версии: House.of.the.Dragon.S02E04.HEVC.torrent',
            type: 'info',
        },
        {
            id: 'a2',
            seriesId: '2',
            timestamp: new Date(Date.now() - 2 * 60 * 60 * 1000),
            message: 'Sonarr: серии S02E04 добавлены в библиотеку',
            type: 'success',
        },
        {
            id: 'a3',
            seriesId: '2',
            timestamp: new Date(Date.now() - 2.5 * 60 * 60 * 1000),
            message: 'Загрузка AVC завершена: 4.2 GB за 45 мин',
            type: 'success',
        },
        {
            id: 'a4',
            seriesId: '2',
            timestamp: new Date(Date.now() - 3 * 60 * 60 * 1000),
            message: 'Начата загрузка: House.of.the.Dragon.S02E04.AVC.torrent',
            type: 'info',
        },
    ])

    const episodes = ref([
        { id: 'e1', seriesId: '2', seasonNumber: 2, episodeNumber: 1, title: 'A Son for a Son', hasAvc: true, hasHevc: true, downloadedAt: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000) },
        { id: 'e2', seriesId: '2', seasonNumber: 2, episodeNumber: 2, title: 'Rhaenyra the Cruel', hasAvc: true, hasHevc: true, downloadedAt: new Date(Date.now() - 23 * 24 * 60 * 60 * 1000) },
        { id: 'e3', seriesId: '2', seasonNumber: 2, episodeNumber: 3, title: 'The Burning Mill', hasAvc: true, hasHevc: true, downloadedAt: new Date(Date.now() - 16 * 24 * 60 * 60 * 1000) },
        { id: 'e4', seriesId: '2', seasonNumber: 2, episodeNumber: 4, title: 'The Red Dragon and the Gold', hasAvc: true, hasHevc: false, downloadedAt: new Date(Date.now() - 2 * 60 * 60 * 1000) },
    ])

    function getActivityIcon(type) {
        switch (type) {
            case 'success': return CheckCircle;
            case 'warning': return AlertTriangle;
            case 'error': return XCircle;
            default: return Info;
        }
    }

    function getActivityIconClass(type) {
        switch (type) {
            case 'success': return "text-green-500";
            case 'warning': return "text-yellow-500";
            case 'error': return "text-red-500";
            default: return "text-blue-500";
        }
    }

    // TODO доделать реализацию
    function formatDistanceToNow(timestamp, params) {
        return 'около 1 часа назад'
    }

    // TODO доделать реализацию
    function format() {
        return '18.04.2026'
    }

    const hasDownloading = computed(() => (series.value.status === 'downloading_avc' || series.value.status === 'downloading_hevc') && series.value.progress !== undefined)

</script>

<style lang="scss" scoped></style>
