<?php

namespace App\Support;

/**
 * Genera un .xlsx (Excel 2007+) de una sola hoja sin extensiones de PHP:
 * el ZIP se arma a mano (entradas sin comprimir) y las celdas van como texto en línea.
 * Encabezado en negrita, fila fija al desplazarse y números como números (suman en Excel).
 */
class Xlsx
{
    /**
     * @param  array<int, string>              $encabezados
     * @param  iterable<array<int, mixed>>     $filas
     */
    public static function generar(string $hoja, array $encabezados, iterable $filas): string
    {
        $xmlFilas = self::fila(1, $encabezados, true);
        $n = 1;
        foreach ($filas as $fila) {
            $xmlFilas .= self::fila(++$n, array_values($fila), false);
        }
        $anchos = '';
        foreach ($encabezados as $i => $titulo) {
            $anchos .= sprintf('<col min="%d" max="%d" width="%d" customWidth="1"/>', $i + 1, $i + 1, max(10, min(40, mb_strlen($titulo) + 6)));
        }

        $hojaXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . "<cols>{$anchos}</cols><sheetData>{$xmlFilas}</sheetData></worksheet>";

        $nombreHoja = self::esc(mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $hoja), 0, 31));

        return self::zip([
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . "<sheets><sheet name=\"{$nombreHoja}\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>",
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
                . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
                . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F6FEB"/><bgColor indexed="64"/></patternFill></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
                . '</styleSheet>',
            'xl/worksheets/sheet1.xml' => $hojaXml,
        ]);
    }

    private static function fila(int $numero, array $valores, bool $encabezado): string
    {
        $celdas = '';
        foreach ($valores as $i => $valor) {
            $ref = self::columna($i) . $numero;
            $estilo = $encabezado ? ' s="1"' : '';
            if (! $encabezado && (is_int($valor) || is_float($valor))) {
                $celdas .= "<c r=\"{$ref}\"{$estilo}><v>{$valor}</v></c>";
            } elseif ($valor !== null && $valor !== '') {
                $celdas .= "<c r=\"{$ref}\" t=\"inlineStr\"{$estilo}><is><t xml:space=\"preserve\">" . self::esc((string) $valor) . '</t></is></c>';
            }
        }
        return "<row r=\"{$numero}\">{$celdas}</row>";
    }

    /** 0 → A, 25 → Z, 26 → AA */
    private static function columna(int $i): string
    {
        $letras = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $letras = chr(65 + ($i - 1) % 26) . $letras;
        }
        return $letras;
    }

    private static function esc(string $texto): string
    {
        // Quita caracteres de control que invalidan el XML
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto) ?? '';
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** ZIP con entradas "stored" (sin compresión), suficiente para OOXML. */
    private static function zip(array $archivos): string
    {
        $datos = '';
        $central = '';
        [$hora, $fecha] = self::fechaDos();

        foreach ($archivos as $nombre => $contenido) {
            $crc = crc32($contenido);
            $tam = strlen($contenido);
            $offset = strlen($datos);
            // Bit 11 = nombres en UTF-8
            $cabecera = pack('vvvvvVVVvv', 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam, strlen($nombre), 0);
            $datos .= "PK\x03\x04" . $cabecera . $nombre . $contenido;
            $central .= "PK\x01\x02" . pack('vvvvvvVVVvvvvvVV', 20, 20, 0x0800, 0, $hora, $fecha, $crc, $tam, $tam,
                strlen($nombre), 0, 0, 0, 0, 0, $offset) . $nombre;
        }

        $fin = "PK\x05\x06" . pack('vvvvVVv', 0, 0, count($archivos), count($archivos), strlen($central), strlen($datos), 0);
        return $datos . $central . $fin;
    }

    private static function fechaDos(): array
    {
        $t = getdate();
        return [
            ($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2),
            (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'],
        ];
    }
}
