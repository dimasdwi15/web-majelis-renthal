@php
    $statusConfig = [
        'menunggu_pembayaran' => [
            'label' => 'Menunggu Bayar',
            'color' => 'text-amber-800',
            'bg'    => 'background: #fef3c7; border: 1px solid #fcd34d;',
            'dot'   => 'background: #d97706;',
            'pulse' => true,
        ],
        'dibayar' => [
            'label' => 'Dibayar',
            'color' => 'text-blue-800',
            'bg'    => 'background: #dbeafe; border: 1px solid #93c5fd;',
            'dot'   => 'background: #2563eb;',
            'pulse' => false,
        ],
        'berjalan' => [
            'label' => 'Berjalan',
            'color' => 'text-green-800',
            'bg'    => 'background: #dcfce7; border: 1px solid #86efac;',
            'dot'   => 'background: #16a34a;',
            'pulse' => true,
        ],
        'terlambat' => [
            'label' => 'Terlambat',
            'color' => 'text-red-800',
            'bg'    => 'background: #fee2e2; border: 1px solid #fca5a5;',
            'dot'   => 'background: #dc2626;',
            'pulse' => true,
        ],
        'selesai' => [
            'label' => 'Selesai',
            'color' => 'text-[#4d462e]',
            'bg'    => 'background: #F2E8C6; border: 1px solid #ccc6a0;',
            'dot'   => 'background: #655e44;',
            'pulse' => false,
        ],
        'dibatalkan' => [
            'label' => 'Dibatalkan',
            'color' => 'text-[#7b776c]',
            'bg'    => 'background: #e7e4dc; border: 1px solid #ccc6b9;',
            'dot'   => 'background: #a09880;',
            'pulse' => false,
        ],
    ];

    $cfg = $statusConfig[$status] ?? [
        'label' => ucfirst(str_replace('_', ' ', $status)),
        'color' => 'text-[#7b776c]',
        'bg'    => 'background: #e7e4dc; border: 1px solid #ccc6b9;',
        'dot'   => 'background: #a09880;',
        'pulse' => false,
    ];
@endphp

<span class="inline-flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg flex-shrink-0 {{ $cfg['color'] }}"
      style="{{ $cfg['bg'] }}">
    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $cfg['pulse'] ? 'pulse-live' : '' }}"
          style="{{ $cfg['dot'] }}"></span>
    {{ $cfg['label'] }}
</span>
