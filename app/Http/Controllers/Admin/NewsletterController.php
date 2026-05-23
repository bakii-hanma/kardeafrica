<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($search = $request->get('search')) {
            $query->where('email', 'like', "%{$search}%");
        }
        if (in_array($status = $request->get('status'), ['active', 'inactive'])) {
            $query->where('is_active', $status === 'active');
        }
        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        switch ($request->get('sort', 'latest')) {
            case 'oldest': $query->oldest(); break;
            case 'email':  $query->orderBy('email', 'asc'); break;
            default:       $query->latest(); break;
        }

        $subscribers = $query->paginate(25)->withQueryString();

        $stats = [
            'total'    => NewsletterSubscriber::count(),
            'active'   => NewsletterSubscriber::where('is_active', true)->count(),
            'inactive' => NewsletterSubscriber::where('is_active', false)->count(),
            'last7'    => NewsletterSubscriber::where('subscribed_at', '>=', now()->subDays(7))->count(),
        ];

        $sources = NewsletterSubscriber::select('source')
            ->groupBy('source')
            ->pluck('source')
            ->all();

        return view('admin.newsletter.index', compact('subscribers', 'stats', 'sources'));
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();
        return back()->with('success', 'Abonné supprimé.');
    }

    public function toggle(NewsletterSubscriber $subscriber)
    {
        if ($subscriber->is_active) {
            $subscriber->unsubscribe();
            $msg = 'Abonné désinscrit.';
        } else {
            $subscriber->resubscribe();
            $msg = 'Abonné réactivé.';
        }
        return back()->with('success', $msg);
    }

    /**
     * Export Excel (.xlsx) de la liste filtree.
     */
    public function export(Request $request)
    {
        $query = NewsletterSubscriber::query();
        if ($search = $request->get('search')) $query->where('email', 'like', "%{$search}%");
        if (in_array($status = $request->get('status'), ['active', 'inactive'])) {
            $query->where('is_active', $status === 'active');
        }
        if ($source = $request->get('source')) $query->where('source', $source);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Abonnés Newsletter');

        // Header KardAfrica branded
        $sheet->setCellValue('A1', 'KardAfrica · Newsletter');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        $sheet->setCellValue('A2', 'Export du ' . now()->translatedFormat('d F Y à H:i'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // Column headers
        $headers = ['Email', 'Statut', 'Source', 'Locale', 'Inscrit le', 'Désinscrit le', 'Adresse IP'];
        $sheet->fromArray($headers, null, 'A4');

        $sheet->getStyle('A4:G4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '44A08D']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '3D9180']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Data rows
        $row = 5;
        $query->chunk(500, function ($chunk) use ($sheet, &$row) {
            foreach ($chunk as $sub) {
                $sheet->setCellValue("A{$row}", $sub->email);
                $sheet->setCellValue("B{$row}", $sub->is_active ? 'Actif' : 'Désinscrit');
                $sheet->setCellValue("C{$row}", $sub->source);
                $sheet->setCellValue("D{$row}", $sub->locale);
                $sheet->setCellValue("E{$row}", $sub->subscribed_at?->format('Y-m-d H:i:s'));
                $sheet->setCellValue("F{$row}", $sub->unsubscribed_at?->format('Y-m-d H:i:s'));
                $sheet->setCellValue("G{$row}", $sub->ip_address);

                // Statut color (column B)
                $statusColor = $sub->is_active ? '047857' : '64748B';
                $statusBg    = $sub->is_active ? 'D1FAE5' : 'F1F5F9';
                $sheet->getStyle("B{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusBg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Alternating rows
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:G{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FAFBFC');
                    // Re-apply status color (row striping ne doit pas tuer le badge)
                    $sheet->getStyle("B{$row}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($statusBg);
                }

                $row++;
            }
        });

        $lastRow = $row - 1;

        // Borders pour toute la zone data
        if ($lastRow >= 5) {
            $sheet->getStyle("A5:G{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                'font'    => ['size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Min widths pour eviter overlap apres autosize
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);

        // Freeze header
        $sheet->freezePane('A5');

        // Footer info
        $totalRow = $lastRow + 2;
        $sheet->setCellValue("A{$totalRow}", 'Total : ' . ($lastRow - 4) . ' abonné(s)');
        $sheet->mergeCells("A{$totalRow}:G{$totalRow}");
        $sheet->getStyle("A{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $filename = 'kardafrica-newsletter-' . now()->format('Y-m-d-His') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'public',
        ]);
    }
}
