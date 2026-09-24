<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pembungkus tipis openspout untuk mengunduh satu tabel sebagai berkas .xlsx.
 *
 * Dipakai oleh menu Laporan. Data ditulis langsung ke output (streaming)
 * supaya tidak menumpuk di memori walau barisnya banyak.
 */
class ExcelWriter
{
    /**
     * @param  array<int, string>  $kolom
     * @param  iterable<array<int, mixed>>  $baris
     */
    public static function unduh(
        string $namaBerkas,
        string $judul,
        array $kolom,
        iterable $baris,
        ?string $keterangan = null,
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($judul, $kolom, $baris, $keterangan) {
                $writer = new Writer(new Options);
                $writer->openToFile('php://output');

                $gayaJudul = (new Style)->setFontBold()->setFontSize(14)->setFontColor('5B2A6E');
                $gayaKeterangan = (new Style)->setFontSize(10)->setFontColor('5B6B76');
                $gayaKepala = (new Style)
                    ->setFontBold()
                    ->setBackgroundColor('EEF6FA')
                    ->setCellAlignment(CellAlignment::CENTER);

                $writer->addRow(Row::fromValues([$judul], $gayaJudul));

                if ($keterangan) {
                    $writer->addRow(Row::fromValues([$keterangan], $gayaKeterangan));
                }

                $writer->addRow(Row::fromValues(['Dicetak '.tanggal_indo(now(), true)], $gayaKeterangan));
                $writer->addRow(Row::fromValues([]));
                $writer->addRow(Row::fromValues($kolom, $gayaKepala));

                foreach ($baris as $b) {
                    $writer->addRow(Row::fromValues(array_values((array) $b)));
                }

                $writer->close();
            },
            $namaBerkas,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache',
            ]
        );
    }
}
