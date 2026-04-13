<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Livewire\WithFileUploads;
use App\Models\Transaksi;
use App\Models\Denda;
use App\Models\DendaFoto;

class ViewTransaksi extends ViewRecord
{
    protected static string $resource = TransaksiResource::class;

    protected string $view = 'filament.transaksis.view-transaksi';

    use WithFileUploads;

    public $dendaKerusakan = 0;
    public $catatan = '';
    public $foto = [];

    public $scanKode;

    protected function getHeaderActions(): array
    {
        return [];
    }

    // 🔥 FIX DENDA TELAT BERDASARKAN HARI
    public function getDendaTelatProperty()
    {
        if (now()->lte($this->record->tanggal_kembali)) {
            return 0;
        }

        $hariTelat = now()->diffInDays($this->record->tanggal_kembali);

        return $this->record->detail->sum(function ($item) use ($hariTelat) {
            return ($item->harga_per_hari * $item->jumlah) * 0.5 * $hariTelat;
        });
    }

    public function ambil()
    {
        // VALIDASI: hanya bisa ambil kalau sudah bayar
        if ($this->record->pembayaranTerakhir?->status !== 'lunas') {
            Notification::make()
                ->title('Belum dibayar')
                ->danger()
                ->send();

            return;
        }

        $this->record->update([
            'status' => 'berjalan',
            'tanggal_ambil' => now(),
        ]);

        Notification::make()
            ->title('Barang berhasil diambil')
            ->success()
            ->send();
    }

    public function selesai()
    {
        // VALIDASI: tidak boleh selesai kalau belum bayar denda
        if ($this->record->pembayaranTerakhir?->status !== 'lunas') {
            Notification::make()
                ->title('Denda belum dibayar')
                ->danger()
                ->send();

            return;
        }

        $this->record->update([
            'status' => 'selesai',
            'tanggal_dikembalikan' => now(),
        ]);

        Notification::make()
            ->title('Transaksi selesai')
            ->success()
            ->send();
    }

    public function simpanDenda()
    {
        $this->validate([
            'dendaKerusakan' => 'required|numeric|min:0',
            'foto.*' => 'image|max:2048',
        ]);

        $denda = Denda::create([
            'transaksi_id' => $this->record->id,
            'jenis' => 'kerusakan',
            'jumlah' => $this->dendaKerusakan,
            'catatan' => $this->catatan,
        ]);

        foreach ($this->foto as $file) {
            $path = $file->store('denda', 'public');

            DendaFoto::create([
                'denda_id' => $denda->id,
                'path_foto' => $path,
            ]);
        }

        $this->record->increment('total_denda', $this->dendaKerusakan);

        $this->reset(['dendaKerusakan', 'catatan', 'foto']);

        Notification::make()
            ->title('Denda berhasil ditambahkan')
            ->success()
            ->send();
    }

    // 🔥 SCAN / INPUT KODE
    public function cariTransaksi()
    {
        $transaksi = Transaksi::where('nomor_transaksi', $this->scanKode)->first();

        if (!$transaksi) {
            Notification::make()->title('Transaksi tidak ditemukan')->danger()->send();
            return;
        }

        return redirect('/admin/transaksis/' . $transaksi->id);
    }
}
