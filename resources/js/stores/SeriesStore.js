import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { createSeries, getSeries, getSeriesList } from '@/api/series'

export default defineStore('series', () => {
    const items = ref([])
    const current = ref(null)
    const loading = ref(false)
    const error = ref(null)

    const isEmpty = computed(() => ! loading.value && items.value.length === 0)

    function upsert(series) {
        const index = items.value.findIndex((item) => item.id === series.id)

        if (index === -1) {
            items.value.push(series)
        } else {
            items.value[index] = series
        }

        if (current.value?.id === series.id) {
            current.value = series
        }
    }

    async function fetchAll() {
        loading.value = true
        error.value = null

        try {
            items.value = await getSeriesList()
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

    return {
        items,
        current,
        loading,
        error,
        isEmpty,
        upsert,
        fetchAll,
        fetchOne,
        create,
    }
})
