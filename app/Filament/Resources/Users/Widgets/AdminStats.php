<?php

namespace App\Filament\Resources\Users\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;

class AdminStats extends BaseWidget
{
    protected function getColumns(): int
    {
        return count($this->getStats());
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Admin', User::where('role', 'admin')->count())
                ->description('Jumlah admin')
                ->icon('heroicon-o-user')
                ->color('success'),

            Stat::make('Super Admin', User::where('role', 'super_admin')->count())
                ->description('Level tertinggi')
                ->icon('heroicon-o-shield-check')
                ->color('danger'),
        ];
    }
}
