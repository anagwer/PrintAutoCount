<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Aktivitas Harian</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        th {border:1px solid #000;text-align: center; font-size: 9px; padding:0px 3px;}
        td {border:1px solid #000;text-align: left; font-size: 8px;height: 12px; padding:3px;}
        .text-right { text-align: right; }
    </style>
</head>
<body>

    @php
        use Carbon\Carbon;

        $today = Carbon::now()->locale('id');
        $day = $today->translatedFormat('l'); // Rabu
        $date = $today->translatedFormat('d F Y'); // 20 Agustus 2025

        $grandTotal = $rows->sum(function($r) {
            return floatval($r->OUTSTANDING);
        });

        $coverage = $coverage = $rows->pluck('AREA')
                    ->map(fn($a) => explode(',', $a)[0])
                    ->map(fn($a) => trim($a))
                    ->unique()
                    ->implode(', ');

        $salesmen = $rows->pluck('desc2')->unique()->map(function($d) {
            return explode(' ', $d)[0];
        })->implode(', ');
    @endphp

    <table style="border: 1px solid #000;margin-bottom:10px;">
        <tr style="border: none;">
            <th colspan="6" style="border: none;padding:3px 0px;"><strong>LAPORAN AKTIVITAS HARIAN SALESMAN</strong></th>
        </tr>
        <tr style="border: none;">
            <td style="border: none; width:25%;font-size: 9px;">
                <strong><u>SALESMAN</u></strong><br>
                TANGGAL/HARI TO : {{ $date }} / {{ $day }}<br>
                NAMA PERSONIL : {{ $salesmen }}<br>
                KM AWAL/AKHIR : <br>
                COVERAGE AREA : {{ $coverage }}<br>
            </td>
            <td style="border: none;width:15%;font-size: 9px;">
                <strong><u>NOTE</u></strong><br>
                REGISTER OUTLET : <br>
                CALL : <br>
                EFECTIVE CALL : <br>
                NOO : <br>
            </td>
            <td style="border: none;width:20%;font-size: 9px;">
                <strong><u>CREW</u></strong><br>
                TANGGAL DO : <br>
                NAMA PERSONIL : <br>
                NOPOL / JENIS KENDARAAN : <br>
                KM AWAL / KM AKHIR : <br>
            </td>
            <td style="border: none;width:15%;font-size: 9px;">
                <strong><u>BIAYA</u></strong><br>
                UM SALES : <br>
                BBM SALES : <br>
                PARKIR/RETRIBUSI : <br>
                LAIN2 : <br>
            </td>
            <td style="border: none;width:15%;font-size: 9px;">
                <strong><u></u></strong><br>
                UM CREW : <br>
                BBM DROP : <br>
                PARKIR/RETRIBUSI : <br>
                LAIN2 : <br>
            </td>
            <td style="border: none; vertical-align: top; width:20%;font-size: 9px;">
                <strong><u></u></strong><br>
                TOTAL BIAYA : <br>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="4">No</th>
                <th rowspan="4" style="width:100px">Company Name</th>
                <th rowspan="4">Type</th>
                <th rowspan="4">No Telp</th>
                <th rowspan="4" style="width:75px">Email Address</th>
                <th rowspan="4" style="width:30px">Sales Agent</th>
                <th rowspan="4" style="width:45px">Doc Date</th>
                <th rowspan="4" style="width:45px">Due Date</th>
                <th rowspan="4">Age</th>
                <th rowspan="4" style="width:50px;font-size:8px;">Local Outstanding</th>
                <th rowspan="3" colspan="2">Tagihan</th>
                <th rowspan="4" style="width:25px">
                R<br>N<br>P
                </th>

                <th colspan="24">ORDER</th>
            </tr>
            <tr>
                <th colspan="15">CREAM</th>
                <th colspan="7">LIQUID</th>
                <th colspan="2">REALISASI DROP</th>
            </tr>
            <tr>
                <th colspan="7">LB500</th>
                <th colspan="3">LB900</th>
                <th colspan="5">LB350</th>
                <th colspan="3">PLP DISWASH</th>
                <th colspan="4">MBR</th>
                <th rowspan="2" style="width:35px">CASH</th>
                <th rowspan="2" style="width:35px">CREDIT</th>
            </tr>
            <tr>
                <th>Tunai</th>
                <th>Barang</th>
                <th>B</th>
                <th>W</th>
                <th>K</th>
                <th>H</th>
                <th>U</th>
                <th>M</th>
                <th>S</th>

                <th>K</th>
                <th>W</th>
                <th>H</th>

                <th>W</th>
                <th>K</th>
                <th>H</th>
                <th>U</th>
                <th>M</th>

                <th>600ML</th>
                <th>320ML</th>
                <th>450ML</th>

                <th>MDSB</th>
                <th>MDSP</th>
                <th>450ML</th>
                <th>DRG</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 30; $i++)
                @php
                    $row = $rows[$i] ?? null;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->COMPANYNAME ?? '' }}</td>
                    <td>{{ $row->DEBTORTYPE ?? '' }}</td>
                    <td>{{ $row->Phone1 ?? $row->Phone2  ?? ''}}</td>
                    <td>{{ $row->EmailAddress ?? '' }}</td>
                    <td>
                        {{ $row && $row->desc2 ? explode(' ', $row->desc2)[0] : '' }}
                    </td>
                    <td>
                        {{ $row && $row->DocDate ? \Carbon\Carbon::parse($row->DocDate)->format('d-M-y') : '' }}
                    </td>
                    <td>
                        {{ $row && $row->DUEDATE ? \Carbon\Carbon::parse($row->DUEDATE)->format('d-M-y') : '' }}
                    </td>

                    <td class="text-right">{{ $row->AGE ?? '' }}</td>
                    <td class="text-right">{{ isset($row) ? number_format($row->OUTSTANDING, 0) : '' }}</td>
                    @for ($a = 0; $a < 27; $a++)
                    <td></td>
                    @endfor
                </tr>
            @endfor
            <tr>
                <th colspan="9">GRANDTOTAL</th>
                <th>{{ number_format($grandTotal, 0, ',', '.') }}</th>
                @for ($a = 0; $a < 27; $a++)
                    <td></td>
                @endfor
            </tr>

        </tbody>
    </table>

    <table style="margin-top:10px;">
        <tr>
            <td style="padding-bottom:25px">DISERAHKAN OLEH</td>
            <td style="padding-bottom:25px">DITERIMA OLEH</td>
            <td style="padding-bottom:25px">MENYETUJUI</td>
            <td style="padding-bottom:25px;padding-left:10px;border:none">KODE RNP:<br>
                1. TOKO TUTUP<br>
                2. PEMILIK TIDAK DITEMPAT
            </td>
            <td style="padding-bottom:25px;border:none"><br>
                3. STOCK MASIH ADA<br>
                4. TIDAK DIKUNJUNGI
            </td>
            <td style="padding-bottom:25px;border:none"><br>
                5. BANGKRUT<br>
                6. LAIN2
            </td>
            <td style="padding-bottom:25px">
                DISERAHKAN OLEH
            </td>
            <td style="padding-bottom:25px">
                DITERIMA OLEH
            </td>
        </tr>
    </table>
</body>
</html>
