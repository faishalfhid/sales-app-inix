<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_password')
                ->label('Ubah Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('new_password')
                        ->label('Password Baru')
                        ->password()
                        ->required()
                        ->rule(\Illuminate\Validation\Rules\Password::default()),

                    \Filament\Forms\Components\TextInput::make('new_password_confirmation')
                        ->label('Konfirmasi Password Baru')
                        ->password()
                        ->required()
                        ->same('new_password'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'password' => \Illuminate\Support\Facades\Hash::make($data['new_password']),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Password Updated')
                        ->body('Password berhasil diubah.')
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User Updated')
            ->body('User berhasil diupdate.');
    }
}