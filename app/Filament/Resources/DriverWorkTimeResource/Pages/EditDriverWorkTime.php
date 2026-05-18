<?php

namespace App\Filament\Resources\DriverWorkTimeResource\Pages;

use App\Filament\Resources\DriverWorkTimeResource;
use App\Models\DriverWorkTime;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditDriverWorkTime extends EditRecord
{
    protected static string $resource = DriverWorkTimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $durationMinutes = DriverWorkTime::calculateDurationMinutes(
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
        );

        if ($durationMinutes === null) {
            throw ValidationException::withMessages([
                'data.end_time' => 'Время окончания должно быть позже времени начала.',
            ]);
        }

        $this->ensureUniqueDriverWorkTime($data);

        return DriverWorkTime::prepareAdminFormData($data);
    }

    protected function ensureUniqueDriverWorkTime(array $data): void
    {
        if (! isset($data['source'], $data['source_user_id'], $data['work_date'])) {
            return;
        }

        $exists = DriverWorkTime::query()
            ->where('source', $data['source'])
            ->where('source_user_id', $data['source_user_id'])
            ->whereDate('work_date', $data['work_date'])
            ->whereKeyNot($this->record->getKey())
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'data.work_date' => 'Для этого водителя уже есть запись за выбранную дату.',
        ]);
    }
}
