<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterResource\Pages\ListNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class NewsletterResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 8;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('admin.newsletter.names.subscriber');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.newsletter.names.subscriber_plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label(__('admin.newsletter.subscriber.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.newsletter.subscriber.name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('source')
                    ->label(__('admin.newsletter.subscriber.source'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('active')
                    ->label(__('admin.newsletter.subscriber.status'))
                    ->boolean(),
                TextColumn::make('subscribed_at')
                    ->label(__('admin.newsletter.subscriber.subscribed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('unsubscribed_at')
                    ->label(__('admin.newsletter.subscriber.unsubscribed_at'))
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label(__('admin.newsletter.subscriber.status')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make()->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterSubscribers::route('/'),
        ];
    }
}
