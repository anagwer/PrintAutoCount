<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Traits\SetReponses;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportController extends BaseController
{
    use SetReponses;

    private function getTargetDbFromToken(Request $request): string
    {
        $tokenAbilities = $request->user()?->currentAccessToken()?->abilities ?? [];

        foreach ($tokenAbilities as $ability) {
            if (str_starts_with($ability, 'target_db:')) {
                return str_replace('target_db:', '', $ability);
            }
        }

        return 'mysql';
    }

    public function get(Request $request)
    {
        $request->validate([
            'fromdate'   => 'required|string',
            'todate'     => 'required|string',
            'areacodes'  => 'required|array',
            'areacodes.*'=> 'string',
        ]);

        $from  = addslashes($request->input('fromdate'));
        $to    = addslashes($request->input('todate'));
        $areas = $request->input('areacodes', []);

        $areasList = implode("','", array_map('addslashes', $areas));

        $targetDb = $this->getTargetDbFromToken($request);

        $sql = "
            SELECT
                D.AccNo,
                D.AREACODE,
                Z.DESCRIPTION AS AREA,
                D.COMPANYNAME,
                R.DocDate,
                ISNULL(D.DEBTORTYPE, '-') AS DEBTORTYPE,
                CASE
                    WHEN D.DisplayTerm = 'C.O.D.' THEN 'C'
                    WHEN D.DisplayTerm = 'Net 30 days' THEN '30'
                    WHEN D.DisplayTerm = 'Net 28 days' THEN '28'
                    WHEN D.DisplayTerm = 'Net 14 days' THEN '14'
                    ELSE '-'
                END AS T,
                ISNULL(R.OUTSTANDING, 0.00) AS OUTSTANDING,
                CASE
                    WHEN DATEDIFF(DAY, R.DUEDATE, GETDATE()) < 0 THEN 0
                    ELSE DATEDIFF(DAY, R.DUEDATE, GETDATE())
                END AS AGE,
                ISNULL(A.AVGL3M, 0.00) AS AVGL3M,
                ISNULL(B.ORDERNOW, 0.00) AS ORDERNOW,
                C.paydays
            FROM DEBTOR D
            INNER JOIN AREA Z ON Z.AREACODE = D.AREACODE
            LEFT JOIN (
                SELECT SOURCEKEY, MIN(DUEDATE) AS DUEDATE, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
                FROM ARINVOICE
                WHERE OUTSTANDING > 0
                GROUP BY SOURCEKEY, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
            ) R ON R.DEBTORCODE = D.ACCNO
            LEFT JOIN IV I ON I.DEBTORCODE = D.ACCNO AND I.DOCKEY = R.SOURCEKEY
            LEFT JOIN (
                SELECT I.DEBTORCODE, SUM(L.QTY) / 3 AS AVGL3M
                FROM IVDTL L
                INNER JOIN IV I ON I.DOCKEY = L.DOCKEY
                WHERE CONVERT(VARCHAR(12), I.DOCDATE, 112) BETWEEN '$from' AND '$to'
                GROUP BY I.DEBTORCODE
            ) A ON A.DEBTORCODE = I.DEBTORCODE
            LEFT JOIN (
                SELECT I.DEBTORCODE, SUM(L.QTY) AS ORDERNOW
                FROM IVDTL L
                INNER JOIN IV I ON I.DOCKEY = L.DOCKEY
                WHERE MONTH(I.DOCDATE) = MONTH(GETDATE())
                GROUP BY I.DEBTORCODE
            ) B ON B.DEBTORCODE = A.DEBTORCODE
            INNER JOIN (
                SELECT
                    a.DebtorCode,
                    CONVERT(INT, ROUND(AVG(CAST(DATEDIFF(DAY, i.DocDate, a.DocDate) AS DECIMAL)), 0)) AS paydays
                FROM ARPaymentKnockOff k
                INNER JOIN ARInvoice i ON i.DocKey = k.KnockOffDocKey
                INNER JOIN ARPayment a ON a.DocKey = k.DocKey
                GROUP BY a.DebtorCode
            ) C ON C.DEBTORCODE = D.AccNo
            WHERE
            R.OUTSTANDING > 0
            AND CONVERT(VARCHAR(12), R.DocDate, 112) BETWEEN '$from' AND '$to'
            AND D.AreaCode IN ('$areasList')
            ORDER BY D.ACCNO ASC, AVGL3M DESC, ORDERNOW DESC
        ";

        $data = collect(DB::connection($targetDb)->select($sql));

        return self::success($data, false);
    }

    public function DebtorReport(Request $request)
    {
        $request->validate([
            'fromdate'   => 'required|string',
            'todate'     => 'required|string',
            'areacodes'  => 'required|array',
            'areacodes.*'=> 'string',
        ]);

        $from = addslashes($request->input('fromdate'));
        $to   = addslashes($request->input('todate'));
        $areas = $request->input('areacodes', []);

        $areasList = implode("','", array_map('addslashes', $areas));

        $targetDb = $this->getTargetDbFromToken($request);

        $sql = "
                SELECT
                    D.AccNo,
                    D.AREACODE,
                    Z.DESCRIPTION AS AREA,
                    Z.DESC2 AS desc2,
                    D.COMPANYNAME,
                    D.Phone1,
                    D.Phone2,
                    D.EmailAddress,
                    R.DocDate,
                    R.DUEDATE,
                    ISNULL(D.DEBTORTYPE, '-') AS DEBTORTYPE,
                    CASE
                        WHEN D.DisplayTerm = 'C.O.D.' THEN 'C'
                        WHEN D.DisplayTerm = 'Net 30 days' THEN '30'
                        WHEN D.DisplayTerm = 'Net 28 days' THEN '28'
                        WHEN D.DisplayTerm = 'Net 14 days' THEN '14'
                        ELSE '-'
                    END AS T,
                    ISNULL(R.OUTSTANDING, 0.00) AS OUTSTANDING,
                    CASE
                        WHEN DATEDIFF(DAY, R.DUEDATE, GETDATE()) < 0 THEN 0
                        ELSE DATEDIFF(DAY, R.DUEDATE, GETDATE())
                    END AS AGE,
                    ISNULL(A.AVGL3M, 0.00) AS AVGL3M,
                    ISNULL(B.ORDERNOW, 0.00) AS ORDERNOW,
                    C.paydays
                FROM DEBTOR D
                INNER JOIN AREA Z ON Z.AREACODE = D.AREACODE
                LEFT JOIN (
                    SELECT SOURCEKEY, MIN(DUEDATE) AS DUEDATE, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
                    FROM ARINVOICE
                    WHERE OUTSTANDING > 0
                    GROUP BY SOURCEKEY, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
                ) R ON R.DEBTORCODE = D.ACCNO
                LEFT JOIN IV I ON I.DEBTORCODE = D.ACCNO AND I.DOCKEY = R.SOURCEKEY
                LEFT JOIN (
                    SELECT I.DEBTORCODE, SUM(L.QTY) / 3 AS AVGL3M
                    FROM IVDTL L
                    INNER JOIN IV I ON I.DOCKEY = L.DOCKEY
                    WHERE CONVERT(VARCHAR(12), I.DOCDATE, 112) BETWEEN '$from' AND '$to'
                    GROUP BY I.DEBTORCODE
                ) A ON A.DEBTORCODE = I.DEBTORCODE
                LEFT JOIN (
                    SELECT I.DEBTORCODE, SUM(L.QTY) AS ORDERNOW
                    FROM IVDTL L
                    INNER JOIN IV I ON I.DOCKEY = L.DOCKEY
                    WHERE MONTH(I.DOCDATE) = MONTH(GETDATE())
                    GROUP BY I.DEBTORCODE
                ) B ON B.DEBTORCODE = A.DEBTORCODE
                INNER JOIN (
                    SELECT
                        a.DebtorCode,
                        CONVERT(INT, ROUND(AVG(CAST(DATEDIFF(DAY, i.DocDate, a.DocDate) AS DECIMAL)), 0)) AS paydays
                    FROM ARPaymentKnockOff k
                    INNER JOIN ARInvoice i ON i.DocKey = k.KnockOffDocKey
                    INNER JOIN ARPayment a ON a.DocKey = k.DocKey
                    GROUP BY a.DebtorCode
                ) C ON C.DEBTORCODE = D.AccNo
                WHERE
                R.OUTSTANDING > 0
                AND CONVERT(VARCHAR(12), R.DocDate, 112) BETWEEN '$from' AND '$to'
                AND D.AreaCode IN ('$areasList')
                ORDER BY D.ACCNO ASC, AVGL3M DESC, ORDERNOW DESC
            ";

        $data = collect(DB::connection($targetDb)->select($sql));

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [330,210],
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5
        ]);

        $html = view('reports.debtors', [
            'rows' => $data,
            'from' => $from,
            'to'   => $to,
            'area' => $areas
        ])->render();

        $pdf->WriteHTML($html);

        return response($pdf->Output('', 'S'))
            ->header('Content-Type', 'application/pdf');
    }

    public function DebtorReportExcel(Request $request)
    {
        $request->validate([
            'fromdate'   => 'required|string',
            'todate'     => 'required|string',
            'areacodes'  => 'required|array',
            'areacodes.*'=> 'string',
        ]);

        try {
            $from = addslashes($request->input('fromdate'));
            $to   = addslashes($request->input('todate'));
            $areas = $request->input('areacodes', []);
            $areasList = implode("','", array_map('addslashes', $areas));

            $targetDb = $this->getTargetDbFromToken($request);

            $sql = "
                SELECT
                    D.AccNo,
                    D.AREACODE,
                    Z.DESCRIPTION AS AREA,
                    Z.DESC2 AS desc2,
                    D.COMPANYNAME,
                    D.Phone1,
                    D.Phone2,
                    D.EmailAddress,
                    R.DocDate,
                    R.DUEDATE,
                    ISNULL(D.DEBTORTYPE, '-') AS DEBTORTYPE,
                    ISNULL(R.OUTSTANDING, 0.00) AS OUTSTANDING,
                    CASE
                        WHEN DATEDIFF(DAY, R.DUEDATE, GETDATE()) < 0 THEN 0
                        ELSE DATEDIFF(DAY, R.DUEDATE, GETDATE())
                    END AS AGE
                FROM DEBTOR D
                INNER JOIN AREA Z ON Z.AREACODE = D.AREACODE
                LEFT JOIN (
                    SELECT SOURCEKEY, MIN(DUEDATE) AS DUEDATE, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
                    FROM ARINVOICE
                    WHERE OUTSTANDING > 0
                    GROUP BY SOURCEKEY, OUTSTANDING, DOCNO, DEBTORCODE, DocDate
                ) R ON R.DEBTORCODE = D.ACCNO
                WHERE
                R.OUTSTANDING > 0
                AND CONVERT(VARCHAR(12), R.DocDate, 112) BETWEEN '$from' AND '$to'
                AND D.AreaCode IN ('$areasList')
                ORDER BY D.ACCNO ASC
            ";

            $rows = collect(DB::connection($targetDb)->select($sql));

            // ========== PREPARE INFO ==========
            $today = Carbon::now()->locale('id');
            $day = $today->translatedFormat('l');
            $date = $today->translatedFormat('d F Y');
            $grandTotal = $rows->sum(fn($r) => floatval($r->OUTSTANDING));
            $coverage = $rows->pluck('AREA')->map(fn($a) => explode(',', $a)[0])->map(fn($a) => trim($a))->unique()->implode(', ');
            $salesmen = $rows->pluck('desc2')->unique()->map(fn($d) => explode(' ', $d)[0])->implode(', ');

            // ========== Generate Excel ==========
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman')->setSize(9);

            // Judul
            $sheet->mergeCells("A1:AH1")->setCellValue("A1", "LAPORAN AKTIVITAS HARIAN SALESMAN");
            $sheet->getStyle("A1")->getFont()->setBold(true);
            $sheet->getStyle("A1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // ====== INFORMASI ======
            $sheet->mergeCells("A3:D3")->setCellValue("A3", "SALESMAN");
            $sheet->getStyle("A3")->getFont()->setBold(true)->setUnderline(true);
            $sheet->setCellValue("A4", "TANGGAL/HARI TO : $date / $day");
            $sheet->setCellValue("A5", "NAMA PERSONIL : $salesmen");
            $sheet->setCellValue("A6", "KM AWAL/AKHIR : ");
            $sheet->setCellValue("A7", "COVERAGE AREA : $coverage");

            $sheet->setCellValue("E3", "NOTE");
            $sheet->getStyle("E3")->getFont()->setBold(true)->setUnderline(true);
            $sheet->setCellValue("E4", "REGISTER OUTLET : ");
            $sheet->setCellValue("E5", "CALL : ");
            $sheet->setCellValue("E6", "EFECTIVE CALL : ");
            $sheet->setCellValue("E7", "NOO : ");

            $sheet->setCellValue("J3", "CREW");
            $sheet->getStyle("J3")->getFont()->setBold(true)->setUnderline(true);
            $sheet->setCellValue("J4", "TANGGAL DO : ");
            $sheet->setCellValue("J5", "NAMA PERSONIL : ");
            $sheet->setCellValue("J6", "NOPOL / JENIS KENDARAAN : ");
            $sheet->setCellValue("J7", "KM AWAL / KM AKHIR : ");

            $sheet->setCellValue("R3", "BIAYA");
            $sheet->getStyle("R3")->getFont()->setBold(true)->setUnderline(true);
            $sheet->setCellValue("R4", "UM SALES : ");
            $sheet->setCellValue("R5", "BBM SALES : ");
            $sheet->setCellValue("R6", "PARKIR/RETRIBUSI : ");
            $sheet->setCellValue("R7", "LAIN2 : ");

            $sheet->setCellValue("AA4", "UM CREW : ");
            $sheet->setCellValue("AA5", "BBM DROP : ");
            $sheet->setCellValue("AA6", "PARKIR/RETRIBUSI : ");
            $sheet->setCellValue("AA7", "LAIN2 : ");
            $sheet->setCellValue("AG4", "TOTAL BIAYA : ");

            // ====== HEADER UTAMA ======
            $rowHeader = 9;
            $sheet->mergeCells("A{$rowHeader}:A" . ($rowHeader+3))->setCellValue("A{$rowHeader}", "No");
            $sheet->mergeCells("B{$rowHeader}:B" . ($rowHeader+3))->setCellValue("B{$rowHeader}", "Company Name");
            $sheet->mergeCells("C{$rowHeader}:C" . ($rowHeader+3))->setCellValue("C{$rowHeader}", "Type");
            $sheet->mergeCells("D{$rowHeader}:D" . ($rowHeader+3))->setCellValue("D{$rowHeader}", "No Telp");
            $sheet->mergeCells("E{$rowHeader}:E" . ($rowHeader+3))->setCellValue("E{$rowHeader}", "Email Address");
            $sheet->mergeCells("F{$rowHeader}:F" . ($rowHeader+3))->setCellValue("F{$rowHeader}", "Sales Agent");
            $sheet->mergeCells("G{$rowHeader}:G" . ($rowHeader+3))->setCellValue("G{$rowHeader}", "Doc Date");
            $sheet->mergeCells("H{$rowHeader}:H" . ($rowHeader+3))->setCellValue("H{$rowHeader}", "Due Date");
            $sheet->mergeCells("I{$rowHeader}:I" . ($rowHeader+3))->setCellValue("I{$rowHeader}", "Age");
            $sheet->mergeCells("J{$rowHeader}:J" . ($rowHeader+3))->setCellValue("J{$rowHeader}", "Local Outstanding");
            $sheet->mergeCells("K{$rowHeader}:L" . ($rowHeader+2))->setCellValue("K{$rowHeader}", "Tagihan");
            $sheet->mergeCells("M{$rowHeader}:M" . ($rowHeader+3))->setCellValue("M{$rowHeader}", "R\nN\nP");
            $sheet->mergeCells("N{$rowHeader}:AK{$rowHeader}")->setCellValue("N{$rowHeader}", "ORDER");

            // Level 2
            $sheet->mergeCells("N".($rowHeader+1).":AB".($rowHeader+1))->setCellValue("N".($rowHeader+1), "CREAM");
            $sheet->mergeCells("AC".($rowHeader+1).":AI".($rowHeader+1))->setCellValue("AC".($rowHeader+1), "LIQUID");
            $sheet->mergeCells("AJ".($rowHeader+1).":AK".($rowHeader+2))->setCellValue("AJ".($rowHeader+1), "REALISASI DROP");

            $sheet->setCellValue("K12", "Tunai");
            $sheet->setCellValue("L12", "Barang");

            // Level 3
            $sheet->mergeCells("N".($rowHeader+2).":T".($rowHeader+2))->setCellValue("N".($rowHeader+2), "LB500");
            $sheet->mergeCells("U".($rowHeader+2).":W".($rowHeader+2))->setCellValue("U".($rowHeader+2), "LB900");
            $sheet->mergeCells("X".($rowHeader+2).":AB".($rowHeader+2))->setCellValue("X".($rowHeader+2), "LB350");
            $sheet->mergeCells("AC".($rowHeader+2).":AE".($rowHeader+2))->setCellValue("AC".($rowHeader+2), "PLP DISWASH");
            $sheet->mergeCells("AF".($rowHeader+2).":AI".($rowHeader+2))->setCellValue("AF".($rowHeader+2), "MBR");


            // Level 4 detail
            $detailRow = $rowHeader+3;
            $headers4 = ["N"=>"B","O"=>"W","P"=>"K","Q"=>"H","R"=>"U","S"=>"M","T"=>"S",
                        "U"=>"K","V"=>"W","W"=>"H",
                        "X"=>"W","Y"=>"K","Z"=>"H","AA"=>"U","AB"=>"M",
                        "AC"=>"600ML","AD"=>"320ML","AE"=>"450ML",
                        "AF"=>"MDSB","AG"=>"MDSP","AH"=>"450ML","AI"=>"DRG"];
            foreach($headers4 as $col=>$val){
                $sheet->setCellValue("{$col}{$detailRow}",$val);
            }


            $sheet->setCellValue("AJ12", "CASH");
            $sheet->setCellValue("AK12", "KREDIT");

            // Style Header
            $sheet->getStyle("A{$rowHeader}:AK{$detailRow}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle("A{$rowHeader}:AK{$detailRow}")
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("A{$rowHeader}:AK{$detailRow}")->getFont()->setBold(true);

            // ====== ISI DATA ======
            $rowNum = $detailRow+1;
            for ($i=0;$i<30;$i++){
                $r = $rows[$i] ?? null;
                $sheet->setCellValue("A$rowNum", $i+1);
                $sheet->setCellValue("B$rowNum", $r->COMPANYNAME ?? '');

                $sheet->getStyle("B$rowNum")->getAlignment()->setWrapText(true);
                $sheet->getStyle("B$rowNum")->getAlignment()->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
                );
                $sheet->setCellValue("C$rowNum", $r->DEBTORTYPE ?? '');
                $sheet->setCellValue("D$rowNum", $r->Phone1 ?? ($r->Phone2 ?? ''));
                $sheet->setCellValue("E$rowNum", $r->EmailAddress ?? '');
                $sheet->setCellValue("F$rowNum", $r && $r->desc2 ? explode(' ', $r->desc2)[0] : '');
                $sheet->setCellValue("G$rowNum", $r && $r->DocDate ? Carbon::parse($r->DocDate)->format('d-M-y') : '');
                $sheet->setCellValue("H$rowNum", $r && $r->DUEDATE ? Carbon::parse($r->DUEDATE)->format('d-M-y') : '');
                $sheet->setCellValue("I$rowNum", $r->AGE ?? '');
                $sheet->setCellValue("J$rowNum", $r ? $r->OUTSTANDING : '');

                $sheet->getStyle("J$rowNum")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("A$rowNum:AK$rowNum")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                $rowNum++;
            }

            // ====== GRANDTOTAL ======
            $sheet->mergeCells("A{$rowNum}:I{$rowNum}");
            $sheet->setCellValue("A{$rowNum}", "GRANDTOTAL");
            $sheet->getStyle("A{$rowNum}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue("J{$rowNum}", $grandTotal);
            $sheet->getStyle("J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$rowNum}:AK{$rowNum}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowNum}:AK{$rowNum}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $rowNum+=2;

            // ====== TANDA TANGAN ======
            $sheet->mergeCells("A45:C48")->setCellValue("A45", "  DISERAHKAN OLEH");
            $sheet->getStyle("A45:C48")->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("A45")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $sheet->mergeCells("D45:E48")->setCellValue("D45", "  DISERAHKAN OLEH");
            $sheet->getStyle("D45:E48")->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("D45")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $sheet->mergeCells("F45:H48")->setCellValue("F45", "  DISERAHKAN OLEH");
            $sheet->getStyle("F45:H48")->getBorders()->getOutline()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getStyle("F45")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
            $sheet->mergeCells("J45:K45")->setCellValue("J45"," KODE RNP:");
            $sheet->mergeCells("J46:K46")->setCellValue("J46","   1. TOKO TUTUP");
            $sheet->mergeCells("J47:K47")->setCellValue("J47","   2. PEMILIK TIDAK DITEMPAT");

            $rowNum++;
            $sheet->mergeCells("N46:R46")->setCellValue("N46","3. STOCK MASIH ADA");
            $sheet->mergeCells("N47:R47")->setCellValue("N47","4. TIDAK DIKUNJUNGI");

            $rowNum++;
            $sheet->mergeCells("U46:AB46")->setCellValue("U46","5. BANGKRUT");
            $sheet->mergeCells("U47:AB47")->setCellValue("U47","6. LAIN2");

            $rowNum+=2;
            $sheet->mergeCells("AC45:AG48")->setCellValue("AC45","DISERAHKAN OLEH");
            $sheet->getStyle("AC45:AG48")->getBorders()->getOutline()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $sheet->getStyle("AC45")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

            $sheet->mergeCells("AH45:AK48")->setCellValue("AH45", "DITERIMA OLEH");
            $sheet->getStyle("AH45:AK48")->getBorders()->getOutline()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $sheet->getStyle("AH45")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT)
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

            //ukuran
            $sheet->getColumnDimension('A')->setWidth(4);   // No
            $sheet->getColumnDimension('B')->setWidth(30);  // Company Name
            $sheet->getColumnDimension('C')->setWidth(7);   // Type
            $sheet->getColumnDimension('D')->setWidth(15);  // No Telp
            $sheet->getColumnDimension('E')->setWidth(20);  // Email Address
            $sheet->getColumnDimension('F')->setWidth(11);  // Sales Agent
            $sheet->getColumnDimension('G')->setWidth(10);  // Doc Date
            $sheet->getColumnDimension('H')->setWidth(10);  // Due Date
            $sheet->getColumnDimension('I')->setWidth(5);   // Age
            $sheet->getColumnDimension('J')->setWidth(12);  // Local Outstanding
            $sheet->getColumnDimension('K')->setWidth(10);  // Tagihan Tunai
            $sheet->getColumnDimension('L')->setWidth(10);  // Tagihan Barang
            $sheet->getColumnDimension('M')->setWidth(7);   // RNP

            // ORDER - CREAM
            $sheet->getColumnDimension('N')->setWidth(5);   // B
            $sheet->getColumnDimension('O')->setWidth(5);   // W
            $sheet->getColumnDimension('P')->setWidth(5);   // K
            $sheet->getColumnDimension('Q')->setWidth(5);   // H
            $sheet->getColumnDimension('R')->setWidth(5);   // U
            $sheet->getColumnDimension('S')->setWidth(5);   // M
            $sheet->getColumnDimension('T')->setWidth(5);   // LB900-M
            $sheet->getColumnDimension('U')->setWidth(5);   // LB900-S
            $sheet->getColumnDimension('V')->setWidth(5);   // LB900-K
            $sheet->getColumnDimension('W')->setWidth(5);   // LB350-W
            $sheet->getColumnDimension('X')->setWidth(5);   // LB350-K
            $sheet->getColumnDimension('Y')->setWidth(5);   // LB350-H
            $sheet->getColumnDimension('Z')->setWidth(5);   // LB350-U
            $sheet->getColumnDimension('AA')->setWidth(5);  // LB350-M
            $sheet->getColumnDimension('AB')->setWidth(5);  // 600ML

            // ORDER - LIQUID
            $sheet->getColumnDimension('AC')->setWidth(8);  // 320ML
            $sheet->getColumnDimension('AD')->setWidth(8);  // 450ML

            // ORDER - MBR
            $sheet->getColumnDimension('AE')->setWidth(8);  // MDSB
            $sheet->getColumnDimension('AF')->setWidth(8);  // MDSP
            $sheet->getColumnDimension('AG')->setWidth(8);  // 450ML
            $sheet->getColumnDimension('AH')->setWidth(8);  // DRG

            // ORDER - REALISASI DROP
            $sheet->getColumnDimension('AI')->setWidth(8);  // CASH
            $sheet->getColumnDimension('AJ')->setWidth(10);  // CREDIT

            // Sampai kolom terakhir
            $sheet->getColumnDimension('AK')->setWidth(10);

            $fileName = "LAHReport_" . date('Ymd_His') . ".xlsx";
            $path = storage_path("app/public/reports/$fileName");
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            return response()->streamDownload(function() use ($writer) {
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (Exception $e) {
            return self::error($e->getMessage(), 500);
        }
    }
}
