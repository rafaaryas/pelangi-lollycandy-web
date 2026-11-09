<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Creates a styled, multi-sheet Office Open XML workbook without an extra runtime dependency. */
final class BusinessWorkbook
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public static function render(string $from, string $to, array $sections, array $datasets): string
    {
        $sheets = [['name' => 'RINGKASAN LAPORAN', 'xml' => self::summary($from, $to, $sections)]];
        foreach ($datasets as $dataset) {
            $xml = self::detail($dataset, $from, $to);
            if ($xml !== null) $sheets[] = ['name' => $dataset['name'], 'xml' => $xml];
        }

        $types = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $relationships = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $files = [
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/styles.xml' => self::styles(),
        ];
        foreach ($sheets as $index => $sheet) {
            $id = $index + 1;
            $types .= '<Override PartName="/xl/worksheets/sheet'.$id.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbook .= '<sheet name="'.self::xml($sheet['name']).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
            $relationships .= '<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';
            $files['xl/worksheets/sheet'.$id.'.xml'] = $sheet['xml'];
        }
        $files['[Content_Types].xml'] = $types.'</Types>';
        $printAreas = '<definedNames>';
        foreach ($sheets as $index => $sheet) {
            preg_match('/<dimension ref="([A-Z]+\d+:[A-Z]+\d+)"/', $sheet['xml'], $match);
            [$start, $end] = explode(':', $match[1]);
            $absolute = fn (string $cell) => preg_replace_callback('/([A-Z]+)(\d+)/', fn (array $part) => '$'.$part[1].'$'.$part[2], $cell);
            $printAreas .= '<definedName name="_xlnm.Print_Area" localSheetId="'.$index.'">\''.self::xml($sheet['name']).'\'!'.$absolute($start).':'.$absolute($end).'</definedName>';
        }
        $files['xl/workbook.xml'] = $workbook.'</sheets>'.$printAreas.'</definedNames></workbook>';
        $files['xl/_rels/workbook.xml.rels'] = $relationships.'<Relationship Id="rId'.(count($sheets) + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

        return TabularExport::zip($files);
    }

    private static function summary(string $from, string $to, array $sections): string
    {
        $rows = self::row(1, [['PELANGI LOLLYCANDY', 'text', 1]])
            .self::row(2, [['LAPORAN OPERASIONAL BISNIS', 'text', 2]])
            .self::row(3, [['Periode: '.$from.' s.d. '.$to, 'text', 3]])
            .self::row(4, [['Dibuat: '.now('Asia/Jakarta')->format('d-m-Y H:i').' WIB', 'text', 3]]);
        $merges = ['A1:E1', 'A2:E2', 'A3:E3', 'A4:E4'];
        $number = 6;
        foreach ($sections as $title => $metrics) {
            $rows .= self::row($number, [[$title, 'text', 4]]);
            $merges[] = 'A'.$number.':E'.$number;
            $number++;
            foreach ($metrics as $label => [$value, $type]) {
                $cells = [[$label, 'text', 15], null, null, [$value, $type, $type === 'money' ? 17 : 16]];
                $rows .= self::row($number++, $cells);
            }
            $number++;
        }
        return self::sheet($rows, ['A' => 32, 'B' => 15, 'C' => 15, 'D' => 24, 'E' => 16], $merges, null, null, $number - 1);
    }

    private static function detail(array $dataset, string $from, string $to): ?string
    {
        $headers = $dataset['headers'];
        $types = $dataset['types'];
        $last = self::column(count($headers));
        $rows = self::row(1, [['PELANGI LOLLYCANDY', 'text', 1]])
            .self::row(2, [[mb_strtoupper($dataset['name']), 'text', 2]])
            .self::row(3, [['Periode transaksi: '.$from.' s.d. '.$to.'  |  Data master: kondisi saat laporan dibuat', 'text', 3]])
            .self::row(4, [['Dibuat: '.now('Asia/Jakarta')->format('d-m-Y H:i').' WIB', 'text', 3]]);
        $headerCells = array_map(fn ($header) => [$header, 'text', 5], $headers);
        $rows .= self::row(6, $headerCells);
        $count = 0;
        $sumCents = 0;
        $totalIndex = $dataset['total'] ?? null;
        foreach ($dataset['rows'] as $item) {
            if (++$count > 250000) throw new \RuntimeException('Laporan melebihi 250.000 baris per sheet. Persempit periode laporan.');
            $cells = [];
            foreach (array_values($item) as $index => $value) {
                $type = $types[$index] ?? 'text';
                $style = match ($type) {
                    'money' => $count % 2 ? 9 : 10,
                    'number' => $count % 2 ? 7 : 8,
                    'date' => $count % 2 ? 11 : 12,
                    default => $count % 2 ? 0 : 6,
                };
                $cells[] = [$value, $type, $style];
            }
            if ($totalIndex !== null) $sumCents += self::cents($item[$totalIndex] ?? 0);
            $rows .= self::row(6 + $count, $cells);
        }
        if ($count === 0) return null;
        $end = 6 + $count;
        if ($totalIndex !== null) {
            $totalCells = array_fill(0, count($headers), null);
            $totalCells[0] = ['TOTAL', 'text', 13];
            $totalCells[$totalIndex] = [self::fromCents($sumCents), 'money', 14];
            $rows .= self::row(++$end, $totalCells);
        }
        $widths = [];
        foreach ($headers as $index => $header) $widths[self::column($index + 1)] = min(34, max(16, mb_strlen($header) + 8));
        return self::sheet($rows, $widths, ['A1:'.$last.'1', 'A2:'.$last.'2', 'A3:'.$last.'3', 'A4:'.$last.'4'], 'A6:'.$last.(6 + $count), 'A7', $end);
    }

    private static function sheet(string $rows, array $widths, array $merges, ?string $filter, ?string $freeze, int $lastRow): string
    {
        $lastColumn = array_key_last($widths);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="'.self::NS.'"><sheetPr><pageSetUpPr fitToPage="1"/></sheetPr><dimension ref="A1:'.$lastColumn.$lastRow.'"/><sheetViews><sheetView showGridLines="0" workbookViewId="0">';
        if ($freeze) $xml .= '<pane ySplit="6" topLeftCell="'.$freeze.'" activePane="bottomLeft" state="frozen"/>';
        $xml .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="17"/><cols>';
        foreach ($widths as $letter => $width) {
            $column = self::columnNumber($letter);
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }
        $xml .= '</cols><sheetData>'.$rows.'</sheetData><mergeCells count="'.count($merges).'">';
        foreach ($merges as $merge) $xml .= '<mergeCell ref="'.$merge.'"/>';
        $xml .= '</mergeCells>';
        if ($filter) $xml .= '<autoFilter ref="'.$filter.'"/>';
        $xml .= '<printOptions horizontalCentered="1"/><pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/></worksheet>';
        return $xml;
    }

    private static function row(int $number, array $cells): string
    {
        $xml = '<row r="'.$number.'"'.(in_array($number, [1, 2, 6], true) ? ' ht="26" customHeight="1"' : '').'>';
        foreach ($cells as $index => $cell) {
            if ($cell === null || $cell[0] === null) continue;
            [$value, $type, $style] = $cell;
            $ref = self::column($index + 1).$number;
            if ($type === 'date') {
                $serial = CarbonImmutable::parse($value)->startOfDay()->diffInDays(CarbonImmutable::create(1899, 12, 30), false) * -1;
                $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.$serial.'</v></c>';
            } elseif (in_array($type, ['money', 'number'], true)) {
                $decimal = $type === 'money' ? self::fromCents(self::cents($value)) : rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
                $xml .= '<c r="'.$ref.'" s="'.$style.'"><v>'.($decimal === '' ? '0' : $decimal).'</v></c>';
            } else {
                $xml .= '<c r="'.$ref.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::xml((string) $value).'</t></is></c>';
            }
        }
        return $xml.'</row>';
    }

    private static function styles(): string
    {
        $xfs = [
            [0,0,0,0], [1,1,0,0], [2,0,0,0], [0,0,0,0], [3,2,0,0], [1,1,1,0],
            [0,3,1,0], [0,0,1,164], [0,3,1,164], [0,0,1,165], [0,3,1,165],
            [0,0,1,166], [0,3,1,166], [3,2,1,0], [3,2,1,165], [3,0,0,0],
            [3,0,0,164], [3,0,0,165],
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="'.self::NS.'"><numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0.###"/><numFmt numFmtId="165" formatCode="&quot;Rp&quot; #,##0.00"/><numFmt numFmtId="166" formatCode="dd mmm yyyy"/></numFmts><fonts count="4"><font><sz val="11"/><color rgb="FF263B59"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="18"/><color rgb="FF263B59"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF263B59"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFBC3456"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFE9EF"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF7F9FC"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border/><border><left/><right/><top/><bottom style="hair"><color rgb="FFDCE3EB"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="'.count($xfs).'">';
        foreach ($xfs as [$font, $fill, $border, $format]) {
            $xml .= '<xf numFmtId="'.$format.'" fontId="'.$font.'" fillId="'.$fill.'" borderId="'.$border.'" xfId="0" applyNumberFormat="'.($format ? 1 : 0).'"/>';
        }
        return $xml.'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value), ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function cents(mixed $value): int
    {
        $text = (string) $value;
        if (preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $text, $parts)) {
            return ($parts[1] === '-' ? -1 : 1) * ((int) $parts[2] * 100 + (int) str_pad($parts[3] ?? '', 2, '0'));
        }
        return (int) round((float) $value * 100);
    }

    private static function fromCents(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    private static function column(int $number): string
    {
        $name = '';
        while ($number > 0) { $number--; $name = chr(65 + $number % 26).$name; $number = intdiv($number, 26); }
        return $name;
    }

    private static function columnNumber(string $letter): int
    {
        $number = 0;
        foreach (str_split($letter) as $character) $number = $number * 26 + ord($character) - 64;
        return $number;
    }
}
