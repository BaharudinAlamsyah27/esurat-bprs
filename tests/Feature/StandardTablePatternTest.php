<?php

use App\Enums\UserLevel;
use App\Filament\Pages\InstitutionSettings;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\ClassificationResource;
use App\Filament\Resources\ClassificationResource\Pages\EditClassification;
use App\Filament\Resources\UserResource;
use App\Models\Classification;
use App\Models\IncomingLetter;
use App\Models\User;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Livewire;

it('sorts classifications by code and keeps delete off the list', function (): void {
    $table = ClassificationResource::table(Table::make(Mockery::mock(HasTable::class)));
    $actionNames = array_map(fn ($action): string => $action->getName(), $table->getActions());

    expect($table->getDefaultSortColumn())->toBe('code')
        ->and($table->getDefaultSortDirection())->toBe('asc')
        ->and($actionNames)->toBe(['edit'])
        ->and($table->getEmptyStateActions())->toHaveCount(1)
        ->and($table->getColumns())->toHaveKey('code')
        ->and(__('m1.classification_list_intro'))->not->toBe('m1.classification_list_intro');
});

it('disables deletion and explains why a used classification cannot be removed', function (): void {
    $admin = User::factory()->create(['level' => UserLevel::Admin]);
    $classification = Classification::factory()->create(['code' => '100']);
    IncomingLetter::factory()->create([
        'classification_id' => $classification->getKey(),
        'registered_by' => $admin->getKey(),
    ]);
    $this->actingAs($admin);

    Livewire::test(EditClassification::class, ['record' => $classification->getRouteKey()])
        ->assertSee(__('m1.classification_delete_blocked', ['count' => 1]))
        ->assertActionDisabled('delete');

    expect($admin->can('delete', $classification))->toBeFalse();
});

it('hides master data navigation from regular users', function (): void {
    $this->actingAs(User::factory()->create(['level' => UserLevel::User]))
        ->get(route('filament.admin.pages.dashboard'))
        ->assertOk()
        ->assertDontSee('DATA MASTER');

    expect(ClassificationResource::canAccess())
        ->toBeFalse()
        ->and(UserResource::canAccess())->toBeFalse()
        ->and(ActivityLogResource::canAccess())->toBeFalse()
        ->and(InstitutionSettings::shouldRegisterNavigation())->toBeFalse();
});

it('sorts data master navigation in the standard order', function (): void {
    expect(ClassificationResource::getNavigationSort())->toBe(1)
        ->and(InstitutionSettings::getNavigationSort())->toBe(2)
        ->and(UserResource::getNavigationSort())->toBe(3)
        ->and(ActivityLogResource::getNavigationSort())->toBe(4);
});
