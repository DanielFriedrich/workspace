<?php
if (!defined('SP_APP')) { exit; }

/** PDF-Grundlage (FPDF, freie Lizenz, siehe inc/lib/fpdf/LICENSE.txt). Wird nur bei Bedarf geladen. */
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', SP_ROOT . '/inc/lib/fpdf/font/');
}
require_once SP_ROOT . '/inc/lib/fpdf/fpdf.php';

class StagepoolPdf extends FPDF
{
    public $footerCols = array();

    public function Footer()
    {
        $this->SetY(-30);
        $this->SetDrawColor(220, 220, 228);
        $this->Line(20, $this->GetY(), 190, $this->GetY());
        $this->Ln(2);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(110, 110, 125);
        $y = $this->GetY();
        $w = 170 / max(1, count($this->footerCols));
        foreach ($this->footerCols as $i => $text) {
            $this->SetXY(20 + $i * $w, $y);
            $this->MultiCell($w - 4, 3.6, pdf_t($text), 0, 'L');
        }
        $this->SetXY(20, -10);
        $this->Cell(170, 4, pdf_t('Seite ' . $this->PageNo() . ' von {nb}'), 0, 0, 'R');
    }
}

