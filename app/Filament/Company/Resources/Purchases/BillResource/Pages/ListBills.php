<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Enums\Accounting\BillStatus;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Models\Accounting\Bill;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

class ListBills extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BillResource\Widgets\BillOverview::class,
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('All'),

            'outstanding' => Tab::make()
                ->label('Outstanding')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->outstanding();
                })
                ->badge(Bill::outstanding()->count()),

            'paid' => Tab::make()
                ->label('Paid')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', BillStatus::Paid);
                })
                ->badge(Bill::where('status', BillStatus::Paid)->count()),
        ];
    }
}
