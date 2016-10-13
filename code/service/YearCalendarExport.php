<?php

class YearCalendarExport
{
    const HEADER_A = 'Titel';
    const HEADER_B = 'Datum van';
    const HEADER_C = 'Tijd van';
    const HEADER_D = 'Datum tot en met';
    const HEADER_E = 'Tijd';
    const HEADER_F = 'Hele dag';
    const HEADER_G = 'Inhoud';
    const HEADER_H = 'Tag';

    const WHOLE_DAY_YES = 'Ja';
    const WHOLE_DAY_NO  = 'Nee';

    public static function create()
    {
        return new static();
    }

    public function generate()
    {
        $excel = new \PHPExcel\Spreadsheet();

        $sheet = $excel->getActiveSheet();

        $sheet->setCellValueByColumnAndRow(0, 1, static::HEADER_A);
        $sheet->setCellValueByColumnAndRow(1, 1, static::HEADER_B);
        $sheet->setCellValueByColumnAndRow(2, 1, static::HEADER_C);
        $sheet->setCellValueByColumnAndRow(3, 1, static::HEADER_D);
        $sheet->setCellValueByColumnAndRow(4, 1, static::HEADER_E);
        $sheet->setCellValueByColumnAndRow(5, 1, static::HEADER_F);
        $sheet->setCellValueByColumnAndRow(6, 1, static::HEADER_G);
        $sheet->setCellValueByColumnAndRow(7, 1, static::HEADER_H);

        foreach([
                    'A' => 40,
                    'B' => 10,
                    'C' => 5,
                    'D' => 10,
                    'E' => 5,
                    'F' => 7,
                    'G' => 4,
                    'H' => 50
                ] as $column => $size) {
            $sheet->getColumnDimension($column)
                ->setAutoSize(false)
                ->setWidth((float) $size + 0.83); // For some reason Excel eats up .83 of the columns width, so 40 would become 39.17
        }
        $agenda = Agenda::get()
            ->sort('From ASC');

        $row = 2;
        /** @var Agenda $item */
        foreach ($agenda as $item) {
            $from = $item->FromDateTime();
            $to   = $item->ToDateTime();

            $sheet->setCellValueExplicitByColumnAndRow(0, $row, $item->Title);
            $sheet->setCellValueExplicitByColumnAndRow(
                1,
                $row,
                $from->format('d-m-Y'),
                \PHPExcel\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValueExplicitByColumnAndRow(2, $row, !$item->WholeDay ? $from->format('H:i') : '');
            $sheet->setCellValueExplicitByColumnAndRow(
                3,
                $row,
                $to->format('d-m-Y'),
                \PHPExcel\Cell\DataType::TYPE_STRING
            );
            $sheet->setCellValueExplicitByColumnAndRow(4, $row, !$item->WholeDay ? $to->format('H:i') : '');
            $sheet->setCellValueExplicitByColumnAndRow(5, $row, $item->WholeDay ? static::WHOLE_DAY_YES : static::WHOLE_DAY_NO);
            $sheet->setCellValueExplicitByColumnAndRow(6, $row, $item->Content);
            $sheet->setCellValueExplicitByColumnAndRow(7, $row, implode(', ', $item->Tags()->map()->toArray()));
            ++$row;
        }

        $file = new PHPExcel\Writer\Excel2007($excel);

        $file->save('php://output');
    }
}