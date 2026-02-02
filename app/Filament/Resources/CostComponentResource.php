<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CostComponentResource\Pages;
use App\Models\CostComponent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CostComponentResource extends Resource
{
    protected static ?string $model = CostComponent::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(200),
                    
                Forms\Components\Select::make('nature')
                    ->options([
                        'R' => 'Real Cost (Inix Bayar)',
                        'L' => 'Pass Cost (Client Bayar)',
                    ])
                    ->required(),
                    
                Forms\Components\Select::make('role')
                    ->options([
                        'Instruktur' => 'Instruktur',
                        'Peserta' => 'Peserta',
                        'Tim Inixindo' => 'Tim Inixindo',
                        'Tim dan Instruktur' => 'Tim dan Instruktur',
                        'Global' => 'Global',
                        'Operasional' => 'Operasional',
                        'Tambahan' => 'Tambahan',
                        'Pajak' => 'Pajak',
                    ])
                    ->required(),
                    
                Forms\Components\TextInput::make('time_unit')
                    ->maxLength(50)
                    ->placeholder('Hari, Malam, Liter, dll'),
                    
                Forms\Components\TextInput::make('quantity_unit')
                    ->maxLength(50)
                    ->placeholder('Pax, Transaksi, dll'),
                    
                Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0),
                    
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('nature')
                    ->colors([
                        'primary' => 'R',
                        'success' => 'L',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'R' ? 'Real Cost' : 'Pass Cost'),
                    
                Tables\Columns\TextColumn::make('role')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                    
                Tables\Filters\SelectFilter::make('nature')
                    ->options([
                        'R' => 'Real Cost',
                        'L' => 'Pass Cost',
                    ]),
            ])
            ->defaultSort('order');
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
            'index' => Pages\ListCostComponents::route('/'),
            'create' => Pages\CreateCostComponent::route('/create'),
            'edit' => Pages\EditCostComponent::route('/{record}/edit'),
        ];
    }
}
