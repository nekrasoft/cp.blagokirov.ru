<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CounterpartyResource\Pages\CreateCounterparty;
use App\Filament\Resources\CounterpartyResource\Pages\EditCounterparty;
use App\Filament\Resources\CounterpartyResource\Pages\ListCounterparties;
use App\Models\Counterparty;
use App\Models\CounterpartyUser;
use BackedEnum;
use Filament\Facades\Filament;
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

        if (static::hasColumn('inn')) {
            $components[] = TextInput::make('inn')
                ->label('ИНН')
                ->maxLength(12);
        }

        if (static::hasColumn('kpp')) {
            $components[] = TextInput::make('kpp')
                ->label('КПП')
                ->maxLength(9);
        }

        if (static::hasColumn('email')) {
            $components[] = TextInput::make('email')
                ->label('Email')
                ->rule(static::emailListValidationRule())
                ->maxLength(255);
        }

        if (static::hasColumn('email_accountant')) {
            $components[] = TextInput::make('email_accountant')
                ->label('Email бухгалтера')
                ->rule(static::emailListValidationRule())
                ->maxLength(255);
        }

        if (static::hasColumn('phone')) {
            $components[] = TextInput::make('phone')
                ->label('Телефон')
                ->tel()
                ->maxLength(20);
        }

        if (static::hasColumn('note')) {
            $components[] = TextInput::make('note')
                ->label('Примечание')
                ->maxLength(255);
        }

        if (static::hasColumn('contract')) {
            $components[] = TextInput::make('contract')
                ->label('Договор')
                ->maxLength(255);
        }

        if (static::hasColumn('bitrix_company_id')) {
            $components[] = TextInput::make('bitrix_company_id')
                ->label('ID компании в Bitrix24')
                ->numeric()
                ->rule('integer');
        }

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

        if (static::hasColumn('inn')) {
            $columns[] = TextColumn::make('inn')
                ->label('ИНН')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('kpp')) {
            $columns[] = TextColumn::make('kpp')
                ->label('КПП')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('email')) {
            $columns[] = TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->copyable()
                ->toggleable();
        }

        if (static::hasColumn('email_accountant')) {
            $columns[] = TextColumn::make('email_accountant')
                ->label('Email бухгалтера')
                ->searchable()
                ->copyable()
                ->toggleable();
        }

        if (static::hasColumn('phone')) {
            $columns[] = TextColumn::make('phone')
                ->label('Телефон')
                ->searchable()
                ->toggleable();
        }

        if (static::hasColumn('note')) {
            $columns[] = TextColumn::make('note')
                ->label('Примечание')
                ->searchable()
                ->limit(40)
                ->toggleable();
        }

        if (static::hasColumn('contract')) {
            $columns[] = TextColumn::make('contract')
                ->label('Договор')
                ->searchable()
                ->limit(40)
                ->toggleable();
        }

        if (static::hasColumn('bitrix_company_id')) {
            $columns[] = TextColumn::make('bitrix_company_id')
                ->label('Bitrix24 ID')
                ->sortable()
                ->toggleable();
        }

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

    public static function canAccess(): bool
    {
        return ! static::isCounterpartyAuthenticated() && parent::canAccess();
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

    protected static function isCounterpartyAuthenticated(): bool
    {
        return Filament::auth()->user() instanceof CounterpartyUser;
    }

    protected static function emailListValidationRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            $value = trim((string) $value);

            if ($value === '') {
                return;
            }

            $emails = array_filter(
                array_map('trim', preg_split('/[;,\n]+/u', $value) ?: []),
                fn (string $email): bool => $email !== '',
            );

            if ($emails === []) {
                return;
            }

            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $fail('Поле Email должно содержать корректный email или список email через запятую или точку с запятой.');

                    return;
                }
            }
        };
    }
}
