<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Invite User';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set a random password — the invite email lets them choose their own
        $data['password'] = bcrypt(Str::random(32));

        return $data;
    }

    protected function afterCreate(): void
    {
        // Mark as verified — admin invited them with a known, trusted email address
        $this->record->markEmailAsVerified();

        $status = Password::sendResetLink(['email' => $this->record->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Invite sent')
                ->body($this->record->email.' will receive an email to set their password.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('User created but invite failed')
                ->body('You can resend the invite from the users list.')
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
