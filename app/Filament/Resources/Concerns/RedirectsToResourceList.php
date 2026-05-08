<?php

namespace App\Filament\Resources\Concerns;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Js;
use Livewire\Attributes\Url;

trait RedirectsToResourceList
{
    #[Url(as: 'search')]
    public ?string $preservedListSearch = null;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResourceListUrl();
    }

    protected function getCancelFormAction(): Action
    {
        $url = $this->getResourceListUrl();

        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->alpineClickHandler(
                FilamentView::hasSpaMode($url)
                    ? 'Livewire.navigate('.Js::from($url).')'
                    : 'window.location.href = '.Js::from($url),
            )
            ->color('gray');
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        if ($action instanceof DeleteAction || $action instanceof ForceDeleteAction) {
            return $this->getResourceListUrl();
        }

        return parent::getDefaultActionSuccessRedirectUrl($action);
    }

    protected function getResourceListUrl(): string
    {
        return $this->getResourceUrl(parameters: $this->preservedListSearchQueryParameter());
    }

    protected function preservedListSearchQueryParameter(): array
    {
        $search = trim((string) $this->preservedListSearch);

        if ($search === '') {
            return [];
        }

        return [
            'search' => $search,
        ];
    }
}
