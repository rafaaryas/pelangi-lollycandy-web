<?php

namespace App\Support;


/** Small, dependency-free writers for operational exports. Rows are supplied lazily. */
final class TabularExport
{
    public static function csv(array $headers, iterable $rows): void
    {
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        self::csvRow($out, $headers);
        foreach ($rows as $row) self::csvRow($out, $row);
        fclose($out);
    }

    private static function csvRow($stream, array $cells): void
    {
        fputcsv($stream, array_map(static function ($value): string {
            $text = (string) ($value ?? '');
            if (is_int($value) || is_float($value)) return $text;
            // Spreadsheet programs interpret these prefixes as formulas, even in CSV.
            return preg_match('/^[\x00-\x20]*[=+\-@]/u', $text) ? "'".$text : $text;
        }, $cells), ';');
    }

    public static function xlsx(string $title, array $headers, iterable $rows, array $numeric): string
    {
        $sheet = fopen('php://temp/maxmemory:1048576', 'w+');
        fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews><cols>');
        foreach ($headers as $index => $heading) {
            $width = min(38, max(15, mb_strlen($heading) + 6));
            fwrite($sheet, '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>');
        }
        fwrite($sheet, '</cols><sheetData>');
        self::xlsxRow($sheet, $headers, 1, []);
        $number = 2;
        foreach ($rows as $row) {
            self::xlsxRow($sheet, $row, $number++, $numeric);
            if ($number > 50002) throw new \RuntimeException('Excel dibatasi 50.000 baris. Gunakan CSV untuk data yang lebih besar.');
        }
        fwrite($sheet, '</sheetData><autoFilter ref="A1:'.self::column(count($headers)).max(1, $number - 1).'"/></worksheet>');
        rewind($sheet);
        $sheetXml = stream_get_contents($sheet);
        fclose($sheet);
        $safeTitle = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/u', '', $title) ?: 'Data', 0, 31);
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.self::xml($safeTitle).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFECEFF5"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];
        return self::zip($files);
    }

    private static function xlsxRow($stream, array $cells, int $rowNumber, array $numeric): void
    {
        fwrite($stream, '<row r="'.$rowNumber.'">');
        foreach (array_values($cells) as $index => $value) {
            $cell = self::column($index + 1).$rowNumber;
            if ($rowNumber > 1 && in_array($index, $numeric, true) && is_numeric($value)) {
                fwrite($stream, '<c r="'.$cell.'" s="2"><v>'.(float) $value.'</v></c>');
            } else {
                fwrite($stream, '<c r="'.$cell.'" t="inlineStr"'.($rowNumber === 1 ? ' s="1"' : '').'><is><t xml:space="preserve">'.self::xml((string) ($value ?? '')).'</t></is></c>');
            }
        }
        fwrite($stream, '</row>');
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value), ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function column(int $number): string
    {
        $name = '';
        while ($number > 0) { $number--; $name = chr(65 + $number % 26).$name; $number = intdiv($number, 26); }
        return $name;
    }

    private static function zip(array $files): string
    {
        $data = ''; $directory = ''; $offset = 0;
        foreach ($files as $name => $content) {
            $compressed = gzdeflate($content, 6);
            $crc = crc32($content);
            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, 0, 0, $crc, strlen($compressed), strlen($content), strlen($name), 0).$name.$compressed;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, 0, 0, $crc, strlen($compressed), strlen($content), strlen($name), 0, 0, 0, 0, 0, $offset).$name;
            $data .= $local;
            $offset += strlen($local);
        }
        return $data.$directory.pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($directory), $offset, 0);
    }

    public static function pdf(string $title, string $period, array $headers, iterable $rows, array $numeric): string
    {
        $pageWidth = count($headers) > 5 ? 842 : 595;
        $pageHeight = count($headers) > 5 ? 595 : 842;
        $margin = 34;
        $usable = $pageWidth - $margin * 2;
        $weights = array_map(fn ($heading) => max(12, min(28, mb_strlen($heading) + 5)), $headers);
        $sum = array_sum($weights);
        $widths = array_map(fn ($weight) => $usable * $weight / $sum, $weights);
        $pages = []; $commands = ''; $y = 0; $count = 0;
        $newPage = function () use (&$commands, &$y, &$pages, $pageHeight, $margin, $title, $period, $headers, $widths): void {
            if ($commands !== '') $pages[] = $commands;
            $commands = '';
            $y = $pageHeight - $margin;
            $commands .= self::pdfText($margin, $y, 15, $title, true); $y -= 20;
            $commands .= self::pdfText($margin, $y, 9, $period.'  |  Dibuat '.now()->format('d-m-Y H:i'), false); $y -= 28;
            $x = $margin;
            foreach ($headers as $index => $heading) {
                $commands .= self::pdfText($x + 3, $y, 8, self::clip($heading, $widths[$index]), true);
                $x += $widths[$index];
            }
            $commands .= '0.75 w '.($margin).' '.($y - 7).' m '.($margin + array_sum($widths)).' '.($y - 7)." l S\n";
            $y -= 22;
        };
        $newPage();
        foreach ($rows as $row) {
            if (++$count > 10000) throw new \RuntimeException('PDF dibatasi 10.000 baris. Gunakan CSV untuk data yang lebih besar.');
            if ($y < $margin + 25) $newPage();
            $x = $margin;
            foreach (array_values($row) as $index => $value) {
                $label = in_array($index, $numeric, true) && is_numeric($value) ? number_format((float) $value, fmod((float) $value, 1) ? 3 : 0, ',', '.') : (string) ($value ?? '');
                $commands .= self::pdfText($x + 3, $y, 7.5, self::clip($label, $widths[$index] ?? 60), false);
                $x += $widths[$index] ?? 60;
            }
            $y -= 18;
        }
        $pages[] = $commands;
        return self::pdfDocument($pages, $pageWidth, $pageHeight);
    }

    private static function clip(string $text, float $width): string
    {
        return mb_strimwidth($text, 0, max(5, (int) ($width / 4.4)), '…');
    }

    private static function pdfText(float $x, float $y, float $size, string $value, bool $bold): string
    {
        $latin = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: '';
        $latin = str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $latin);
        return 'BT /'.($bold ? 'F2' : 'F1').' '.$size.' Tf '.round($x, 2).' '.round($y, 2).' Td ('.$latin.") Tj ET\n";
    }

    private static function pdfDocument(array $pages, int $width, int $height): string
    {
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => '', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $kids = [];
        foreach ($pages as $index => $commands) {
            $pageId = 5 + $index * 2; $streamId = $pageId + 1;
            $kids[] = $pageId.' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$width.' '.$height.'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($commands).' >>' . "\nstream\n".$commands."endstream";
        }
        $objects[2] = '<< /Type /Pages /Count '.count($pages).' /Kids ['.implode(' ', $kids).'] >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n"; $offsets = [0];
        foreach ($objects as $id => $body) { $offsets[$id] = strlen($pdf); $pdf .= $id." 0 obj\n".$body."\nendobj\n"; }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        return $pdf.'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF";
    }
}
