@php
    $ikon = $get('ikon');
@endphp

@if ($ikon)
    {!! view('icons.' . $ikon)->render() !!}
@else
    <span>Belum memilih icon</span>
@endif
