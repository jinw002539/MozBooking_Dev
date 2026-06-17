<?php
/**
 * pdf_helper.php — gerador mínimo de PDF em PHP puro, sem dependências externas.
 *
 * Não usa nenhuma biblioteca (TCPDF/FPDF/Dompdf) porque o ambiente onde isto
 * corre pode não ter acesso ao Composer/Packagist. Serve apenas para gerar
 * comprovativos de uma página com texto e algumas formas simples — não é
 * um motor de PDF genérico.
 *
 * Suporta acentuação portuguesa convertendo o texto de UTF-8 para
 * Windows-1252 (CP1252), que é o que a tabela WinAnsiEncoding dos tipos
 * de letra base do PDF (Helvetica) já entende nativamente.
 */

class SimplePdf
{
    private float $width;
    private float $height;
    /** @var array<int,string> operadores do content stream, na ordem em que são desenhados */
    private array $ops = [];

    public function __construct(float $width = 595.28, float $height = 841.89)
    {
        $this->width  = $width;
        $this->height = $height;
    }

    public function width(): float  { return $this->width; }
    public function height(): float { return $this->height; }

    /** Converte texto UTF-8 para CP1252 e escapa parênteses/backslashes para uso em strings PDF */
    private function pdfText(string $text): string
    {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = $text; // fallback — não deveria acontecer com texto PT normal
        }
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
    }

    /** Desenha um rectângulo. $fill = true preenche, false só contorno. Cor em RGB 0-255 */
    public function rect(float $x, float $y, float $w, float $h, array $rgb, bool $fill = true): void
    {
        [$r, $g, $b] = $rgb;
        $op = $fill ? 're f' : 're S';
        $colorOp = $fill ? 'rg' : 'RG';
        $this->ops[] = sprintf('%.3F %.3F %.3F %s', $r / 255, $g / 255, $b / 255, $colorOp);
        $this->ops[] = sprintf('%.2F %.2F %.2F %.2F %s', $x, $y, $w, $h, $op);
    }

    /** Desenha uma linha simples (separador) */
    public function line(float $x1, float $y1, float $x2, float $y2, array $rgb, float $widthPt = 1): void
    {
        [$r, $g, $b] = $rgb;
        $this->ops[] = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        $this->ops[] = sprintf('%.2F w', $widthPt);
        $this->ops[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    /**
     * Escreve uma linha de texto.
     * $font: 'F1' = Helvetica normal, 'F2' = Helvetica-Bold
     * $align: 'left' | 'center' | 'right' (centra/alinha dentro de $boxWidth a partir de $x)
     */
    public function text(
        float $x,
        float $y,
        string $text,
        string $font = 'F1',
        float $size = 11,
        array $rgb = [0, 0, 0],
        string $align = 'left',
        float $boxWidth = 0
    ): void {
        $escaped = $this->pdfText($text);
        if ($align !== 'left' && $boxWidth > 0) {
            $approxWidth = $this->approxTextWidth($text, $font, $size);
            if ($align === 'center') {
                $x += max(0, ($boxWidth - $approxWidth) / 2);
            } elseif ($align === 'right') {
                $x += max(0, $boxWidth - $approxWidth);
            }
        }
        [$r, $g, $b] = $rgb;
        $this->ops[] = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ops[] = 'BT';
        $this->ops[] = sprintf('/%s %.2F Tf', $font, $size);
        $this->ops[] = sprintf('%.2F %.2F Td', $x, $y);
        $this->ops[] = sprintf('(%s) Tj', $escaped);
        $this->ops[] = 'ET';
    }

    /** Estimativa simples da largura do texto (larguras médias por tipo de letra Helvetica) */
    public function approxTextWidth(string $text, string $font = 'F1', float $size = 11): float
    {
        $avgCharWidth = ($font === 'F2') ? 0.56 : 0.50; // fracção da altura da fonte
        $converted    = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        $len          = ($converted !== false) ? strlen($converted) : strlen($text);
        return $len * $size * $avgCharWidth;
    }

    /** Gera os bytes finais do ficheiro PDF */
    public function output(): string
    {
        $contentStream = implode("\n", $this->ops);

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = sprintf(
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>",
            $this->width,
            $this->height
        );
        $objects[4] = "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        $pdf      = "%PDF-1.4\n";
        $offsets  = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count      = count($objects) + 1;
        $pdf .= "xref\n0 $count\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }
}
