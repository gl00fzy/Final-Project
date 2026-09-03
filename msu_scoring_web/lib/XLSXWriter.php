<?php
/**
 * XLSXWriter — Lightweight single-file XLSX generator
 * Based on the public domain XLSXWriter by mk-j (simplified)
 */
class XLSXWriter {
    protected $sheets = [];
    protected $shared_strings = [];
    protected $shared_string_lookup = [];

    public function writeSheetHeader($sheet_name, $col_types, $col_options = []) {
        if (empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name] = ['rows'=>[], 'col_types'=>$col_types, 'col_options'=>$col_options, 'freeze_rows'=>0];
        }
        $this->sheets[$sheet_name]['col_types'] = $col_types;
        $this->sheets[$sheet_name]['col_options'] = $col_options;
    }

    public function writeSheetRow($sheet_name, $row_data, $options = []) {
        if (empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name] = ['rows'=>[], 'col_types'=>[], 'col_options'=>[], 'freeze_rows'=>0];
        }
        $this->sheets[$sheet_name]['rows'][] = ['data'=>$row_data, 'options'=>$options];
    }

    public function setFreezeRows($sheet_name, $rows) {
        if (!empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name]['freeze_rows'] = $rows;
        }
    }

    protected function addSharedString($str) {
        $str = (string)$str;
        if (!isset($this->shared_string_lookup[$str])) {
            $idx = count($this->shared_strings);
            $this->shared_strings[] = $str;
            $this->shared_string_lookup[$str] = $idx;
        }
        return $this->shared_string_lookup[$str];
    }

    protected function colLetter($col_idx) {
        $letter = '';
        $col_idx++;
        while ($col_idx > 0) {
            $col_idx--;
            $letter = chr(65 + ($col_idx % 26)) . $letter;
            $col_idx = (int)($col_idx / 26);
        }
        return $letter;
    }

    protected function buildSheetXML($sheet_name) {
        $sheet = $this->sheets[$sheet_name];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        // Freeze pane
        if (!empty($sheet['freeze_rows'])) {
            $xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0">';
            $xml .= '<pane ySplit="' . $sheet['freeze_rows'] . '" topLeftCell="A' . ($sheet['freeze_rows']+1) . '" activePane="bottomLeft" state="frozen"/>';
            $xml .= '</sheetView></sheetViews>';
        }

        $xml .= '<sheetData>';
        $row_idx = 1;
        foreach ($sheet['rows'] as $row_info) {
            $row_data = $row_info['data'];
            $is_header = !empty($row_info['options']['bold']);
            $xml .= '<row r="' . $row_idx . '">';
            $col_idx = 0;
            foreach ($row_data as $val) {
                $cell_ref = $this->colLetter($col_idx) . $row_idx;
                $str_idx = $this->addSharedString((string)$val);
                $style = $is_header ? ' s="1"' : '';
                $xml .= '<c r="' . $cell_ref . '" t="s"' . $style . '><v>' . $str_idx . '</v></c>';
                $col_idx++;
            }
            $xml .= '</row>';
            $row_idx++;
        }
        $xml .= '</sheetData>';
        $xml .= '</worksheet>';
        return $xml;
    }

    protected function buildSharedStringsXML() {
        $count = count($this->shared_strings);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">';
        foreach ($this->shared_strings as $s) {
            $xml .= '<si><t>' . htmlspecialchars($s, ENT_XML1, 'UTF-8') . '</t></si>';
        }
        $xml .= '</sst>';
        return $xml;
    }

    protected function buildStylesXML() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<fonts count="2">';
        $xml .= '<font><sz val="11"/><name val="Calibri"/></font>'; // style 0: normal
        $xml .= '<font><b/><sz val="11"/><name val="Calibri"/></font>'; // style 1: bold
        $xml .= '</fonts>';
        $xml .= '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>';
        $xml .= '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>';
        $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $xml .= '<cellXfs count="2">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';  // 0: normal
        $xml .= '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>';  // 1: bold
        $xml .= '</cellXfs>';
        $xml .= '</styleSheet>';
        return $xml;
    }

    public function writeToFile($filename) {
        $zip = new ZipArchive();
        if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create file: $filename");
        }

        // [Content_Types].xml
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
        $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $ct .= '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>';
        $sheet_idx = 1;
        foreach ($this->sheets as $name => $s) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $sheet_idx . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheet_idx++;
        }
        $ct .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $ct);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rels .= '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/_rels/workbook.xml.rels
        $wb_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $wb_rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $wb_rels .= '<Relationship Id="rId0" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $wb_rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $sheet_idx = 2;
        foreach ($this->sheets as $name => $s) {
            $wb_rels .= '<Relationship Id="rId' . $sheet_idx . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($sheet_idx-1) . '.xml"/>';
            $sheet_idx++;
        }
        $wb_rels .= '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wb_rels);

        // xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $wb .= '<sheets>';
        $sheet_idx = 2;
        foreach ($this->sheets as $name => $s) {
            $wb .= '<sheet name="' . htmlspecialchars($name, ENT_XML1, 'UTF-8') . '" sheetId="' . ($sheet_idx-1) . '" r:id="rId' . $sheet_idx . '"/>';
            $sheet_idx++;
        }
        $wb .= '</sheets></workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // xl/styles.xml
        $zip->addFromString('xl/styles.xml', $this->buildStylesXML());

        // xl/sharedStrings.xml
        $zip->addFromString('xl/sharedStrings.xml', $this->buildSharedStringsXML());

        // xl/worksheets/sheet*.xml
        $sheet_idx = 1;
        foreach ($this->sheets as $name => $s) {
            $zip->addFromString('xl/worksheets/sheet' . $sheet_idx . '.xml', $this->buildSheetXML($name));
            $sheet_idx++;
        }

        $zip->close();
    }

    public function writeToString() {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $this->writeToFile($tmp);
        $data = file_get_contents($tmp);
        unlink($tmp);
        return $data;
    }
}
