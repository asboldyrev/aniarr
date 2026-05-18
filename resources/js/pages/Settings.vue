<template>
    <div class="p-6 space-y-6">
        <div>
            <h1 class="text-3xl font-bold">Настройки</h1>
            <p class="text-muted-foreground">Конфигурация подключений к сервисам</p>
        </div>

        <div class="grid gap-6">

            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="text-2xl font-semibold leading-none tracking-tight">Sonarr</CardTitle>
                    <CardDescription>Настройки подключения к Sonarr для управления библиотекой сериалов</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label htmlFor="sonarr-url">URL сервера</Label>
                            <Input id="sonarr-url" placeholder="http://localhost:8989" />
                        </div>
                        <div class="space-y-2">
                            <Label htmlFor="sonarr-api">API ключ</Label>
                            <Input id="sonarr-api" type="password" placeholder="Введите API ключ" />
                        </div>
                    </div>
                    <Button variant="outline">Проверить подключение</Button>
                </CardContent>
            </Card>

            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="text-2xl font-semibold leading-none tracking-tight">qBittorrent</CardTitle>
                    <CardDescription>Настройки подключения к qBittorrent для загрузки торрентов</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0 space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="qbit-url">URL WebUI</Label>
                            <Input id="qbit-url" placeholder="http://localhost:8080" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="qbit-user">Имя пользователя</Label>
                            <Input id="qbit-user" placeholder="admin" />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="qbit-pass">Пароль</Label>
                        <Input id="qbit-pass" type="password" placeholder="Введите пароль" />
                    </div>
                    <Button variant="outline">Проверить подключение</Button>
                </CardContent>
            </Card>

            <Card class="p-0">
                <CardHeader class="flex flex-col space-y-1.5 p-6">
                    <CardTitle class="text-2xl font-semibold leading-none tracking-tight">Jellyfin</CardTitle>
                    <CardDescription>Настройки подключения к Jellyfin для обновления медиабиблиотеки</CardDescription>
                </CardHeader>
                <CardContent class="p-6 pt-0 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="jellyfin-url">URL сервера</Label>
                            <Input id="jellyfin-url" placeholder="http://localhost:8096" />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="jellyfin-api">API ключ</Label>
                            <Input id="jellyfin-api" type="password" placeholder="Введите API ключ" />
                        </div>
                    </div>
                    <Button variant="outline">Проверить подключение</Button>
                </CardContent>
            </Card>

        </div>
    </div>
</template>

<script setup>
    import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
    import { Button } from '@/components/ui/button';
    import Input from '@/components/ui/input/Input.vue';
    import Label from '@/components/ui/label/Label.vue';

</script>

<style lang="scss" scoped></style>
