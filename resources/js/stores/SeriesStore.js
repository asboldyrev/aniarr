import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import {
    createSeries,
    deleteSeries,
    getSeries,
    getSeriesList,
    updateSeriesMonitoring,
} from '@/api/series'

export default defineStore('series', () => {
    const items = ref([])
    const current = ref(null)
    const loading = ref(false)
    const error = ref(null)
    const hasLoadedAll = ref(false)

    const isEmpty = computed(() => hasLoadedAll.value && ! loading.value && items.value.length === 0)

    function upsert(series) {
        const index = items.value.findIndex((item) => item.id === series.id)

        if (index !== -1) {
            items.value[index] = series
        } else if (hasLoadedAll.value) {
            items.value.push(series)
        }

        if (current.value?.id === series.id) {
            current.value = series
        }
    }

    function remove(id) {
        items.value = items.value.filter((item) => item.id !== id)

        if (current.value?.id === id) {
            current.value = null
        }
    }

    async function fetchAll() {
        loading.value = true
        error.value = null

        try {
            items.value = await getSeriesList()
            hasLoadedAll.value = true
        } catch (exception) {
            error.value = exception
            throw exception
        } finally {
            loading.value = false
        }
    }

    async function fetchOne(id) {
        loading.value = true
        error.value = null

        try {
            current.value = await getSeries(id)

            if (current.value) {
                upsert(current.value)
            }

            return current.value
        } catch (exception) {
            error.value = exception
            throw exception
        } finally {
            loading.value = false
        }
    }

    async function create(payload) {
        loading.value = true
        error.value = null

        try {
            const series = await createSeries(payload)

            if (series) {
                upsert(series)
            }

            return series
        } catch (exception) {
            error.value = exception
            throw exception
        } finally {
            loading.value = false
        }
    }

    async function setMonitoring(id, monitored) {
        error.value = null

        try {
            const series = await updateSeriesMonitoring(id, monitored)

            if (series) {
                upsert(series)
            }

            return series
        } catch (exception) {
            error.value = exception
            throw exception
        }
    }

    async function destroy(id, deleteFromSonarr = false) {
        error.value = null

        try {
            await deleteSeries(id, deleteFromSonarr)
            remove(id)
        } catch (exception) {
            error.value = exception
            throw exception
        }
    }

    return {
        items,
        current,
        loading,
        error,
        hasLoadedAll,
        isEmpty,
        upsert,
        remove,
        fetchAll,
        fetchOne,
        create,
        setMonitoring,
        destroy,
    }
})
