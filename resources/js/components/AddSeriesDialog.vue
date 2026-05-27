<template>
    <Dialog :open="open" @onOpenChange="handleOpenChange">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Добавить сериал</DialogTitle>
                <DialogDescription>
                    Укажите RSS-ленту и информацию о сериале для начала отслеживания
                </DialogDescription>
            </DialogHeader>

            <form @submit="handleSubmit" class="space-y-4">
                <!-- RSS URL -->
                <div class="space-y-2">
                    <Label htmlFor="rss-url">RSS-ссылка *</Label>
                    <Input id="rss-url" type="url" placeholder="https://example.com/rss/series" v-model="rssUrl" @change="(e) => setRssUrl(e.target.value)" />
                </div>

                <!-- Title with search -->
                <div class="space-y-2">
                    <Label htmlFor="title">Название сериала *</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input id="title" placeholder="Начните вводить название..." v-model="title" @change="(e) => setTitle(e.target.value)" class="pl-9" />
                        <Loader2 v-if="isSearching" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    </div>

                    <!-- Search Results -->
                    <div v-if="(searchResults.length > 0 || (hasSearched && !isSearching))" class="mt-2 rounded-lg border bg-popover p-2">
                        <div v-if="searchResults.length > 0" class="space-y-2">
                            <button v-for="result in searchResults" :key="result.id" type="button" @click="() => handleSelectResult(result)" class="flex w-full items-start gap-3 rounded-md p-2 text-left transition-colors hover:bg-muted">
                                <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-muted">
                                    <img v-if="result?.posterUrl" :src="result.posterUrl" :alt="result.title" class="h-full w-full object-cover" @error="console.log('err')" />
                                    <div v-else class="flex h-full w-full items-center justify-center text-muted-foreground">
                                        TV
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium truncate">{{ result?.title }}</span>
                                        <span v-if="result.year" class="text-sm text-muted-foreground">({{ result?.year }})</span>
                                    </div>
                                    <p v-if="result?.overview" class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                        {{ result.overview }}
                                    </p>
                                </div>
                            </button>
                        </div>
                        <p v-else class="py-4 text-center text-sm text-muted-foreground">
                            Ничего не найдено
                        </p>
                    </div>
                </div>

                <!-- TheTVDB ID -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Label htmlFor="thetvdb-id">TheTVDB ID *</Label>
                        <a v-if="thetvdbId" :href="`https://thetvdb.com/series/${thetvdbId}`" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs text-primary hover:underline">
                            <ExternalLink class="h-3 w-3" />
                            Открыть
                        </a>
                    </div>
                    <Input id="thetvdb-id" type="number" placeholder="Выберите из результатов или введите вручную" :value="thetvdbId" @change="(e) => setThetvdbId(e.target.value)" />
                </div>

                <!-- Actions -->
                <div className="flex justify-end gap-2 pt-4">
                    <Button type="button" variant="outline" @click="() => handleOpenChange(false)">
                        Отмена
                    </Button>
                    <Button type="submit">
                        Добавить
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup>
    import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
    import Input from '@/components/ui/input/Input.vue';
    import Label from '@/components/ui/label/Label.vue';
    import Button from './ui/button/Button.vue';

    import { Search, Loader2, ExternalLink } from '@lucide/vue';

    import { ref } from 'vue';

    defineProps({
        open: Boolean
    })
    defineEmits(['onOpenChange'])

    const rssUrl = ref('')
    const title = ref('')
    const isSearching = ref(false)
    const searchResults = ref([
        {
            id: 11,
            title: 'Фрирен',
            year: 2016,
            overview: 'Описание'
        }
    ])
    const hasSearched = ref(true)
    const thetvdbId = ref(1111)

    function handleOpenChange() {
        //
    }

    function handleSubmit() {
        //
    }

    function setRssUrl() {
        //
    }

</script>

<style lang="scss" scoped></style>
