@php
    $record = $getRecord();

    $batas = \Carbon\Carbon::parse($record->tanggal_ambil)->addDay();
    $batasTimestamp = $batas->timestamp;
    $batasFormatted = $batas->format('d M Y, H:i');
    $sudahLewat = now()->greaterThan($batas);
@endphp

@if ($sudahLewat)
    <div class="flex items-start gap-2 font-semibold text-red-600 dark:text-red-400">
        <span>❌</span>
        <span>
            Pembayaran COD telah melewati batas waktu.<br>
            <span class="text-sm opacity-80">
                Transaksi otomatis dibatalkan oleh sistem.
            </span>
        </span>
    </div>
@else
    <div
        class="rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-3 space-y-1"
        x-data="{
            deadline: {{ $batasTimestamp }},
            text: '',
            urgency: 'normal'
        }"
        x-init="
            const update = () => {
                const now = Math.floor(Date.now() / 1000);
                const diff = deadline - now;

                if (diff <= 0) {
                    text = '❌ Waktu habis — transaksi dibatalkan';
                    urgency = 'danger';
                    return;
                }

                if (diff < 300) urgency = 'danger';        // < 5 menit
                else if (diff < 1800) urgency = 'warning'; // < 30 menit
                else urgency = 'normal';

                const h = Math.floor(diff / 3600);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;

                const pad = n => String(n).padStart(2, '0');

                let parts = [];

                if (h > 0) parts.push(h + ' jam');
                if (m > 0) parts.push(m + ' menit');

                parts.push(pad(s) + ' detik');

                text = 'Akan dibatalkan dalam ' + parts.join(' ');
            };

            update();
            setInterval(update, 1000);
        "
    >
        {{-- Countdown (PRIMARY) --}}
        <div
            x-text="'⏳ ' + text"
            x-bind:class="{
                'text-yellow-700 dark:text-yellow-300': urgency === 'normal',
                'text-yellow-800 font-bold': urgency === 'warning',
                'text-red-600 font-bold animate-pulse': urgency === 'danger'
            }"
            class="text-sm"
        ></div>

        {{-- Deadline (SECONDARY) --}}
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Batas pembayaran:
            <span class="font-medium">{{ $batasFormatted }}</span>
        </div>
    </div>
@endif
