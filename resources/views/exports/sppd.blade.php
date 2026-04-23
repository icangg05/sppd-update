<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 1cm; size: 33cm 21.5cm; } /* F4 Landscape approx */
        body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; line-height: 1.2; color: #000; }
        .container { width: 100%; clear: both; }
        .left-column { width: 48%; float: left; padding-right: 1%; border-right: 1px dashed #ccc; }
        .right-column { width: 48%; float: right; padding-left: 1%; }
        
        .header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 3px; margin-bottom: 8px; }
        .logo { width: 45px; }
        .title { text-align: center; font-weight: bold; font-size: 10pt; margin: 8px 0; text-decoration: underline; }
        
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 3px 4px; vertical-align: top; }
        .no-col { width: 20px; text-align: center; }
        .label-col { width: 180px; }
        
        .signature-section { width: 100%; margin-top: 10px; }
        .sig-box { float: right; width: 200px; text-align: left; }
        
        .clear { clear: both; }
        
        /* Back Page Styles */
        .back-table { width: 100%; border-collapse: collapse; }
        .back-table td { border: 1px solid #000; height: 80px; width: 50%; padding: 4px; vertical-align: top; font-size: 7.5pt; }
    </style>
</head>
<body>
    <div class="container">
        <!-- FRONT PAGE (LEFT COLUMN) -->
        <div class="left-column">
            <table class="header-table">
                <tr>
                    <td class="logo">
                        <img src="{{ public_path('images/logo-pemkot.png') }}" style="width: 100%;">
                    </td>
                    <td style="text-align: center;">
                        <div style="font-size: 9pt; font-weight: bold;">PEMERINTAH KOTA KENDARI</div>
                        <div style="font-size: 10pt; font-weight: bold; text-transform: uppercase;">{{ $user->department->name }}</div>
                        <div style="font-size: 7pt;">{{ $user->department->address ?? 'Jl. Drs. H. Abd Silondae No. 8 Kendari' }}</div>
                    </td>
                </tr>
            </table>

            <div style="float: right; width: 180px; font-size: 7pt;">
                <table style="width: 100%;">
                    <tr><td style="width: 50px;">Lampiran</td><td>:</td><td></td></tr>
                    <tr><td>Lembar Ke</td><td>:</td><td>I, II, III, IV</td></tr>
                    <tr><td>Kode No</td><td>:</td><td></td></tr>
                    <tr><td>Nomor</td><td>:</td><td>{{ $sppd->document_number }}</td></tr>
                </table>
            </div>
            <div class="clear"></div>

            <div class="title">SURAT PERINTAH PERJALANAN DINAS (SPPD)</div>

            <table class="main-table">
                <tr>
                    <td class="no-col">1.</td>
                    <td class="label-col">Pejabat berwenang yang memberi perintah</td>
                    <td>{{ $pdfData['approver_role'] ?? 'Kepala Dinas' }}</td>
                </tr>
                <tr>
                    <td class="no-col">2.</td>
                    <td class="label-col">Nama Pegawai yang diperintahkan</td>
                    <td style="font-weight: bold; text-transform: uppercase;">{{ $user->name }}</td>
                </tr>
                <tr>
                    <td class="no-col">3.</td>
                    <td class="label-col">
                        a. Pangkat dan Golongan<br>
                        b. Jabatan / Instansi<br>
                        c. Tingkat Biaya Perjalanan Dinas
                    </td>
                    <td>
                        a. {{ $user->rank->name ?? '-' }}<br>
                        b. {{ $user->position_name }}<br>
                        c. {{ $sppd->category->name ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="no-col">4.</td>
                    <td class="label-col">Maksud Perjalanan Dinas</td>
                    <td style="text-align: justify;">{{ $sppd->purpose }}</td>
                </tr>
                <tr>
                    <td class="no-col">5.</td>
                    <td class="label-col">Alat angkutan yang dipergunakan</td>
                    <td>{{ $sppd->transport_name }}</td>
                </tr>
                <tr>
                    <td class="no-col">6.</td>
                    <td class="label-col">
                        a. Tempat Berangkat<br>
                        b. Tempat Tujuan
                    </td>
                    <td>
                        a. {{ $sppd->departure_place ?? 'Kendari' }}<br>
                        b. @foreach($sppd->destinations as $dest) {{ $dest->regency->name ?? '' }}@if(!$loop->last), @endif @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="no-col">7.</td>
                    <td class="label-col">
                        a. Lamanya Perjalanan Dinas<br>
                        b. Tanggal berangkat<br>
                        c. Tanggal harus kembali
                    </td>
                    <td>
                        a. {{ $pdfData['duration'] }} Hari<br>
                        b. {{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d F Y') }}<br>
                        c. {{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td class="no-col">8.</td>
                    <td class="label-col">Pengikut : Nama</td>
                    <td>
                        @if($is_main)
                            @foreach($sppd->followers as $f)
                                {{ $loop->iteration }}. {{ $f->user->name }}<br>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="no-col">9.</td>
                    <td class="label-col">
                        Pembebanan Anggaran<br>
                        a. Instansi<br>
                        b. Mata Anggaran
                    </td>
                    <td>
                        <br>
                        a. {{ $user->department->name }}<br>
                        b. {{ $sppd->budget->kode_rekening ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td class="no-col">10.</td>
                    <td class="label-col">Keterangan lain-lain</td>
                    <td>{{ $sppd->urgency ?? '-' }}</td>
                </tr>
            </table>

            <div class="signature-section">
                <div class="sig-box">
                    <div>Dikeluarkan di : Kendari</div>
                    <div>Tanggal : {{ \Carbon\Carbon::parse($sppd->created_at)->translatedFormat('d F Y') }}</div>
                    <div style="margin-top: 5px; font-weight: bold; text-transform: uppercase;">{{ $pdfData['approver_role'] }}</div>
                    <div style="height: 40px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">{{ $pdfData['approver_name'] }}</div>
                    <div>NIP. {{ $pdfData['approver_nip'] }}</div>
                </div>
            </div>
        </div>

        <!-- BACK PAGE (RIGHT COLUMN) -->
        <div class="right-column">
            <table class="back-table">
                <tr>
                    <td></td>
                    <td>
                        I. Berangkat dari : Kendari<br>
                        Pada Tanggal : {{ \Carbon\Carbon::parse($sppd->start_date)->translatedFormat('d-m-Y') }}<br>
                        KEPALA DINAS<br><br><br>
                        <b>{{ $pdfData['approver_name'] }}</b><br>
                        NIP. {{ $pdfData['approver_nip'] }}
                    </td>
                </tr>
                <tr>
                    <td>
                        II. Tiba di : {{ $sppd->destinations->first()->regency->name ?? '........' }}<br>
                        Pada Tanggal : ....................
                    </td>
                    <td>
                        Berangkat dari : {{ $sppd->destinations->first()->regency->name ?? '........' }}<br>
                        Ke : ....................<br>
                        Pada Tanggal : ....................
                    </td>
                </tr>
                <tr>
                    <td>III. Tiba di : ....................</td>
                    <td>Berangkat dari : ....................</td>
                </tr>
                <tr>
                    <td>IV. Tiba di : ....................</td>
                    <td>Berangkat dari : ....................</td>
                </tr>
                <tr>
                    <td>V. Tiba di : ....................</td>
                    <td>Berangkat dari : ....................</td>
                </tr>
                <tr>
                    <td>
                        VI. Tiba di : Kendari<br>
                        Pada Tanggal : {{ \Carbon\Carbon::parse($sppd->end_date)->translatedFormat('d-m-Y') }}<br><br>
                        Pejabat yang memberi perintah<br>
                        KEPALA DINAS<br><br><br>
                        <b>{{ $pdfData['approver_name'] }}</b>
                    </td>
                    <td>
                        Telah diperiksa dengan keterangan bahwa perjalanan tersebut di atas benar dilakukan...<br><br>
                        Pejabat yang memberi perintah<br>
                        KEPALA DINAS<br><br><br>
                        <b>{{ $pdfData['approver_name'] }}</b>
                    </td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>
</body>
</html>
