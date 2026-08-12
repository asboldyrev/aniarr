<template>
    <div class="space-y-5 p-4 sm:space-y-6 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Настройки</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Подключения к внешним сервисам и параметры загрузки Aniarr
                </p>
            </div>

            <Button :disabled="loading || saving || ! dirty" @click="save">
                <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                <Save v-else class="mr-2 h-4 w-4" />
                Сохранить изменения
            </Button>
        </div>

        <div v-if="loading" class="flex min-h-64 items-center justify-center">
            <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
        </div>

        <div v-else class="space-y-4">
            <div
                v-if="saveMessage"
                class="rounded-lg border px-4 py-3 text-sm"
                :class="saveError
                    ? 'border-red-500/30 bg-red-500/5 text-red-700 dark:text-red-300'
                    : 'border-emerald-500/30 bg-emerald-500/5 text-emerald-700 dark:text-emerald-300'"
            >
                {{ saveMessage }}
            </div>

            <SettingsConnectionCard
                title="Sonarr"
                description="Управление сериалами, состоянием эпизодов и импортом скачанных файлов."
                :status="statuses.sonarr"
                :testing="testing.sonarr"
                :dirty="serviceDirty('sonarr')"
                @test="testConnection('sonarr')"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="sonarr-url">URL Sonarr</Label>
                        <Input id="sonarr-url" v-model="form.sonarr_url" placeholder="http://sonarr:8989" />
                    </div>
                    <div class="space-y-2">
                        <Label for="sonarr-api-key">API key</Label>
                        <SecretInput id="sonarr-api-key" v-model="form.sonarr_api_key" />
                    </div>
                </div>
            </SettingsConnectionCard>

            <SettingsConnectionCard
                title="qBittorrent"
                description="Добавление torrent, выбор файлов, прогресс загрузки и очистка завершённых операций."
                :status="statuses.qbittorrent"
                :testing="testing.qbittorrent"
                :dirty="serviceDirty('qbittorrent')"
                @test="testConnection('qbittorrent')"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="qbittorrent-url">URL WebUI</Label>
                        <Input id="qbittorrent-url" v-model="form.qbittorrent_url" placeholder="http://qbittorrent:8080" />
                    </div>
                    <div class="space-y-2">
                        <Label for="qbittorrent-username">Имя пользователя</Label>
                        <Input id="qbittorrent-username" v-model="form.qbittorrent_username" autocomplete="username" />
                    </div>
                    <div class="space-y-2">
                        <Label for="qbittorrent-password">Пароль</Label>
                        <SecretInput id="qbittorrent-password" v-model="form.qbittorrent_password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="qbittorrent-category">Категория</Label>
                        <Input id="qbittorrent-category" v-model="form.qbittorrent_category" placeholder="tv-sonarr" />
                        <p class="text-xs text-muted-foreground">
                            Необязательная категория, назначаемая Aniarr новым torrent. Может использоваться qBittorrent для выбора папки загрузки.
                        </p>
                    </div>
                </div>
            </SettingsConnectionCard>

            <Card class="gap-0 p-0">
                <CardHeader class="p-4 sm:p-5">
                    <CardTitle class="text-lg">Загрузки</CardTitle>
                    <CardDescription class="mt-1">
                        Путь, который Aniarr передаёт qBittorrent при создании torrent.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3 p-4 pt-0 sm:p-5 sm:pt-0">
                    <div class="space-y-2">
                        <Label for="download-save-path">Папка загрузки</Label>
                        <Input id="download-save-path" v-model="form.download_save_path" placeholder="/media/downloads/tvshows" />
                        <p class="text-xs leading-relaxed text-muted-foreground">
                            Если путь задан, он передаётся qBittorrent как save path. Для последующего ManualImport Sonarr должен видеть скачанные файлы по совместимому контейнерному пути. Если папкой управляет категория qBittorrent, это поле можно оставить пустым.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <SettingsConnectionCard
                title="Jellyfin"
                description="Обновление медиабиблиотеки после успешного импорта новых эпизодов."
                :status="statuses.jellyfin"
                :testing="testing.jellyfin"
                :dirty="serviceDirty('jellyfin')"
                @test="testConnection('jellyfin')"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="jellyfin-url">URL Jellyfin</Label>
                        <Input id="jellyfin-url" v-model="form.jellyfin_url" placeholder="http://jellyfin:8096" />
                    </div>
                    <div class="space-y-2">
                        <Label for="jellyfin-api-key">API key</Label>
                        <SecretInput id="jellyfin-api-key" v-model="form.jellyfin_api_key" />
                    </div>
                </div>
            </SettingsConnectionCard>

            <SettingsConnectionCard
                title="TheTVDB"
                description="Поиск сериалов, метаданные, локализованные названия и постеры."
                :status="statuses.thetvdb"
                :testing="testing.thetvdb"
                :dirty="serviceDirty('thetvdb')"
                @test="testConnection('thetvdb')"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="thetvdb-api-key">API key</Label>
                        <SecretInput id="thetvdb-api-key" v-model="form.thetvdb_api_key" />
                    </div>
                    <div class="space-y-2">
                        <Label for="thetvdb-pin">PIN</Label>
                        <SecretInput id="thetvdb-pin" v-model="form.thetvdb_pin" placeholder="Необязательно" />
                    </div>
                </div>
            </SettingsConnectionCard>

            <div class="flex justify-end pt-1">
                <Button :disabled="saving || ! dirty" @click="save">
                    <Loader2 v-if="saving" class="mr-2 h-4 w-4 animate-spin" />
                    <Save v-else class="mr-2 h-4 w-4" />
                    Сохранить изменения
                </Button>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { computed, onMounted, reactive, ref } from 'vue'
    import { Loader2, Save } from '@lucide/vue'
    import { getSettings, testSettingsConnection, updateSettings } from '@/api/settings'
    import SecretInput from '@/components/Settings/SecretInput.vue'
    import SettingsConnectionCard from '@/components/Settings/SettingsConnectionCard.vue'
    import Button from '@/components/ui/button/Button.vue'
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
    import Input from '@/components/ui/input/Input.vue'
    import Label from '@/components/ui/label/Label.vue'

    const keys = [
        'sonarr_url',
        'sonarr_api_key',
        'qbittorrent_url',
        'qbittorrent_username',
        'qbittorrent_password',
        'jellyfin_url',
        'jellyfin_api_key',
        'thetvdb_api_key',
        'thetvdb_pin',
        'download_save_path',
        'qbittorrent_category',
    ]

    const serviceKeys = {
        sonarr: ['sonarr_url', 'sonarr_api_key'],
        qbittorrent: ['qbittorrent_url', 'qbittorrent_username', 'qbittorrent_password', 'qbittorrent_category', 'download_save_path'],
        jellyfin: ['jellyfin_url', 'jellyfin_api_key'],
        thetvdb: ['thetvdb_api_key', 'thetvdb_pin'],
    }

    const form = reactive(Object.fromEntries(keys.map((key) => [key, ''])))
    const original = ref({})
    const loading = ref(true)
    const saving = ref(false)
    const saveMessage = ref('')
    const saveError = ref(false)

    const statuses = reactive({
        sonarr: null,
        qbittorrent: null,
        jellyfin: null,
        thetvdb: null,
    })

    const testing = reactive({
        sonarr: false,
        qbittorrent: false,
        jellyfin: false,
        thetvdb: false,
    })

    const dirty = computed(() => keys.some((key) => form[key] !== (original.value[key] ?? '')))

    function normalize(settings) {
        return Object.fromEntries(keys.map((key) => [key, settings?.[key] ?? '']))
    }

    function applySettings(settings) {
        const normalized = normalize(settings)
        original.value = { ...normalized }

        for (const key of keys) {
            form[key] = normalized[key]
        }
    }

    function serviceDirty(service) {
        return (serviceKeys[service] ?? []).some(
            (key) => form[key] !== (original.value[key] ?? ''),
        )
    }

    async function load() {
        loading.value = true
        saveMessage.value = ''

        try {
            applySettings(await getSettings())
        } catch (exception) {
            saveError.value = true
            saveMessage.value = exception?.response?.data?.message
                ?? 'Не удалось загрузить настройки.'
        } finally {
            loading.value = false
        }
    }

    async function save() {
        if (saving.value || ! dirty.value) return

        saving.value = true
        saveMessage.value = ''
        saveError.value = false

        try {
            applySettings(await updateSettings({ ...form }))
            saveMessage.value = 'Настройки сохранены.'

            for (const service of Object.keys(statuses)) {
                statuses[service] = null
            }
        } catch (exception) {
            saveError.value = true
            saveMessage.value = exception?.response?.data?.message
                ?? 'Не удалось сохранить настройки.'
        } finally {
            saving.value = false
        }
    }

    async function testConnection(service) {
        if (testing[service] || serviceDirty(service)) return

        testing[service] = true
        statuses[service] = null

        try {
            statuses[service] = await testSettingsConnection(service)
        } catch (exception) {
            statuses[service] = {
                connected: false,
                message: exception?.response?.data?.message ?? 'Ошибка проверки подключения.',
            }
        } finally {
            testing[service] = false
        }
    }

    onMounted(load)
</script>
