<?php

namespace App\Filament\Resources;

use App\Enums\Role as UserRole;
use App\Filament\Resources\RoleResource\Pages\CreateRole;
use App\Filament\Resources\RoleResource\Pages\EditRole;
use App\Filament\Resources\RoleResource\Pages\ListRoles;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role as SpatieRole;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = SpatieRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $modelLabel = 'rôle';

    protected static ?string $pluralModelLabel = 'rôles';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rôle')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('guard_name')
                            ->label('Guard')
                            ->options(['web' => 'web'])
                            ->default('web')
                            ->disabledOn('edit')
                            ->required(),
                    ]),
                Section::make('Permissions')
                    ->description('Associer les permissions à ce rôle')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->relationship('permissions', 'name')
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->label('Guard'),
                TextColumn::make('users_count')
                    ->label('Utilisateurs')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options(['web' => 'web']),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (SpatieRole $record): bool => $record->name === UserRole::SuperAdmin->value),
                DeleteAction::make()
                    ->hidden(fn (SpatieRole $record): bool => $record->name === UserRole::SuperAdmin->value)
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
