<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms;

class CreateTrainingClass extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = TrainingClassResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Informasi Dasar')
                ->schema([
                    Forms\Components\Select::make('scenario_id')
                        ->relationship('scenario', 'name')
                        ->required()
                        ->reactive()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                        
                    Forms\Components\TextInput::make('sales_name')
                        ->label('Nama Sales')
                        ->maxLength(100),
                        
                    Forms\Components\TextInput::make('material')
                        ->label('Materi')
                        ->maxLength(200),
                        
                    Forms\Components\TextInput::make('customer')
                        ->label('Customer')
                        ->maxLength(200)
                        ->required(),
                ])
                ->columns(2),
                
            Step::make('Detail Pelatihan')
                ->schema([
                    Forms\Components\TextInput::make('training_days')
                        ->label('Jumlah Hari Pelatihan')
                        ->numeric()
                        ->default(0)
                        ->required(),
                        
                    Forms\Components\TextInput::make('admin_days')
                        ->label('Jumlah Hari Administrasi')
                        ->numeric()
                        ->default(0),
                        
                    Forms\Components\TextInput::make('participant_count')
                        ->label('Jumlah Peserta')
                        ->numeric()
                        ->default(0)
                        ->required(),
                        
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),
                        
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai'),
                ])
                ->columns(2),
                
            Step::make('Harga & Status')
                ->schema([
                    Forms\Components\TextInput::make('price_per_participant')
                        ->label('Harga Real/Peserta')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),
                        
                    Forms\Components\TextInput::make('discount')
                        ->label('Diskon')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                        
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'proposed' => 'Proposed',
                            'approved' => 'Approved',
                        ])
                        ->default('draft')
                        ->required(),
                ])
                ->columns(2),
        ];
    }
}
