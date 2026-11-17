<?php

namespace App\Support;

use App\Exceptions\BusinessConflictException;
use ZipArchive;

/**
 * Ghi một bảng dữ liệu ra tệp .xlsx (Office Open XML) bằng đúng ZipArchive có sẵn của PHP — không cần thư viện ngoài
 * (maatwebsite/excel hay phpoffice/phpspreadsheet). Đủ cho báo cáo dạng bảng của UC05.04: một trang tính, chữ và số,
 * hàng tiêu đề và hàng tổng in đậm, cột rộng vừa phải. Cần extension `zip` (Docker image và CI đã có).
 *
 * Số được ghi dạng số thật (Excel cộng / sắp xếp được), chữ ghi dạng inlineStr nên không cần bảng chuỗi chung.
 */
final class SimpleXlsx
{
    /**
     * @param  list<string>  $header  tiêu đề cột
     * @param  list<list<string|int|float|null>>  $rows  các dòng dữ liệu (cùng số cột với $header)
     * @param  list<string|int|float|null>|null  $footer  dòng tổng cộng (in đậm), tùy chọn
     * @param  list<float>  $widths  độ rộng từng cột (ký tự), tùy chọn
     * @return string nội dung nhị phân của tệp .xlsx
     */
    public static function build(string $sheetName, array $header, array $rows, ?array $footer = null, array $widths = []): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new BusinessConflictException('Máy chủ chưa bật extension zip của PHP nên không tạo được tệp Excel.');
        }

        $sheetName = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/u', ' ', $sheetName) ?? 'Sheet1', 0, 31);

        $xmlRows = [];
        $xmlRows[] = self::row(1, $header, 1); // style 1 = đậm + nền xám
        $line = 2;
        foreach ($rows as $row) {
            $xmlRows[] = self::row($line++, $row, 0);
        }
        if ($footer !== null) {
            $xmlRows[] = self::row($line, $footer, 2); // style 2 = đậm
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="'.$n.'" max="'.$n.'" width="'.$w.'" customWidth="1"/>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .($cols !== '' ? '<cols>'.$cols.'</cols>' : '')
            .'<sheetData>'.implode('', $xmlRows).'</sheetData></worksheet>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEEF0F7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="'.self::esc($sheetName).'" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => $styles,
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new BusinessConflictException('Không tạo được tệp Excel tạm.');
        }
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }

    /** @param  list<string|int|float|null>  $cells */
    private static function row(int $rowNumber, array $cells, int $style): string
    {
        $out = '<row r="'.$rowNumber.'">';
        foreach (array_values($cells) as $i => $value) {
            $ref = self::column($i).$rowNumber;
            $s = $style > 0 ? ' s="'.$style.'"' : '';

            if ($value === null || $value === '') {
                $out .= '<c r="'.$ref.'"'.$s.'/>';
            } elseif (is_int($value) || is_float($value)) {
                $out .= '<c r="'.$ref.'"'.$s.'><v>'.$value.'</v></c>';
            } else {
                $out .= '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $value).'</t></is></c>';
            }
        }

        return $out.'</row>';
    }

    /** 0 -> A, 25 -> Z, 26 -> AA */
    private static function column(int $index): string
    {
        $name = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $name = chr(65 + ($n - 1) % 26).$name;
        }

        return $name;
    }

    private static function esc(string $text): string
    {
        // Bỏ ký tự điều khiển không hợp lệ trong XML 1.0 rồi escape
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
