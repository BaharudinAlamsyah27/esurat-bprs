<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DispositionInstructionResource\Pages\CreateDispositionInstruction;
use App\Filament\Resources\DispositionInstructionResource\Pages\EditDispositionInstruction;
use App\Filament\Resources\DispositionInstructionResource\Pages\ListDispositionInstructions;
use App\Models\DispositionInstruction;

class DispositionInstructionResource extends MasterDataResource
{
    protected static ?string $model = DispositionInstruction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'LANJUTAN';

    public static function getNavigationLabel(): string
    {
        return __('m1.disposition_instructions');
    }

    public static function getModelLabel(): string
    {
        return __('m1.disposition_instruction');
    }

    public static function getPluralModelLabel(): string
    {
        return __('m1.disposition_instructions');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDispositionInstructions::route('/'),
            'create' => CreateDispositionInstruction::route('/create'),
            'edit' => EditDispositionInstruction::route('/{record}/edit'),
        ];
    }
}
