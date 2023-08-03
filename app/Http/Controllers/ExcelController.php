<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\WriterFactory;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelController extends Controller
{
    public function readExcel(Request $request)
    {
        $file = $request->file('excel');
    
        // Check if a file is uploaded
        if ($file) {
            $path = $file->getRealPath();
    
            // Load the Excel file
            $spreadsheet = IOFactory::load($path);
    
            // Create a new Xlsx Writer
            $writer = new Xlsx($spreadsheet);
    
            // Set the response headers for Excel file download
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="excel_file.xlsx"',
            ];
    
            // Save the Spreadsheet to a temporary file and return as a response
            ob_start();
            $writer->save('php://output');
            $excelContent = ob_get_clean();
    
            return response($excelContent, 200, $headers);
        }
    
        // No file uploaded
        return response()->json(['error' => 'No file uploaded.'], 400);
    }
}
