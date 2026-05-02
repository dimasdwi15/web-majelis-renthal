import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo + Pusher Setup
 * ─────────────────────────────────────────────────────────────────────────
 * Pastikan .env sudah berisi:
 *   VITE_PUSHER_APP_KEY=xxx
 *   VITE_PUSHER_APP_CLUSTER=ap1
 *
 * Dan jalankan: npm run build
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster:  'pusher',
    key:          import.meta.env.VITE_PUSHER_APP_KEY,
    cluster:      import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS:     true,

    /**
     * Endpoint autentikasi private channel.
     * Laravel otomatis mendaftarkan /broadcasting/auth.
     * Axios sudah membawa XSRF-TOKEN dari cookie secara otomatis.
     */
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
    },
});
