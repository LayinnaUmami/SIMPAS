<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan {{ $label }} · {{ config('simpas.nama_aplikasi') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            color: #0f172a;
            background: #f1f5f9;
            margin: 0;
            padding: 24px;
        }
        .sheet {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            padding: 32px;
            border: 1px solid #cbd5e1;
        }
        .toolbar {
            max-width: 1100px;
            margin: 0 auto 16px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .toolbar button, .toolbar a {
            font: inherit;
            font-size: 13px;
            font-weight: 700;
            padding: 9px 16px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar .primary { background: #059669; border-color: #059669; color: #fff; }

        .kop { text-align: center; border-bottom: 3px double #0f172a; padding-bottom: 14px; margin-bottom: 18px; }
        .kop h1 { margin: 0; font-size: 20px; letter-spacing: .12em; }
        .kop p { margin: 4px 0 0; font-size: 12px; color: #475569; }

        h2.judul { text-align: center; font-size: 15px; margin: 0 0 4px; text-transform: uppercase; letter-spacing: .08em; }
        p.periode { text-align: center; font-size: 12px; color: #475569; margin: 0 0 18px; }

        table.data { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        table.data th, table.data td { border: 1px solid #94a3b8; padding: 6px 8px; }
        table.data th { background: #ecfdf5; font-weight: 800; text-transform: uppercase; font-size: 10px; letter-spacing: .05em; }
        table.data td { text-align: left; }
        table.data td.center, table.data th.center { text-align: center; }
        table.data tr:nth-child(even) td { background: #f8fafc; }

        .ket { margin-top: 22px; border: 1px solid #cbd5e1; padding: 14px 16px; page-break-inside: avoid; }
        .ket h3 { margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        .ket table { border-collapse: collapse; font-size: 12px; min-width: 260px; }
        .ket table td { border: 1px solid #94a3b8; padding: 5px 10px; }
        .ket .grid { display: flex; gap: 28px; flex-wrap: wrap; }
        .ket .total { font-weight: 800; }
        .ket .poin { font-size: 18px; font-weight: 800; }

        .tanda { margin-top: 34px; display: flex; justify-content: space-between; font-size: 12px; }
        .tanda .box { text-align: center; width: 220px; }
        .tanda .space { height: 64px; }
        .tanda .nama { font-weight: 700; text-decoration: underline; }

        .footer { margin-top: 18px; font-size: 10px; color: #64748b; text-align: center; }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .sheet { border: none; padding: 0; max-width: none; }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <a href="{{ url()->previous() }}">← Kembali</a>
    <button onclick="window.print()" class="primary">🖨️ Cetak / Simpan PDF</button>
</div>

<div class="sheet">

    {{-- KOP SURAT --}}
    <div class="kop">
        <h1>{{ config('simpas.nama_aplikasi') }}</h1>
        <p>{{ config('simpas.tagline') }}</p>
        <p>Pondok Pesantren · dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <h2 class="judul">Laporan {{ strtoupper($label) }}</h2>
    <p class="periode">
        {{ $periode['label'] }}
        @if($cari) · Pencarian: "{{ $cari }}" @endif
    </p>

    {{-- TABEL --}}
    @if($isActivity)
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:34px">No</th>
                    <th style="width:80px">NIS</th>
                    <th>Nama Santri</th>
                    <th style="width:80px">Tanggal</th>
                    @foreach($sessions as $s)
                        <th class="center">{{ $s }}</th>
                    @endforeach
                    <th class="center" style="width:70px">Total Poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $row['santri']->nis ?? '-' }}</td>
                        <td>{{ $row['santri']->nama ?? '-' }}</td>
                        <td>{{ $row['tanggal']->format('d M Y') }}</td>
                        @foreach($sessions as $s)
                            <td class="center">{{ $row['sesi'][$s] ?? '-' }}</td>
                        @endforeach
                        <td class="center"><b>{{ $row['total'] >= 0 ? '+' : '' }}{{ $row['total'] }}</b></td>
                    </tr>
                @empty
                    <tr><td class="center" colspan="{{ 4 + count($sessions) }}">Tidak ada data pada periode ini</td></tr>
                @endforelse
            </tbody>
        </table>
    @elseif($jenis === 'prestasi')
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:34px">No</th>
                    <th style="width:80px">Tanggal</th>
                    <th style="width:80px">NIS</th>
                    <th>Nama Santri</th>
                    <th>Prestasi</th>
                    <th style="width:90px">Tingkat</th>
                    <th>Keterangan</th>
                    <th class="center" style="width:55px">Poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $row->tanggal->format('d M Y') }}</td>
                        <td>{{ $row->santri->nis ?? '-' }}</td>
                        <td>{{ $row->santri->nama ?? '-' }}</td>
                        <td>{{ $row->nama_prestasi }} ({{ $row->jenis }})</td>
                        <td>{{ $row->tingkat }}</td>
                        <td>{{ $row->keterangan ?? '-' }}</td>
                        <td class="center"><b>+{{ $row->poin }}</b></td>
                    </tr>
                @empty
                    <tr><td class="center" colspan="8">Tidak ada data pada periode ini</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:34px">No</th>
                    <th style="width:80px">Tanggal</th>
                    <th style="width:80px">NIS</th>
                    <th>Nama Santri</th>
                    <th>Jenis Pelanggaran</th>
                    <th style="width:80px">Tingkat</th>
                    <th>Keterangan</th>
                    <th class="center" style="width:55px">Poin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $row->tanggal->format('d M Y') }}</td>
                        <td>{{ $row->santri->nis ?? '-' }}</td>
                        <td>{{ $row->santri->nama ?? '-' }}</td>
                        <td>{{ $row->jenis }}{{ $row->is_auto ? ' (Otomatis)' : '' }}</td>
                        <td>{{ $row->tingkat }}</td>
                        <td>{{ $row->keterangan ?? '-' }}</td>
                        <td class="center"><b>{{ $row->poin }}</b></td>
                    </tr>
                @empty
                    <tr><td class="center" colspan="8">Tidak ada data pada periode ini</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- KETERANGAN LAPORAN --}}
    <div class="ket">
        <h3>Keterangan Laporan</h3>
        <p style="font-size:12px;margin:0 0 10px"><b>Periode:</b> {{ str_replace('Periode ', '', $periode['label']) }}</p>

        <div class="grid">
            <div>
                <table>
                    @if($isActivity)
                        @foreach($keterangan['status'] as $status => $jumlah)
                            <tr>
                                <td>{{ $status }}</td>
                                <td class="total" style="text-align:right;min-width:70px">{{ $jumlah }}</td>
                            </tr>
                        @endforeach
                    @elseif($jenis === 'prestasi')
                        @foreach($keterangan['per_jenis'] as $j => $jumlah)
                            <tr>
                                <td>{{ $j }}</td>
                                <td class="total" style="text-align:right;min-width:70px">{{ $jumlah }}</td>
                            </tr>
                        @endforeach
                    @else
                        @foreach($keterangan['per_tingkat'] as $t => $jumlah)
                            <tr>
                                <td>{{ $t }}</td>
                                <td class="total" style="text-align:right;min-width:70px">{{ $jumlah }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td><b>Total Absensi</b></td>
                        <td class="total" style="text-align:right">{{ $keterangan['total_absensi'] }}</td>
                    </tr>
                    </table>
            </div>

            <div>
                <table>
                    <tr>
                        <td><b>Total Poin</b></td>
                        <td class="poin" style="text-align:right">{{ $keterangan['total_poin'] >= 0 ? '+' : '' }}{{ $keterangan['total_poin'] }}</td>
                    </tr>
                    <tr>
                        <td><b>Jumlah Baris Data</b></td>
                        <td class="total" style="text-align:right">{{ $keterangan['jumlah_baris'] }}</td>
                    </tr>
                </table>
                <p style="font-size:10px;color:#64748b;max-width:320px;margin-top:8px">
                    Seluruh angka rekap dihitung otomatis dari data absensi pada periode terpilih.
                    @if($jenis === 'diniyah') Poin Diniyah tidak masuk ranking. @endif
                </p>
            </div>
        </div>
    </div>

    {{-- TANDA TANGAN --}}
    <div class="tanda">
        <div class="box">
            <p style="margin:0">Mengetahui,</p>
            <p style="margin:2px 0 0">Pengasuh Pondok</p>
            <div class="space"></div>
            <p class="nama">………………………………</p>
        </div>
        <div class="box">
            <p style="margin:0">{{ now()->translatedFormat('d F Y') }}</p>
            <p style="margin:2px 0 0">Pengurus {{ config('simpas.nama_aplikasi') }}</p>
            <div class="space"></div>
            <p class="nama">{{ auth()->user()->name }}</p>
        </div>
    </div>

    <p class="footer">
        Dokumen ini dicetak otomatis oleh {{ config('simpas.nama_aplikasi') }} ·
        Filter: {{ $periode['label'] }}{{ $cari ? ' · Pencarian: ' . $cari : '' }}
    </p>
</div>

</body>
</html>
