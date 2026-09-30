class AniarrRealtimeClient {
    socket = null
    reconnectTimer = null
    reconnectAttempt = 0
    stopped = false

    connect() {
        const key = import.meta.env.VITE_REVERB_APP_KEY
        if (! key || this.socket || this.stopped) return

        const scheme = import.meta.env.VITE_REVERB_SCHEME
            || (window.location.protocol === 'https:' ? 'https' : 'http')
        const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname
        const port = import.meta.env.VITE_REVERB_PORT
            || (scheme === 'https' ? '443' : '8080')
        const wsScheme = scheme === 'https' ? 'wss' : 'ws'
        const url = `${wsScheme}://${host}:${port}/app/${encodeURIComponent(key)}?protocol=7&client=js&version=8.4.0&flash=false`

        this.socket = new WebSocket(url)

        this.socket.addEventListener('open', () => {
            this.reconnectAttempt = 0
            this.dispatchStatus('connected')
        })

        this.socket.addEventListener('message', (event) => this.handleMessage(event))

        this.socket.addEventListener('close', () => {
            this.socket = null
            this.dispatchStatus('disconnected')
            this.scheduleReconnect()
        })

        this.socket.addEventListener('error', () => {
            this.socket?.close()
        })
    }

    disconnect() {
        this.stopped = true
        if (this.reconnectTimer) {
            window.clearTimeout(this.reconnectTimer)
            this.reconnectTimer = null
        }
        this.socket?.close()
        this.socket = null
    }

    handleMessage(message) {
        let payload

        try {
            payload = JSON.parse(message.data)
        } catch {
            return
        }

        if (payload.event === 'pusher:connection_established') {
            this.send({
                event: 'pusher:subscribe',
                data: { channel: 'aniarr' },
            })
            return
        }

        if (payload.event === 'pusher:ping') {
            this.send({ event: 'pusher:pong', data: {} })
            return
        }

        if (payload.event !== 'realtime.changed' || payload.channel !== 'aniarr') {
            return
        }

        let data = payload.data
        if (typeof data === 'string') {
            try {
                data = JSON.parse(data)
            } catch {
                return
            }
        }

        window.dispatchEvent(new CustomEvent('aniarr:realtime', { detail: data }))
    }

    send(payload) {
        if (this.socket?.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(payload))
        }
    }

    scheduleReconnect() {
        if (this.stopped || this.reconnectTimer) return

        const delay = Math.min(30_000, 1_000 * (2 ** this.reconnectAttempt))
        this.reconnectAttempt = Math.min(this.reconnectAttempt + 1, 5)

        this.reconnectTimer = window.setTimeout(() => {
            this.reconnectTimer = null
            this.connect()
        }, delay)
    }

    dispatchStatus(status) {
        window.dispatchEvent(new CustomEvent('aniarr:realtime-status', {
            detail: { status },
        }))
    }
}

const realtime = new AniarrRealtimeClient()

export default realtime
