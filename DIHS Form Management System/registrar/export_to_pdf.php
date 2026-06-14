<?php
require_once '../vendor/autoload.php'; // Ensure dompdf is installed via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    if (empty($data)) {
        throw new Exception('No data provided for PDF export');
    }

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>School Form 1 (SF1)</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
            th { background-color: #f0f0f0; font-weight: bold; }
            .header { margin-bottom: 20px; }
            .header img { width: 100px; height: 100px; }
            .header p { font-size: 14px; font-weight: bold; }
            .header .info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }
            .header .info div { font-size: 12px; }
            .header .info .label { color: #555; }
            .header .info .value { font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="../images/KElogo.png" alt="KE Logo">
            <p>School Forms 1 (SF 1) School Registrar</p>
            <p style="font-style: italic; font-size: 12px;">(This replaces Form 1, Master List & STS Form 2-Family Background and Profile)</p>
            <div class="info">
                <div><span class="label">School ID</span><br><span class="value">107921120657</span></div>
                <div><span class="label">Region</span><br><span class="value">IV-A</span></div>
                <div><span class="label">Division</span><br><span class="value">107921120657</span></div>
                <div><span class="label">District</span><br><span class="value">107921120657</span></div>
                <div><span class="label">School Name</span><br><span class="value">DASMARIÑAS INTEGRATED HIGH SCHOOL</span></div>
                <div><span class="label">School Year</span><br><span class="value"></span></div>
                <div><span class="label">Grade Level</span><br><span class="value"></span></div>
                <div><span class="label">Section</span><br><span class="value"></span></div>
            </div>
        </div>
        <table>
            <tr>
                <th>LRN</th>
                <th>NAME (Last Name, First Name, Name Extension, Middle Name)</th>
                <th>Sex</th>
                <th>BIRTHDATE</th>
                <th>AGE</th>
                <th>Religious Affiliation</th>
                <th>House No./ Street/ Sitio/ Purok</th>
                <th>Barangay</th>
                <th>Municipality/ City</th>
                <th>Province</th>
                <th>Father\'s Name</th>
                <th>Mother\'s Maiden Name</th>
                <th>Name (Guardian)</th>
                <th>Relationship</th>
                <th>Contact Number</th>
                <th>Remarks</th>
            </tr>';

    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '
        </table>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $output = $dompdf->output();

    if (empty($output)) {
        throw new Exception('PDF generation failed: Empty output');
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="School_Form_1_SF1_' . date('Y-m-d_H-i-s') . '.pdf"');
    echo $output;
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>