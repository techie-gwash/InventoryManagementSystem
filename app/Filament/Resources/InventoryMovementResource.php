<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Location;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Inventory Movements';

    protected static ?string $navigationGroup = 'Inventory';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::check() && Auth::user()->can('view inventory');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Select::make('type')
                ->required()
                ->options([
                    'purchase' => 'Purchase',
                    'sale' => 'Sale',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Adjustment',
                ])
                ->reactive(),

            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->options(Product::pluck('name', 'id'))
                ->searchable()
                ->required(),

            Forms\Components\Select::make('location_id')
                ->label('Location')
                ->options(Location::pluck('name', 'id'))
                ->searchable()
                ->required()
                ->visible(fn ($get) => $get('type') !== 'transfer'),

            Forms\Components\Select::make('from_location_id')
                ->label('From Location')
                ->options(Location::pluck('name', 'id'))
                ->searchable()
                ->required()
                ->visible(fn ($get) => $get('type') === 'transfer'),

            Forms\Components\Select::make('to_location_id')
                ->label('To Location')
                ->options(Location::pluck('name', 'id'))
                ->searchable()
                ->required()
                ->visible(fn ($get) => $get('type') === 'transfer'),

            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('location.name')->label('Location'),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('quantity')
                    ->color(fn ($record) => $record->quantity < 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('creator.name')->label('Created By'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([]) // No edit/delete
            ->bulkActions([]);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $type = $data['type'];
        $quantity = (int) $data['quantity'];

        if ($type === 'sale') {
            $availableStock = Product::find($data['product_id'])
                ->getStockAtLocation($data['location_id']);

            if ($quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock available.',
                ]);
            }

            $data['quantity'] = -$quantity;
        }

        if ($type === 'adjustment') {
            // Positive or negative allowed manually
        }

        if ($type === 'purchase') {
            $data['quantity'] = abs($quantity);
        }

        if ($type === 'transfer') {

            $availableStock = Product::find($data['product_id'])
                ->getStockAtLocation($data['from_location_id']);

            if ($quantity > $availableStock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock in source location.',
                ]);
            }

            // Create OUT movement
            InventoryMovement::create([
                'product_id' => $data['product_id'],
                'location_id' => $data['from_location_id'],
                'type' => 'transfer_out',
                'quantity' => -abs($quantity),
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Create IN movement
            InventoryMovement::create([
                'product_id' => $data['product_id'],
                'location_id' => $data['to_location_id'],
                'type' => 'transfer_in',
                'quantity' => abs($quantity),
                'created_by' => Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);

            return [];
        }

        $data['created_by'] = Auth::id();

        return $data;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
        ];
    }
}
