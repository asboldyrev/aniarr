<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Добавить сериал</DialogTitle>
                <DialogDescription>
                    Найдите сериал в TheTVDB, укажите RSS-ленту и настройте автоматический мониторинг.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-5" @submit.prevent="handleSubmit">
                <div class="space-y-2">
                    <Label for="series-search">Сериал *</Label>

                    <div v-if="selectedSeries" class="flex items-start gap-3 rounded-lg border p-3">
                        <div class="h-20 w-14 shrink-0 overflow-hidden rounded bg-muted">
                            <img
                                v-if="selectedSeries.posterUrl"
                                :src="selectedSeries.posterUrl"
                                :alt="selectedSeries.title"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-muted-foreground">
                                TV
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ selectedSeries.title }}</p>
                            <p
                                v-if="selectedSeries.originalTitle && selectedSeries.originalTitle !== selectedSeries.title"
                                class="mt-0.5 text-sm text-muted-foreground"
                            >
                                {{ selectedSeries.originalTitle }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                <span v-if="selectedSeries.year">{{ selectedSeries.year }} · </span>
                                TheTVDB #{{ selectedSeries.thetvdbId }}
                            </p>
                        </div>

                        <Button type="button" variant="ghost" size="sm" @click="clearSelection">
                            Изменить
                        </Button>
                    </div>

                    <template v-else>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="series-search"
                                v-model="searchQuery"
                                class="pl-9 pr-9"
                                autocomplete="off"
                                placeholder="Начните вводить название..."
                            />
                            <Loader2
                                v-if="searching"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground"
                            />
                        </div>

                        <p v-if="searchQuery.length > 0 && searchQuery.length < 2" class="text-xs text-muted-foreground">
                            Введите минимум 2 символа.
                        </p>

                        <div
                            v-if="showSearchResults"
                            class="max-h-72 overflow-y-auto rounded-lg border bg-popover p-1 shadow-sm"
                        >
                            <button
                                v-for="result in searchResults"
                                :key="result.thetvdbId"
                                type="button"
                                class="flex w-full items-start gap-3 rounded-md p-2 text-left transition-colors hover:bg-muted"
                                @click="selectSeries(result)"
                            >
                                <div class="h-16 w-11 shrink-0 overflow-hidden rounded bg-muted">
                                    <img
                                        v-if="result.posterUrl"
                                        :src="result.posterUrl"
                                        :alt="result.title"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center text-[10px] text-muted-foreground">
                                        TV
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <span class="font-medium">{{ result.title }}</span>
                                        <span v-if="result.year" class="text-xs text-muted-foreground">{{ result.year }}</span>
                                    </div>
                                    <p
                                        v-if="result.originalTitle && result.originalTitle !== result.title"
                                        class="mt-0.5 truncate text-xs text-muted-foreground"
                                    >
                                        {{ result.originalTitle }}
                                    </p>
                                    <p class="mt-1 text-xs text-muted-foreground">TheTVDB #{{ result.thetvdbId }}</p>
                                    <p v-if="result.overview" class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                        {{ result.overview }}
                                    </p>
                                </div>
                            </button>

                            <p v-if="searchResults.length === 0" class="px-3 py-5 text-center text-sm text-muted-foreground">
                                {{ searchError ? 'Не удалось выполнить поиск в TheTVDB.' : 'Ничего не найдено.' }}
                            </p>
                        </div>
                    </template>
                </div>

                <div class="grid gap-4 sm:grid-cols-[1fr_120px]">
                    <div class="space-y-2">
                        <Label for="rss-url">RSS-лента *</Label>
                        <Input
                            id="rss-url"
                            v-model="rssUrl"
                            type="url"
                            placeholder="https://example.com/rss"
                            required
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="season-number">Сезон *</Label>
                        <Input
                            id="season-number"
                            v-model.number="seasonNumber"
                            type="number"
                            min="0"
                            required
                        />
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-lg border p-4">
                    <Checkbox id="monitoring" v-model="monitored" class="mt-0.5" />
                    <div class="space-y-1">
                        <Label for="monitoring" class="cursor-pointer">Включить мониторинг</Label>
                        <p class="text-xs leading-relaxed text-muted-foreground">
                            Aniarr будет проверять RSS и автоматически планировать загрузки. Синхронизация состояния с Sonarr продолжится даже при отключённом мониторинге.
                        </p>
                    </div>
                </div>

                <div v-if="submitError" class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                    {{ submitError }}
                </div>

                <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                    <Button type="button" variant="outline" :disabled="submitting" @click="handleOpenChange(false)">
                        Отмена
                    </Button>
                    <Button type="submit" :disabled="!canSubmit || submitting">
                        <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
                        {{ submitting ? 'Добавление...' : 'Добавить' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { computed, onUnmounted, ref, watch } from 'vue'
    import { Loader2, Search } from '@lucide/vue'
    import { useRouter } from 'vue-router'
    import { searchTvdbSeries } from '@/api/tvdb'
    import Button from '@/components/ui/button/Button.vue'
    import { Checkbox } from '@/components/ui/checkbox'
    import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
    import Input from '@/components/ui/input/Input.vue'
    import Label from '@/components/ui/label/Label.vue'
    import useSeriesStore from '@/stores/SeriesStore'

    defineProps({
        open: { type: Boolean, default: false },
    })

    const emit = defineEmits(['onOpenChange'])
    const router = useRouter()
    const seriesStore = useSeriesStore()

    const searchQuery = ref('')
    const searchResults = ref([])
    const selectedSeries = ref(null)
    const searching = ref(false)
    const hasSearched = ref(false)
    const searchError = ref(null)
    const rssUrl = ref('')
    const seasonNumber = ref(1)
    const monitored = ref(true)
    const submitting = ref(false)
    const submitError = ref(null)

    let searchTimer = null
    let searchSequence = 0

    const showSearchResults = computed(() =>
        searchQuery.value.trim().length >= 2
        && ! searching.value
        && hasSearched.value,
    )

    const canSubmit = computed(() => Boolean(
        selectedSeries.value
        && rssUrl.value.trim()
        && Number.isInteger(Number(seasonNumber.value))
        && Number(seasonNumber.value) >= 0,
    ))

    watch(searchQuery, (value) => {
        if (selectedSeries.value && value === selectedSeries.value.title) {
            return
        }

        selectedSeries.value = null
        searchResults.value = []
        hasSearched.value = false
        searchError.value = null

        if (searchTimer) {
            clearTimeout(searchTimer)
        }

        const query = value.trim()
        if (query.length < 2) {
            searching.value = false
            return
        }

        searchTimer = setTimeout(() => runSearch(query), 350)
    })

    async function runSearch(query) {
        const sequence = ++searchSequence
        searching.value = true
        searchError.value = null

        try {
            const results = await searchTvdbSeries(query)
            if (sequence !== searchSequence) return

            searchResults.value = results
        } catch (exception) {
            if (sequence !== searchSequence) return

            searchResults.value = []
            searchError.value = exception
        } finally {
            if (sequence === searchSequence) {
                searching.value = false
                hasSearched.value = true
            }
        }
    }

    function selectSeries(series) {
        selectedSeries.value = series
        searchQuery.value = series.title
        searchResults.value = []
        hasSearched.value = false
        searchError.value = null
    }

    function clearSelection() {
        selectedSeries.value = null
        searchQuery.value = ''
        searchResults.value = []
        hasSearched.value = false
    }

    function resetForm() {
        searchSequence++
        if (searchTimer) clearTimeout(searchTimer)

        searchQuery.value = ''
        searchResults.value = []
        selectedSeries.value = null
        searching.value = false
        hasSearched.value = false
        searchError.value = null
        rssUrl.value = ''
        seasonNumber.value = 1
        monitored.value = true
        submitting.value = false
        submitError.value = null
    }

    function handleOpenChange(state) {
        if (! state && ! submitting.value) {
            resetForm()
        }

        if (! submitting.value || state) {
            emit('onOpenChange', state)
        }
    }

    function errorMessage(exception) {
        const data = exception?.response?.data
        const validationErrors = data?.errors

        if (validationErrors && typeof validationErrors === 'object') {
            const first = Object.values(validationErrors).flat()[0]
            if (first) return String(first)
        }

        return data?.message ?? exception?.message ?? 'Не удалось добавить сериал.'
    }

    async function handleSubmit() {
        if (! canSubmit.value || submitting.value) return

        submitting.value = true
        submitError.value = null

        try {
            const series = await seriesStore.create({
                thetvdb_id: selectedSeries.value.thetvdbId,
                monitored: monitored.value,
                rss_feeds: [
                    {
                        rss_url: rssUrl.value.trim(),
                        season_number: Number(seasonNumber.value),
                    },
                ],
            })

            if (! series) {
                throw new Error('Backend не вернул созданный сериал.')
            }

            emit('onOpenChange', false)
            resetForm()
            await router.push(`/series/${series.id}`)
        } catch (exception) {
            submitError.value = errorMessage(exception)
        } finally {
            submitting.value = false
        }
    }

    onUnmounted(() => {
        searchSequence++
        if (searchTimer) clearTimeout(searchTimer)
    })
</script>
