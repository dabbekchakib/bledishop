<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ProductReviewResource\Pages\CreateProductReview;
use App\Filament\Resources\ProductReviewResource\Pages\EditProductReview;
use App\Filament\Resources\ProductReviewResource\Pages\ListProductReviews;
use App\Models\ProductReview;
use App\Services\ReviewService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.nav.catalogue');
    }

    public static function getModelLabel(): string
    {
        return __('admin.reviews.names.review');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.reviews.names.review_plural');
    }

    public static function canViewAny(): bool
    {
        return (bool) (auth()->user()?->can('reviews.view'));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Grid::make(2)->schema([
                        Select::make('product_id')
                            ->label(__('admin.reviews.review.product'))
                            ->relationship('product', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->translatedName())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('rating')
                            ->label(__('admin.reviews.review.rating'))
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                            ->required(),
                    ]),
                    TextInput::make('title')
                        ->label(__('admin.reviews.review.title'))
                        ->maxLength(150),
                    Textarea::make('comment')
                        ->label(__('admin.reviews.review.comment'))
                        ->rows(4)
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label(__('admin.reviews.review.status'))
                            ->options(collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $s): array => [$s->value => $s->label()])->all())
                            ->default(ReviewStatus::Pending->value),
                        Toggle::make('verified_purchase')
                            ->label(__('admin.reviews.review.verified_purchase')),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label(__('admin.reviews.review.product'))
                    ->searchable()
                    ->sortable('product_id'),
                TextColumn::make('author')
                    ->label(__('admin.reviews.review.author'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })),
                TextColumn::make('rating')
                    ->label(__('admin.reviews.review.rating'))
                    ->badge()
                    ->suffix(' / 5')
                    ->colors(['warning' => fn ($state): bool => in_array($state, [3, 4, 5], true), 'danger' => fn ($state): bool => in_array($state, [1, 2], true)])
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.reviews.review.status'))
                    ->badge()
                    ->color(fn (ReviewStatus $state): string => match ($state) {
                        ReviewStatus::Approved => 'success',
                        ReviewStatus::Pending => 'warning',
                        ReviewStatus::Rejected => 'danger',
                    })
                    ->formatStateUsing(fn (ReviewStatus $state): string => $state->label()),
                IconColumn::make('verified_purchase')
                    ->label(__('admin.reviews.review.verified_purchase'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.reviews.review.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.reviews.review.status'))
                    ->options(collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $s): array => [$s->value => $s->label()])->all()),
                SelectFilter::make('rating')
                    ->label(__('admin.reviews.review.rating'))
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                TableAction::make('approve')
                    ->label(__('admin.reviews.review.approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProductReview $record): bool => (bool) (auth()->user()?->can('reviews.moderate')) && ! $record->isApproved())
                    ->action(function (ProductReview $record): void {
                        app(ReviewService::class)->moderate($record, ReviewStatus::Approved);
                    }),
                TableAction::make('reject')
                    ->label(__('admin.reviews.review.reject'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ProductReview $record): bool => (bool) (auth()->user()?->can('reviews.moderate')) && $record->status !== ReviewStatus::Rejected)
                    ->action(function (ProductReview $record): void {
                        app(ReviewService::class)->moderate($record, ReviewStatus::Rejected);
                    }),
                EditAction::make()
                    ->visible(fn (): bool => (bool) (auth()->user()?->can('reviews.manage'))),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->visible(fn (): bool => (bool) (auth()->user()?->can('reviews.manage'))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => (bool) (auth()->user()?->can('reviews.manage'))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'create' => CreateProductReview::route('/create'),
            'edit' => EditProductReview::route('/{record}/edit'),
        ];
    }
}
