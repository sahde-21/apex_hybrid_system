<?php

namespace App\Services\Print;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class PrintService
{
    public const LAYOUT_A4 = 'a4';

    public const LAYOUT_THERMAL_80MM = 'thermal_80mm';

    /**
     * @return array<string, string>
     */
    public function availableLayouts(): array
    {
        return [
            self::LAYOUT_A4 => __('scf.print_a4'),
            self::LAYOUT_THERMAL_80MM => __('scf.print_thermal'),
        ];
    }

    /**
     * @return list<string>
     */
    public function layoutKeys(): array
    {
        return [
            self::LAYOUT_A4,
            self::LAYOUT_THERMAL_80MM,
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function render(string $modelClass, Model $record, string $layout = self::LAYOUT_A4): View
    {
        $module = class_basename($modelClass);
        $view = $layout === self::LAYOUT_THERMAL_80MM
            ? "print.thermal.{$this->viewSlug($module)}"
            : "print.a4.{$this->viewSlug($module)}";

        if (! view()->exists($view)) {
            $view = $layout === self::LAYOUT_THERMAL_80MM
                ? 'print.thermal.default'
                : 'print.a4.default';
        }

        return view($view, [
            'record' => $record,
            'layout' => $layout,
            'printedAt' => now(),
        ]);
    }

    protected function viewSlug(string $module): string
    {
        return str($module)->snake('-')->toString();
    }
}
