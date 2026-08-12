import { onBeforeUnmount, onMounted } from 'vue'

export function useRealtimeRefresh(callback, options = {}) {
    const resources = new Set(options.resources ?? [])
    const delay = options.delay ?? 250
    const predicate = options.predicate ?? (() => true)
    let timer = null

    function handle(event) {
        const detail = event.detail ?? {}

        if (resources.size > 0 && ! resources.has(detail.resource)) {
            return
        }

        if (! predicate(detail)) {
            return
        }

        if (timer !== null) {
            window.clearTimeout(timer)
        }

        timer = window.setTimeout(() => {
            timer = null
            callback(detail)
        }, delay)
    }

    onMounted(() => window.addEventListener('aniarr:realtime', handle))

    onBeforeUnmount(() => {
        window.removeEventListener('aniarr:realtime', handle)

        if (timer !== null) {
            window.clearTimeout(timer)
        }
    })
}
