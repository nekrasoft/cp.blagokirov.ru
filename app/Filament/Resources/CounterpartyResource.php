<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterpartyResource\Pages\CreateCounterparty;
use App\Filament\Resources\CounterpartyResource\Pages\EditCounterparty;
use App\Filament\Resources\CounterpartyResource\Pages\ListCounterparties;
use App\Models\Counterparty;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;
use UnitEnum;

class CounterpartyResource extends Resource
{
    protected static ?string $model = Counterparty::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Контрагенты';

    protected static ?string $modelLabel = 'Контрагент';

    protected static ?string $pluralModelLabel = 'Контрагенты';

    protected static string|UnitEnum|null $navigationGroup = 'Карта бункеров';

    protected static array $hasColumnCache = [];

    public static function form(Schema $schema): Schema
    {
        $components = [
            TextInput::make('short_name')
                ->label('Краткое название')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label('Полное название')
                ->required()
                ->maxLength(255),
        ];

        if (static::hasColumn('invoice_schedule')) {
            $components[] = TextInput::make('invoice_schedule')
                ->label('График счетов')
                ->maxLength(255);
        }

        if (static::hasColumn('operation_type')) {
            $components[] = TextInput::make('operation_type')
                ->label('Тип операции')
                ->maxLength(255);
        }

        if (static::hasColumn('status')) {
            $components[] = TextInput::make('status')
                ->label('Статус')
                ->default('active')
                ->required()
                ->maxLength(64);
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),
            TextColumn::make('short_name')
                ->label('Краткое название')
                ->searchable()
                ->sortable(),
            TextColumn::make('name')
                ->label('Полное название')
                ->searchable()
                ->sortable(),
        ];

        if (static::hasColumn('invoice_schedule')) {
            $columns[] = TextColumn::make('invoice_schedule')
                ->label('График счетов')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('operation_type')) {
            $columns[] = TextColumn::make('operation_type')
                ->label('Тип операции')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('status')) {
            $columns[] = TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->color(fn (?string $state): string => $state === 'active' ? 'success' : 'gray')
                ->sortable();
        }

        $filters = [];

        if (static::hasColumn('status')) {
            $filters[] = SelectFilter::make('status')
                ->label('Статус')
                ->options([
                    'active' => 'active',
                    'inactive' => 'inactive',
                ]);
        }

        return $table
            ->defaultSort('short_name')
            ->columns($columns)
            ->filters($filters)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCounterparties::route('/'),
            'create' => CreateCounterparty::route('/create'),
            'edit' => EditCounterparty::route('/{record}/edit'),
        ];
    }

    protected static function hasColumn(string $column): bool
    {
        if (array_key_exists($column, static::$hasColumnCache)) {
            return static::$hasColumnCache[$column];
        }

        try {
            static::$hasColumnCache[$column] = SchemaFacade::hasColumn('counterparties', $column);
        } catch (Throwable) {
            static::$hasColumnCache[$column] = false;
        }

        return static::$hasColumnCache[$column];
    }
}
