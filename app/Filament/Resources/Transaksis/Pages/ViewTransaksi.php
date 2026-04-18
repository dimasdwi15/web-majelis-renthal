<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Enums\MetodePembayaran;
use App\Enums\StatusTransaksi;
use App\Filament\Resources\Transaksis\TransaksiResource;
use App\Models\Transaksi;
use App\Services\TransaksiService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    /**
     * Polling interval — Livewire me-refresh komponen setiap 30 detik.
     * Ini memastikan status transaksi ter-update otomatis di UI
     * tanpa perlu reload manual oleh admin.
     */
    protected static ?string $pollingInterval = '30s';

    // ── Eager loading semua relasi sekaligus ──────────────────────────

    protected function resolveRecord(int|string $key): Transaksi
    {
        return Transaksi::query()
            ->with([
                'user:id,name,email,phone',
                'details.barang.fotoUtama',
                'jaminanIdentitas:id,transaksi_id,jenis_identitas,path_file',
                'denda.foto:id,denda_id,path_foto',
                'pembayaran:id,transaksi_id,jenis,jumlah,status,dibayar_pada',
            ])
            ->findOrFail($key);
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->resolveRecord($this->record->getKey());
    }

    /**
     * Saat halaman dibuka, jalankan dua pengecekan otomatis:
     *   1. Auto-mark terlambat — jika transaksi berjalan sudah melewati tanggal_kembali
     *   2. Auto-cancel COD expired — jika COD tidak dibayar dalam 24 jam
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->autoCheckTerlambat();
        $this->autoCheckDanBatalkanCodExpired();
    }

    // ── Auto-check: tandai terlambat ──────────────────────────────────
    //
    // Jika transaksi ini statusnya 'berjalan' dan tanggal_kembali sudah
    // terlewati (today > tanggal_kembali), langsung tandai sebagai 'terlambat'.
    // Admin juga mendapat notifikasi di UI.

    private function autoCheckTerlambat(): void
    {
        if (
            $this->record->status === StatusTransaksi::Berjalan
            && now()->startOfDay()->isAfter(\Carbon\Carbon::parse($this->record->tanggal_kembali)->startOfDay())
        ) {
            app(TransaksiService::class)->markTerlambat();
            $this->refreshRecord();

            Notification::make()
                ->title('Status Diperbarui: Terlambat')
                ->body(
                    'Transaksi ' . $this->record->nomor_transaksi .
                    ' telah melewati batas pengembalian (' .
                    \Carbon\Carbon::parse($this->record->tanggal_kembali)->format('d M Y') .
                    '). Status diubah menjadi "Terlambat". ' .
                    'Denda keterlambatan 50% (Rp ' .
                    number_format($this->record->total_sewa * 0.5, 0, ',', '.') .
                    ') akan dihitung saat pengembalian.'
                )
                ->warning()
                ->persistent()
                ->send();
        }
    }

    // ── Auto-check: batalkan COD expired ─────────────────────────────
    //
    // Deadline COD = tanggal_ambil + 1 hari (H+1).
    // Jika sudah melewati deadline, otomatis dibatalkan.

    private function autoCheckDanBatalkanCodExpired(): void
    {
        if (
            $this->record->status === StatusTransaksi::MenungguPembayaran
            && $this->record->metode_pembayaran === MetodePembayaran::Tunai
            && now()->isAfter(\Carbon\Carbon::parse($this->record->tanggal_ambil)->addDay())
        ) {
            app(TransaksiService::class)->cancelExpiredCod();
            $this->refreshRecord();

            Notification::make()
                ->title('Transaksi Dibatalkan Otomatis')
                ->body(
                    'Transaksi ' . $this->record->nomor_transaksi .
                    ' dibatalkan karena user tidak membayar COD dalam batas waktu 24 jam (H+1).'
                )
                ->warning()
                ->send();
        }
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Detail Transaksi';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return $this->record->nomor_transaksi;
    }

    // ── Header Actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            // 1. Bayar COD
            Action::make('bayarCod')
                ->label('Bayar COD & Ambil Barang')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran COD')
                ->modalIcon('heroicon-o-banknotes')
                ->modalIconColor('info')
                ->modalDescription('Apakah user sudah datang dan membayar tunai? Stok barang akan dikurangi setelah konfirmasi.')
                ->modalSubmitActionLabel('Ya, Konfirmasi Bayar')
                ->action(function () {
                    app(TransaksiService::class)->bayarCod($this->record);
                    $this->refreshRecord();
                    Notification::make()
                        ->title('Pembayaran COD Dikonfirmasi')
                        ->body('Stok telah dikurangi. Barang siap digunakan.')
                        ->success()
                        ->send();
                })
                ->visible(
                    fn() => $this->record->status === StatusTransaksi::MenungguPembayaran
                        && $this->record->metode_pembayaran === MetodePembayaran::Tunai
                ),

            // 2. Pengambilan Barang
            Action::make('ambilBarang')
                ->label('Serahkan Barang')
                ->icon('heroicon-o-cube')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengambilan Barang')
                ->modalIcon('heroicon-o-cube')
                ->modalIconColor('success')
                ->modalDescription('Barang akan diserahkan ke penyewa. Status transaksi akan berubah menjadi "Berjalan".')
                ->modalSubmitActionLabel('Ya, Serahkan Barang')
                ->action(function () {
                    app(TransaksiService::class)->ambilBarang($this->record);
                    $this->refreshRecord();
                    Notification::make()
                        ->title('Barang Telah Diserahkan')
                        ->body('Status transaksi diubah menjadi "Berjalan".')
                        ->success()
                        ->send();
                })
                ->visible(
                    fn() => $this->record->status === StatusTransaksi::Dibayar
                        && $this->record->metode_pembayaran === MetodePembayaran::Midtrans
                ),

            // 3. Proses Pengembalian
            // Tombol ini muncul untuk status 'berjalan' DAN 'terlambat'.
            // Jika terlambat, denda keterlambatan (50% total_sewa) sudah dihitung
            // otomatis dari accessor — ditampilkan di modal sebagai informasi.
            // Admin hanya perlu menambah denda kerusakan (jika ada).

            Action::make('prosesKembali')
                ->label('Proses Pengembalian')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->modalHeading('Proses Pengembalian Barang')
                ->modalIcon('heroicon-o-arrow-uturn-left')
                ->modalIconColor('warning')
                ->modalDescription(
                    fn() => $this->record->hari_telat > 0
                        ? "⚠️ Terlambat {$this->record->hari_telat} hari.\n" .
                          "Denda keterlambatan otomatis: Rp " .
                          number_format($this->record->hitung_denda_telat, 0, ',', '.') .
                          " (50% dari total sewa Rp " .
                          number_format($this->record->total_sewa, 0, ',', '.') . ").\n" .
                          "Tambahkan denda kerusakan di bawah jika ada."
                        : '✅ Pengembalian tepat waktu — tidak ada denda keterlambatan.'
                )
                ->modalSubmitActionLabel('Proses Sekarang')
                ->form([
                    TextInput::make('dendaKerusakan')
                        ->label('Denda Kerusakan (Rp)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->prefix('Rp')
                        ->placeholder('0')
                        ->helperText('Kosongkan atau isi 0 jika tidak ada kerusakan'),

                    Textarea::make('catatan')
                        ->label('Catatan Kerusakan')
                        ->placeholder('Deskripsikan kondisi kerusakan barang...')
                        ->rows(3),

                    FileUpload::make('foto')
                        ->label('Foto Bukti Kerusakan')
                        ->multiple()
                        ->image()
                        ->maxSize(2048)
                        ->directory('denda')
                        ->disk('public')
                        ->imagePreviewHeight('100')
                        ->panelLayout('grid')
                        ->reorderable(),
                ])
                ->action(function (array $data) {
                    $fotoFiles = [];
                    if (!empty($data['foto'])) {
                        foreach ($data['foto'] as $path) {
                            $fullPath = storage_path('app/public/' . $path);
                            if (file_exists($fullPath)) {
                                $fotoFiles[] = new \Illuminate\Http\UploadedFile(
                                    $fullPath,
                                    basename($path),
                                    mime_content_type($fullPath),
                                    null,
                                    true
                                );
                            }
                        }
                    }

                    app(TransaksiService::class)->prosesKembali(
                        transaksi: $this->record,
                        dendaKerusakan: (float) ($data['dendaKerusakan'] ?? 0),
                        catatan: $data['catatan'] ?? '',
                        fotoFiles: $fotoFiles
                    );

                    $this->refreshRecord();

                    $totalDenda = (float) $this->record->total_denda;
                    Notification::make()
                        ->title('Pengembalian Berhasil')
                        ->body($totalDenda > 0
                            ? 'Barang dikembalikan. Total denda: Rp ' . number_format($totalDenda, 0, ',', '.')
                            : 'Barang dikembalikan tanpa denda. Transaksi selesai.')
                        ->success()
                        ->send();
                })
                ->visible(fn() => in_array($this->record->status, [
                    StatusTransaksi::Berjalan,
                    StatusTransaksi::Terlambat,
                ])),

            // 4. Bayar Denda COD
            Action::make('bayarDendaCod')
                ->label('Bayar Denda & Selesaikan')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Denda COD')
                ->modalIcon('heroicon-o-check-circle')
                ->modalIconColor('success')
                ->modalDescription(fn() => 'Total denda: Rp ' . number_format($this->record->total_denda, 0, ',', '.') . '. Transaksi akan ditutup setelah ini.')
                ->modalSubmitActionLabel('Ya, Bayar & Selesaikan')
                ->action(function () {
                    app(TransaksiService::class)->bayarDendaCod($this->record);
                    $this->refreshRecord();
                    Notification::make()
                        ->title('Denda Lunas')
                        ->body('Transaksi telah diselesaikan.')
                        ->success()
                        ->send();
                })
                ->visible(
                    fn() => $this->record->status === StatusTransaksi::Dikembalikan
                        && $this->record->metode_pembayaran === MetodePembayaran::Tunai
                        && (float) $this->record->total_denda > 0
                ),

            // 5. Kirim Tagihan Denda (Midtrans)
            Action::make('kirimTagihan')
                ->label('Kirim Tagihan Denda')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Kirim Tagihan Denda via Midtrans')
                ->modalIcon('heroicon-o-paper-airplane')
                ->modalIconColor('primary')
                ->modalDescription(fn() => 'Tagihan Rp ' . number_format($this->record->total_denda, 0, ',', '.') . ' akan dikirim ke user via notifikasi dan link pembayaran.')
                ->modalSubmitActionLabel('Ya, Kirim Tagihan')
                ->action(function () {
                    app(TransaksiService::class)->kirimTagihan($this->record);
                    $this->refreshRecord();
                    Notification::make()
                        ->title('Tagihan Terkirim')
                        ->body('Notifikasi dan link pembayaran denda telah dikirim ke user.')
                        ->success()
                        ->send();
                })
                ->visible(
                    fn() => $this->record->status === StatusTransaksi::Dikembalikan
                        && $this->record->metode_pembayaran === MetodePembayaran::Midtrans
                        && (float) $this->record->total_denda > 0
                ),
        ];
    }

    // ── Infolist Schema ────────────────────────────────────────────────

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([

                // ══════════════════════════════════════════════════════
                // BANNER — Summary bar full width
                // ══════════════════════════════════════════════════════
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([

                            // Kolom 1: Status
                            Group::make()->schema([
                                TextEntry::make('status')
                                    ->label('Status Transaksi')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state instanceof StatusTransaksi ? $state->label() : $state)
                                    ->color(fn($state) => $state instanceof StatusTransaksi ? $state->color() : 'gray')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('metode_pembayaran')
                                    ->label('Metode Pembayaran')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state instanceof MetodePembayaran ? $state->label() : $state)
                                    ->color(fn($state) => $state instanceof MetodePembayaran ? $state->color() : 'gray'),
                            ]),

                            // Kolom 2: Grand Total
                            Group::make()->schema([
                                TextEntry::make('grand_total_banner')
                                    ->label('Grand Total')
                                    ->getStateUsing(fn(Transaksi $record) => $record->total_keseluruhan)
                                    ->money('IDR')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::ExtraBold)
                                    ->color('success'),

                                TextEntry::make('status_pembayaran')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'lunas'    => '✓ Lunas',
                                        'menunggu' => '⏳ Menunggu',
                                        'gagal'    => '✗ Gagal',
                                        default    => $state,
                                    })
                                    ->color(fn($state) => match ($state) {
                                        'lunas'    => 'success',
                                        'menunggu' => 'warning',
                                        'gagal'    => 'danger',
                                        default    => 'gray',
                                    }),
                            ]),

                            // Kolom 3: Periode & No. Transaksi
                            Group::make()->schema([
                                TextEntry::make('periode_sewa')
                                    ->label('Periode Sewa')
                                    ->getStateUsing(
                                        fn(Transaksi $record) =>
                                        \Carbon\Carbon::parse($record->tanggal_ambil)->format('d M Y')
                                            . ' — '
                                            . \Carbon\Carbon::parse($record->tanggal_kembali)->format('d M Y')
                                    )
                                    ->icon('heroicon-m-calendar-days')
                                    ->iconPosition(IconPosition::Before)
                                    ->weight(FontWeight::SemiBold),

                                TextEntry::make('nomor_transaksi')
                                    ->label('No. Transaksi')
                                    ->copyable()
                                    ->copyMessage('Disalin!')
                                    ->icon('heroicon-m-document-duplicate')
                                    ->iconPosition(IconPosition::Before)
                                    ->color('gray'),
                            ]),
                        ]),

                        TextEntry::make('sisa_waktu_cod')
                            ->label('⏰ Batas Waktu Pembayaran COD')
                            ->icon('heroicon-m-clock')
                            ->iconPosition(IconPosition::Before)
                            ->weight(FontWeight::Bold)
                            ->view('filament.components.cod-countdown')
                            ->visible(
                                fn(Transaksi $record) =>
                                $record->metode_pembayaran === MetodePembayaran::Tunai
                                    && $record->status === StatusTransaksi::MenungguPembayaran
                            ),
                    ]),

                // ══════════════════════════════════════════════════════
                // LEFT (2/3) — Tabs
                // ══════════════════════════════════════════════════════
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Tabs::make()
                            ->tabs([

                                // ── Tab 1: Penyewa ──
                                Tab::make('Penyewa')
                                    ->icon('heroicon-o-user')
                                    ->schema([
                                        Section::make('Data Penyewa')
                                            ->description('Informasi identitas dan kontak penyewa')
                                            ->icon('heroicon-o-user-circle')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextEntry::make('user.name')
                                                        ->label('Nama Lengkap')
                                                        ->weight(FontWeight::Bold)
                                                        ->size(TextSize::Large)
                                                        ->icon('heroicon-m-user')
                                                        ->iconPosition(IconPosition::Before),

                                                    TextEntry::make('user.email')
                                                        ->label('Email')
                                                        ->icon('heroicon-m-envelope')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->copyable()
                                                        ->copyMessage('Email disalin!'),

                                                    TextEntry::make('user.phone')
                                                        ->label('Telepon')
                                                        ->icon('heroicon-m-phone')
                                                        ->iconPosition(IconPosition::Before)
                                                        ->default('—')
                                                        ->copyable(),
                                                ]),
                                            ]),

                                        Section::make('Jaminan Identitas')
                                            ->description('Dokumen identitas yang diserahkan sebagai jaminan')
                                            ->icon('heroicon-o-identification')
                                            ->collapsed()
                                            ->visible(fn(Transaksi $record) => $record->jaminanIdentitas !== null)
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextEntry::make('jaminanIdentitas.jenis_identitas')
                                                        ->label('Jenis Identitas')
                                                        ->badge()
                                                        ->color('info')
                                                        ->size(TextSize::Large),

                                                    ImageEntry::make('jaminanIdentitas.path_file')
                                                        ->label('Foto Identitas')
                                                        ->getStateUsing(
                                                            fn($record) => $record->jaminanIdentitas
                                                                ? asset('storage/' . $record->jaminanIdentitas->path_file)
                                                                : null
                                                        )
                                                        ->height(150)
                                                        ->width(240),
                                                ]),
                                            ]),
                                    ]),

                                // ── Tab 2: Item Disewa ──
                                Tab::make('Item Disewa')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->badge(fn(Transaksi $record) => $record->details->count())
                                    ->badgeColor('primary')
                                    ->schema([
                                        Section::make('Daftar Barang yang Disewa')
                                            ->description('Rincian item beserta durasi dan subtotal biaya')
                                            ->icon('heroicon-o-cube')
                                            ->schema([
                                                RepeatableEntry::make('details')
                                                    ->label('')
                                                    ->schema([
                                                        Grid::make(4)->schema([

                                                            ImageEntry::make('barang.fotoUtama.path_foto')
                                                                ->label('Foto Barang')
                                                                ->getStateUsing(
                                                                    fn($record) =>
                                                                    $record->barang?->fotoUtama
                                                                        ? asset('storage/' . $record->barang->fotoUtama->path_foto)
                                                                        : 'https://via.placeholder.com/80'
                                                                )
                                                                ->height(80)
                                                                ->width(80),
                                                            TextEntry::make('barang.nama')
                                                                ->label('Nama Barang')
                                                                ->weight(FontWeight::Bold)
                                                                ->icon('heroicon-m-cube')
                                                                ->iconPosition(IconPosition::Before),

                                                            TextEntry::make('jumlah')
                                                                ->label('Jumlah')
                                                                ->suffix(' unit')
                                                                ->badge()
                                                                ->color('primary'),

                                                            TextEntry::make('durasi_hari')
                                                                ->label('Durasi')
                                                                ->suffix(' hari')
                                                                ->badge()
                                                                ->color('info'),

                                                            TextEntry::make('subtotal')
                                                                ->label('Subtotal')
                                                                ->money('IDR')
                                                                ->weight(FontWeight::Bold)
                                                                ->color('success')
                                                                ->size(TextSize::Large),
                                                        ]),
                                                    ])
                                                    ->contained(false),
                                            ]),
                                    ]),

                                // ── Tab 3: Denda ──
                                // Bagian ini menampilkan denda keterlambatan yang dihitung
                                // otomatis via accessor `hitung_denda_telat` (50% total_sewa
                                // jika terlambat) dan denda kerusakan yang diinput manual admin.

                                Tab::make('Denda & Kerusakan')
                                    ->icon('heroicon-o-exclamation-triangle')
                                    ->badge(fn(Transaksi $record) => $record->denda->isNotEmpty() ? $record->denda->count() : null)
                                    ->badgeColor('danger')
                                    ->schema([
                                        Section::make('Ringkasan Denda')
                                            ->description('Kalkulasi total denda yang dikenakan pada transaksi ini')
                                            ->icon('heroicon-o-calculator')
                                            ->schema([
                                                Grid::make(2)->schema([

                                                    // Denda keterlambatan — otomatis 50% total sewa
                                                    TextEntry::make('denda_telat_display')
                                                        ->label('Denda Keterlambatan')
                                                        ->getStateUsing(fn(Transaksi $record) => $record->hitung_denda_telat)
                                                        ->money('IDR')
                                                        ->weight(FontWeight::Bold)
                                                        ->size(TextSize::Large)
                                                        ->color(fn($state) => $state > 0 ? 'danger' : 'gray')
                                                        ->helperText(fn(Transaksi $record) => $record->hari_telat > 0
                                                            ? "📅 {$record->hari_telat} hari terlambat · denda otomatis 50% dari total sewa (Rp " .
                                                              number_format($record->total_sewa, 0, ',', '.') . ")"
                                                            : '✅ Tidak ada keterlambatan'),

                                                    // Denda kerusakan — manual dari admin
                                                    TextEntry::make('total_denda')
                                                        ->label('Denda Kerusakan')
                                                        ->getStateUsing(function (Transaksi $record) {
                                                            // Hanya tampilkan denda kerusakan (total_denda - denda_telat)
                                                            $dendaKerusakan = $record->total_denda - $record->hitung_denda_telat;
                                                            return max(0, $dendaKerusakan);
                                                        })
                                                        ->money('IDR')
                                                        ->weight(FontWeight::Bold)
                                                        ->size(TextSize::Large)
                                                        ->color(fn($state) => (float) $state > 0 ? 'danger' : 'gray')
                                                        ->helperText('Diinput manual oleh admin saat pengembalian'),
                                                ]),

                                                // Info peringatan jika transaksi sedang terlambat
                                                // dan belum diproses pengembalian
                                                TextEntry::make('peringatan_terlambat')
                                                    ->label('')
                                                    ->getStateUsing(fn(Transaksi $record) =>
                                                        $record->status === StatusTransaksi::Terlambat
                                                            ? "⚠️ Transaksi ini sedang TERLAMBAT " . $record->hari_telat . " hari. " .
                                                              "Denda keterlambatan Rp " .
                                                              number_format($record->hitung_denda_telat, 0, ',', '.') .
                                                              " akan ditagihkan saat admin memproses pengembalian."
                                                            : null
                                                    )
                                                    ->visible(fn(Transaksi $record) => $record->status === StatusTransaksi::Terlambat)
                                                    ->color('danger')
                                                    ->weight(FontWeight::SemiBold),
                                            ]),

                                        Section::make('Riwayat Denda')
                                            ->description('Detail setiap denda yang tercatat')
                                            ->icon('heroicon-o-clipboard-document-list')
                                            ->visible(fn(Transaksi $record) => $record->denda->isNotEmpty())
                                            ->schema([
                                                RepeatableEntry::make('denda')
                                                    ->label('')
                                                    ->schema([
                                                        Grid::make(3)->schema([
                                                            TextEntry::make('jenis')
                                                                ->label('Jenis Denda')
                                                                ->badge()
                                                                ->formatStateUsing(fn($state) => match ($state) {
                                                                    'terlambat'  => '⏰ Keterlambatan (Otomatis)',
                                                                    'kerusakan'  => '🔧 Kerusakan (Manual)',
                                                                    default      => ucfirst($state),
                                                                })
                                                                ->color(fn($state) => $state === 'terlambat' ? 'warning' : 'danger'),

                                                            TextEntry::make('jumlah')
                                                                ->label('Jumlah')
                                                                ->money('IDR')
                                                                ->weight(FontWeight::Bold)
                                                                ->color('danger'),

                                                            TextEntry::make('dibayar_pada')
                                                                ->label('Status Bayar')
                                                                ->formatStateUsing(fn($state) => $state ? '✓ Lunas' : '⏳ Belum Bayar')
                                                                ->color(fn($state) => $state ? 'success' : 'warning')
                                                                ->badge(),
                                                        ]),

                                                        TextEntry::make('catatan')
                                                            ->label('Catatan')
                                                            ->icon('heroicon-m-chat-bubble-left')
                                                            ->iconPosition(IconPosition::Before)
                                                            ->visible(fn($state) => filled($state))
                                                            ->color('gray'),

                                                        RepeatableEntry::make('foto')
                                                            ->label('📷 Foto Bukti')
                                                            ->visible(fn($record) => $record->foto->isNotEmpty())
                                                            ->schema([
                                                                ImageEntry::make('path_foto')
                                                                    ->label('')
                                                                    ->getStateUsing(
                                                                        fn($record) => $record->path_foto
                                                                            ? asset('storage/' . $record->path_foto)
                                                                            : null
                                                                    )
                                                                    ->height(90)
                                                                    ->width(90),
                                                            ])
                                                            ->grid(5),
                                                    ])
                                                    ->contained(false),
                                            ]),
                                    ]),

                                // ── Tab 4: Riwayat Pembayaran ──
                                Tab::make('Pembayaran')
                                    ->icon('heroicon-o-credit-card')
                                    ->badge(fn(Transaksi $record) => $record->pembayaran->isNotEmpty() ? $record->pembayaran->count() : null)
                                    ->badgeColor('info')
                                    ->schema([
                                        Section::make('Riwayat Transaksi Pembayaran')
                                            ->description('Semua catatan pembayaran yang terkait transaksi ini')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                RepeatableEntry::make('pembayaran')
                                                    ->label('')
                                                    ->schema([
                                                        Grid::make(4)->schema([
                                                            TextEntry::make('jenis')
                                                                ->label('Jenis')
                                                                ->badge()
                                                                ->formatStateUsing(fn($state) => ucfirst($state))
                                                                ->color(fn($state) => $state === 'utama' ? 'info' : 'warning'),

                                                            TextEntry::make('jumlah')
                                                                ->label('Jumlah')
                                                                ->money('IDR')
                                                                ->weight(FontWeight::Bold),

                                                            TextEntry::make('status')
                                                                ->label('Status')
                                                                ->badge()
                                                                ->color(fn($state) => match ($state) {
                                                                    'lunas'    => 'success',
                                                                    'menunggu' => 'warning',
                                                                    default    => 'danger',
                                                                }),

                                                            TextEntry::make('dibayar_pada')
                                                                ->label('Dibayar Pada')
                                                                ->dateTime('d M Y · H:i')
                                                                ->placeholder('—')
                                                                ->icon('heroicon-m-clock')
                                                                ->iconPosition(IconPosition::Before)
                                                                ->color('gray'),
                                                        ]),
                                                    ])
                                                    ->contained(false),
                                            ]),
                                    ]),
                            ]),
                    ]),

                // ══════════════════════════════════════════════════════
                // RIGHT (1/3) — Sidebar
                // ══════════════════════════════════════════════════════
                Group::make()
                    ->columnSpan(1)
                    ->schema([

                        // Status card
                        Section::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Transaksi')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state instanceof StatusTransaksi ? $state->label() : $state)
                                    ->color(fn($state) => $state instanceof StatusTransaksi ? $state->color() : 'gray')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('status_pembayaran')
                                    ->label('Pembayaran Sewa')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => match ($state) {
                                        'lunas'    => '✓ Lunas',
                                        'menunggu' => '⏳ Menunggu',
                                        'gagal'    => '✗ Gagal',
                                        default    => $state,
                                    })
                                    ->color(fn($state) => match ($state) {
                                        'lunas'    => 'success',
                                        'menunggu' => 'warning',
                                        'gagal'    => 'danger',
                                        default    => 'gray',
                                    }),

                                TextEntry::make('tanggal_dikembalikan')
                                    ->label('Dikembalikan Pada')
                                    ->dateTime('d M Y · H:i')
                                    ->icon('heroicon-m-arrow-uturn-left')
                                    ->iconPosition(IconPosition::Before)
                                    ->visible(fn($state) => filled($state)),
                            ]),

                        // Ringkasan Biaya
                        Section::make('Ringkasan Biaya')
                            ->icon('heroicon-o-banknotes')
                            ->description('Total biaya yang dikenakan')
                            ->schema([
                                TextEntry::make('total_sewa')
                                    ->label('Biaya Sewa')
                                    ->money('IDR')
                                    ->weight(FontWeight::SemiBold),

                                // Denda telat: otomatis 50% total_sewa
                                TextEntry::make('sidebar_denda_telat')
                                    ->label('Denda Keterlambatan (50%)')
                                    ->getStateUsing(fn(Transaksi $record) => $record->hitung_denda_telat)
                                    ->money('IDR')
                                    ->color('danger')
                                    ->weight(FontWeight::SemiBold)
                                    ->helperText(fn(Transaksi $record) => $record->hari_telat > 0
                                        ? $record->hari_telat . ' hari terlambat'
                                        : null
                                    )
                                    ->visible(fn(Transaksi $record) => $record->hitung_denda_telat > 0),

                                // Denda kerusakan: manual
                                TextEntry::make('sidebar_denda_kerusakan')
                                    ->label('Denda Kerusakan')
                                    ->getStateUsing(function (Transaksi $record) {
                                        $dendaKerusakan = $record->total_denda - $record->hitung_denda_telat;
                                        return max(0, (float) $dendaKerusakan);
                                    })
                                    ->money('IDR')
                                    ->color('danger')
                                    ->weight(FontWeight::SemiBold)
                                    ->visible(function (Transaksi $record) {
                                        $dendaKerusakan = $record->total_denda - $record->hitung_denda_telat;
                                        return max(0, (float) $dendaKerusakan) > 0;
                                    }),

                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->getStateUsing(fn(Transaksi $record) => $record->total_keseluruhan)
                                    ->money('IDR')
                                    ->weight(FontWeight::ExtraBold)
                                    ->size(TextSize::Large)
                                    ->color('success'),
                            ]),
                    ]),
            ]);
    }
}
