<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;

class ReportSalesConfig extends Model
{
    /**
     * @var string
     */
    protected $table = 'report_sales_config';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'general_sales_goal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'general_sales_goal' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        $config = self::query()->first();

        if ($config === null) {
            $config = self::query()->create(['general_sales_goal' => 0]);
        }

        return $config;
    }
}
