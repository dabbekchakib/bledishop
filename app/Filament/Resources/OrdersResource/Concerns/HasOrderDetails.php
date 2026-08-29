<?php

namespace App\Filament\Resources\OrdersResource\Concerns;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;

trait HasOrderDetails
{
    protected static function orderInformationSection(): Section
    {
        return Section::make(__('admin.orders.order'))
            ->columns(2)
            ->schema([
                TextInput::make('order_number')
                    ->label('N° de commande')
                    ->disabled(),
                TextInput::make('created_at')
                    ->label('Date')
                    ->disabled()
                    ->formatStateUsing(fn (Order $record): string => optional($record->created_at)->format('d/m/Y H:i') ?? ''),
                TextInput::make('customer_full_name')
                    ->label('Client')
                    ->disabled()
                    ->formatStateUsing(fn (Order $record): string => $record->customerFullName()),
                TextInput::make('customer_email')
                    ->label('Email client')
                    ->disabled(),
                TextInput::make('customer_phone')
                    ->label('Téléphone client')
                    ->disabled(),
                TextInput::make('account')
                    ->label('Compte')
                    ->disabled()
                    ->formatStateUsing(fn (Order $record): string => $record->isGuestOrder()
                        ? __('admin.orders.guest_order')
                        : __('admin.orders.registered_order')),
            ]);
    }

    protected static function orderProcessingSection(): Section
    {
        return Section::make(__('admin.orders.processing'))
            ->description(__('admin.orders.processing_hint'))
            ->columns(2)
            ->schema([
                TextInput::make('status_display')
                    ->label('Statut')
                    ->disabled()
                    ->formatStateUsing(fn (Order $record): string => $record->status->label())
                    ->helperText(__('admin.orders.status_actions_hint')),
                Select::make('payment_status')
                    ->label('Statut de paiement')
                    ->options(PaymentStatus::options())
                    ->required(),
                TextInput::make('total_display')
                    ->label('Total')
                    ->disabled()
                    ->formatStateUsing(fn (Order $record): string => format_price($record->totalAmount())),
                TextInput::make('currency')
                    ->label('Devise')
                    ->disabled(),
                Textarea::make('admin_notes')
                    ->label('Notes internes')
                    ->rows(3)
                    ->helperText(__('admin.orders.admin_notes_hint')),
            ]);
    }

    protected static function orderAddressSection(): Section
    {
        return Section::make(__('admin.orders.shipping'))
            ->columns(2)
            ->schema([
                Textarea::make('shipping_address')
                    ->label('Adresse de livraison')
                    ->disabled()
                    ->rows(2),
                TextInput::make('shipping_city')
                    ->label('Ville')
                    ->disabled(),
                TextInput::make('shipping_postal_code')
                    ->label('Code postal')
                    ->disabled(),
                TextInput::make('shipping_country')
                    ->label('Pays')
                    ->disabled(),
                Textarea::make('customer_notes')
                    ->label('Note du client')
                    ->disabled()
                    ->rows(2)
                    ->placeholder('-'),
            ]);
    }

    protected static function orderStatusOptions(): array
    {
        return OrderStatus::options();
    }
}
