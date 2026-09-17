<?php
/**
 * XLSXWriter — Lightweight single-file XLSX generator
 * Pure PHP implementation with built-in PKZIP archiver (Zero external dependencies)
 * Generates standards-compliant OpenXML (.xlsx) files compatible with Excel, LibreOffice, Google Sheets
 */
class XLSXWriter {
    protected $sheets = [];

    public function writeSheetHeader($sheet_name, $col_types, $col_options = []) {
        if (empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name] = ['rows' => [], 'freeze_rows' => 0];
        }
    }

    public function writeSheetRow($sheet_name, $row_data, $options = []) {
        if (empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name] = ['rows' => [], 'freeze_rows' => 0];
        }
        $this->sheets[$sheet_name]['rows'][] = [
            'data'    => $row_data,
            'options' => $options
        ];
    }

    public function setFreezeRows($sheet_name, $rows) {
        if (!empty($this->sheets[$sheet_name])) {
            $this->sheets[$sheet_name]['freeze_rows'] = (int)$rows;
        }
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
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        // Freeze pane if requested
        if (!empty($sheet['freeze_rows'])) {
            $freeze = (int)$sheet['freeze_rows'];
            $top_left = 'A' . ($freeze + 1);
            $xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0">';
            $xml .= '<pane ySplit="' . $freeze . '" topLeftCell="' . $top_left . '" activePane="bottomLeft" state="frozen"/>';
            $xml .= '<selection pane="bottomLeft" activeCell="' . $top_left . '" sqref="' . $top_left . '"/>';
            $xml .= '</sheetView></sheetViews>';
        } else {
            $xml .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>';
        }

        $xml .= '<sheetFormatPr defaultRowHeight="18"/>';
        $xml .= '<sheetData>';

        $row_idx = 1;
        foreach ($sheet['rows'] as $row_info) {
            $row_data  = $row_info['data'];
            $is_header = !empty($row_info['options']['bold']);
            $xml .= '<row r="' . $row_idx . '" spans="1:' . count($row_data) . '">';

            $col_idx = 0;
            foreach ($row_data as $val) {
                $cell_ref = $this->colLetter($col_idx) . $row_idx;
                $style    = $is_header ? ' s="1"' : '';

                if (is_numeric($val) && !is_string($val)) {
                    // Pure numeric value (e.g. integer or float score)
                    $xml .= '<c r="' . $cell_ref . '"' . $style . '><v>' . $val . '</v></c>';
                } else {
                    // String / text value (including numeric strings like student IDs)
                    $escaped = htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $xml .= '<c r="' . $cell_ref . '" t="inlineStr"' . $style . '><is><t>' . $escaped . '</t></is></c>';
                }
                $col_idx++;
            }
            $xml .= '</row>';
            $row_idx++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';
        return $xml;
    }

    protected function buildStylesXML() {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<fonts count="2">';
        $xml .= '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'; // 0: Normal
        $xml .= '<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'; // 1: Bold
        $xml .= '</fonts>';
        $xml .= '<fills count="2">';
        $xml .= '<fill><patternFill patternType="none"/></fill>';
        $xml .= '<fill><patternFill patternType="gray125"/></fill>';
        $xml .= '</fills>';
        $xml .= '<borders count="1">';
        $xml .= '<border><left/><right/><top/><bottom/><diagonal/></border>';
        $xml .= '</borders>';
        $xml .= '<cellStyleXfs count="1">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>';
        $xml .= '</cellStyleXfs>';
        $xml .= '<cellXfs count="2">';
        $xml .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'; // 0: Normal
        $xml .= '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'; // 1: Bold
        $xml .= '</cellXfs>';
        $xml .= '<cellStyles count="1">';
        $xml .= '<cellStyle name="Normal" xfId="0" builtinId="0"/>';
        $xml .= '</cellStyles>';
        $xml .= '</styleSheet>';
        return $xml;
    }

    protected function buildFilesArray() {
        $files = [];

        // 1. Build each worksheet XML first
        $sheet_idx = 1;
        $sheet_entries = [];
        foreach ($this->sheets as $name => $s) {
            // Clean sheet name (Excel limits to 31 chars and disallows: \ / ? * [ ] :)
            $safe_name = preg_replace('/[\\\\\\/\\?\\*\\[\\]\\:]/', '', (string)$name);
            if (mb_strlen($safe_name, 'UTF-8') > 31) {
                $safe_name = mb_substr($safe_name, 0, 31, 'UTF-8');
            }
            if (trim($safe_name) === '') {
                $safe_name = 'Sheet' . $sheet_idx;
            }

            $part_name = 'xl/worksheets/sheet' . $sheet_idx . '.xml';
            $files[$part_name] = $this->buildSheetXML($name);
            $sheet_entries[] = [
                'index' => $sheet_idx,
                'name'  => $safe_name,
                'rid'   => 'rId' . ($sheet_idx + 1) // rId1 is styles.xml, rId2+ are sheets
            ];
            $sheet_idx++;
        }

        // 2. [Content_Types].xml
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
        $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($sheet_entries as $entry) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $entry['index'] . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';
        $files['[Content_Types].xml'] = $ct;

        // 3. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rels .= '</Relationships>';
        $files['_rels/.rels'] = $rels;

        // 4. xl/_rels/workbook.xml.rels
        $wb_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wb_rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $wb_rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach ($sheet_entries as $entry) {
            $wb_rels .= '<Relationship Id="' . $entry['rid'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $entry['index'] . '.xml"/>';
        }
        $wb_rels .= '</Relationships>';
        $files['xl/_rels/workbook.xml.rels'] = $wb_rels;

        // 5. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $wb .= '<sheets>';
        foreach ($sheet_entries as $entry) {
            $wb .= '<sheet name="' . htmlspecialchars($entry['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" sheetId="' . $entry['index'] . '" r:id="' . $entry['rid'] . '"/>';
        }
        $wb .= '</sheets></workbook>';
        $files['xl/workbook.xml'] = $wb;

        // 6. xl/styles.xml
        $files['xl/styles.xml'] = $this->buildStylesXML();

        return $files;
    }

    /**
     * Pure-PHP Zip packager — creates standard PKZIP / OpenXML compatible archive
     */
    protected function buildZipData(array $files) {
        $data = '';
        $cd = '';
        $offset = 0;
        $entries = 0;

        // DOS Timestamp: 2026-01-01 00:00:00
        $dtime = 0;
        $ddate = 0x5C21;

        foreach ($files as $name => $content) {
            $name = str_replace('\\', '/', $name);
            $unc_len = strlen($content);
            $crc = crc32($content);

            if (function_exists('gzdeflate')) {
                $compressed = gzdeflate($content, 6);
                $comp_len = strlen($compressed);
                $method = 8; // Deflate
            } else {
                $compressed = $content;
                $comp_len = $unc_len;
                $method = 0; // Store
            }

            // Local File Header
            $lh = "\x50\x4b\x03\x04" .        // signature (PK\x03\x04)
                  "\x14\x00" .                // version needed to extract (2.0)
                  "\x00\x00" .                // general purpose bit flag
                  pack('v', $method) .        // compression method
                  pack('v', $dtime) .         // last mod time
                  pack('v', $ddate) .         // last mod date
                  pack('V', $crc) .           // crc-32
                  pack('V', $comp_len) .      // compressed size
                  pack('V', $unc_len) .       // uncompressed size
                  pack('v', strlen($name)) .  // file name length
                  pack('v', 0) .              // extra field length
                  $name;

            $data .= $lh . $compressed;

            // Central Directory Entry
            $cd .= "\x50\x4b\x01\x02" .       // signature (PK\x01\x02)
                   "\x14\x00" .               // version made by (2.0)
                   "\x14\x00" .               // version needed (2.0)
                   "\x00\x00" .               // bit flag
                   pack('v', $method) .       // compression method
                   pack('v', $dtime) .        // last mod time
                   pack('v', $ddate) .        // last mod date
                   pack('V', $crc) .          // crc-32
                   pack('V', $comp_len) .     // compressed size
                   pack('V', $unc_len) .      // uncompressed size
                   pack('v', strlen($name)) . // file name length
                   pack('v', 0) .             // extra field length
                   pack('v', 0) .             // file comment length
                   pack('v', 0) .             // disk number start
                   pack('v', 0) .             // internal file attributes
                   pack('V', 32) .            // external file attributes (archive)
                   pack('V', $offset) .       // relative offset of local header
                   $name;

            $offset = strlen($data);
            $entries++;
        }

        $cd_len = strlen($cd);
        $cd_offset = strlen($data);

        // End of Central Directory Record (EOCD)
        $eocd = "\x50\x4b\x05\x06" .          // signature (PK\x05\x06)
                pack('v', 0) .                // number of this disk
                pack('v', 0) .                // number of disk with central directory
                pack('v', $entries) .          // total entries on this disk
                pack('v', $entries) .          // total entries in central directory
                pack('V', $cd_len) .          // size of central directory
                pack('V', $cd_offset) .       // offset of start of central directory
                pack('v', 0);                 // zipfile comment length

        return $data . $cd . $eocd;
    }

    public function writeToString() {
        $files = $this->buildFilesArray();
        return $this->buildZipData($files);
    }

    public function writeToFile($filename) {
        $data = $this->writeToString();
        if (file_put_contents($filename, $data) === false) {
            throw new Exception("Cannot create file: $filename");
        }
    }
}
