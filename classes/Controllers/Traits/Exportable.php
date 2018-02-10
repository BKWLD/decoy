<?php

namespace Bkwld\Decoy\Controllers\Traits;

// Deps
use Carbon\Carbon;
use Decoy;
use League\Csv\Writer;
use SplTempFileObject;

/**
 * Add functionality for exporting to CSV
 */
trait Exportable
{
    /**
     * Export a CSV
     * https://csv.thephpleague.com/9.0/connections/output/
     *
     * @return void
     */
    public function csv()
    {
        $items = $this->makeCsvQuery()->get();
        if ($items->isEmpty()) abort(404);
        $csv = $this->makeCsv($items);
        return response($csv->getContent())->withHeaders([
            'Content-Encoding' => 'none',
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"',
                $this->makeCsvFileTitle()),
            'Content-Description' => 'File Transfer',
        ]);
    }

    /**
     * Make the CSV query, removing eager loading of images because that can
     * create a passing query that breaks some databases (like sql server)
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function makeCsvQuery()
    {
        return $this->makeIndexQuery()
            ->withoutGlobalScopes(['decoy.images'])
            ->exporting();
    }

    /**
     * Build the CSV object from a collection of models
     * https://csv.thephpleague.com/9.0/writer/
     *
     * @param  Collection $items
     * @return Writer
     */
    protected function makeCsv($items)
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne($this->getCsvHeaderNames($items));
        $items->each(function($item) use ($csv) {
            $row = $item->forExport();
            if (is_object($row) && method_exists($row, 'toArray')) {
                $row = $row->toArray();
            }
            $csv->insertOne($row);
        });
        return $csv;
    }

    /**
     * Make the header row
     *
     * @param  Collection $items
     * @return array
     */
    protected function getCsvHeaderNames($items)
    {
        return $items->first()->makeCsvHeaderNames();
    }

    /**
     * Make the title of the CSV
     *
     * @return string
     */
    protected function makeCsvFileTitle()
    {
        return vsprintf('%s %s as of %s at %s.csv', [
            Decoy::site(),
            $this->title(),
            Carbon::now()->format('n.j.y'),
            Carbon::now()->format('g:i A'),
        ]);
    }


}
