<link rel="stylesheet" href="/build/assets/app-D5nfEzWy.css">

<x-filament::page>

    @php
        $sudahBayar = $record->pembayaranTerakhir?->status === 'lunas';
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">

        <!-- LEFT -->
        <div class="lg:col-span-2 space-y-6">

            <!-- USER -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm flex justify-between items-center">

                <div class="flex items-center gap-4">
                    <div
                        class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-sm font-semibold">
                        {{ substr($record->user->name, 0, 2) }}
                    </div>

                    <div>
                        <p class="font-semibold text-sm">{{ $record->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $record->user->phone }}</p>

                        @if ($record->jaminan)
                            <div class="mt-2">
                                <p class="text-xs text-yellow-500 font-semibold">
                                    Jaminan ({{ $record->jaminan->jenis_identitas }})
                                </p>

                                <img src="{{ asset('storage/' . $record->jaminan->path_file) }}"
                                    class="w-32 h-20 object-cover rounded-lg mt-1">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="text-right text-xs text-gray-500">
                    <p>Mulai: <b>{{ $record->tanggal_ambil }}</b></p>
                    <p>Kembali: <b>{{ $record->tanggal_kembali }}</b></p>

                    @php
                        $statusColor = [
                            'menunggu_pembayaran' => 'bg-yellow-500',
                            'berjalan' => 'bg-green-500',
                            'terlambat' => 'bg-red-500',
                            'selesai' => 'bg-gray-500',
                        ];
                    @endphp

                    <span
                        class="inline-block mt-2 px-3 py-1 rounded text-white text-xs {{ $statusColor[$record->status] ?? 'bg-gray-500' }}">
                        {{ strtoupper(str_replace('_', ' ', $record->status)) }}
                    </span>
                </div>

            </div>

            <!-- ITEM -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm">

                <h2 class="font-semibold mb-4">Item Disewa</h2>

                @php $totalSewa = 0; @endphp

                @foreach ($record->detail as $item)
                    @php
                        $subtotal = $item->harga_per_hari * $item->jumlah;
                        $totalSewa += $subtotal;
                    @endphp

                    <div class="flex justify-between items-center p-3 rounded-xl bg-gray-100 dark:bg-gray-800 mb-2">

                        <div class="flex items-center gap-3">

                            @if ($item->barang->foto->first())
                                <img src="{{ asset('storage/' . $item->barang->foto->first()->path_foto) }}"
                                    class="w-10 h-10 rounded-lg object-cover">
                            @endif

                            <div>
                                <p class="text-sm font-semibold">{{ $item->barang->nama }}</p>
                                <p class="text-xs text-gray-500">{{ $item->jumlah }} unit</p>
                            </div>

                        </div>

                        <p class="text-sm font-semibold">
                            Rp {{ number_format($subtotal) }}
                        </p>

                    </div>
                @endforeach

            </div>

            <!-- DENDA -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm">

                <h2 class="font-semibold mb-4">Denda & Kerusakan</h2>

                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <p class="text-xs text-gray-500">Denda Telat</p>
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded-lg text-sm">
                            Rp {{ number_format($this->dendaTelat) }}
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Denda Kerusakan</p>
                        <div class="bg-gray-100 dark:bg-gray-800 p-2 rounded-lg text-sm">
                            Rp {{ number_format($record->total_denda ?? 0) }}
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- RIGHT -->
        <div>

            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm sticky top-6">

                <h2 class="font-semibold mb-4">Ringkasan</h2>

                @php
                    $total = $totalSewa + $this->dendaTelat + ($record->total_denda ?? 0);
                @endphp

                <div class="text-sm space-y-2">

                    <div class="flex justify-between">
                        <span>Total Sewa</span>
                        <span>Rp {{ number_format($totalSewa) }}</span>
                    </div>

                    <div class="flex justify-between text-red-500">
                        <span>Denda</span>
                        <span>Rp {{ number_format($this->dendaTelat + ($record->total_denda ?? 0)) }}</span>
                    </div>

                    <hr>

                    <div class="flex justify-between font-semibold text-base">
                        <span>Total</span>
                        <span>Rp {{ number_format($total) }}</span>
                    </div>

                </div>

                <!-- STATUS PEMBAYARAN -->
                <div class="mt-4">
                    <span
                        class="px-3 py-1 text-xs rounded
                        {{ $sudahBayar ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $sudahBayar ? 'SUDAH DIBAYAR' : 'BELUM BAYAR' }}
                    </span>
                </div>

                <!-- ACTION -->
                <div class="mt-4 space-y-2">

                    {{-- AMBIL BARANG --}}
                    @if ($record->status === 'dibayar' && $sudahBayar)
                        <button type="button" wire:click.prevent="ambil"
                            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:opacity-90 transition">
                            Ambil Barang
                        </button>
                    @endif

                    {{-- SELESAI --}}
                    @if (in_array($record->status, ['berjalan', 'terlambat']) && $sudahBayar)
                        <button type="button" wire:click.prevent="selesai"
                            class="w-full bg-green-600 text-white py-2 rounded-lg hover:opacity-90 transition">
                            Selesaikan
                        </button>
                    @endif

                </div>

            </div>

        </div>

    </div>

</x-filament::page>
