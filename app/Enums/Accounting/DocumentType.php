<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DocumentType: string implements HasIcon, HasLabel
{
    case Invoice = 'invoice';
    case Bill = 'bill';
    case Estimate = 'estimate';

    public const DEFAULT = self::Invoice->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return match ($this->value) {
            self::Invoice->value => 'heroicon-o-document-duplicate',
            self::Bill->value => 'heroicon-o-clipboard-document-list',
            self::Estimate->value => 'heroicon-o-document-text',
        };
    }

    public function getTaxKey(): string
    {
        return match ($this) {
            self::Invoice, self::Estimate => 'salesTaxes',
            self::Bill => 'purchaseTaxes',
        };
    }

    public function getDiscountKey(): string
    {
        return match ($this) {
            self::Invoice, self::Estimate => 'salesDiscounts',
            self::Bill => 'purchaseDiscounts',
        };
    }

    public function getLabels(): array
    {
        return match ($this) {
            self::Invoice => [
                'title' => 'Invoice',
                'number' => 'Invoice Number',
                'reference_number' => 'P.O/S.O Number',
                'date' => 'Invoice Date',
                'due_date' => 'Payment Due',
            ],
            self::Estimate => [
                'title' => 'Estimate',
                'number' => 'Estimate Number',
                'reference_number' => 'Reference Number',
                'date' => 'Estimate Date',
                'due_date' => 'Expiration Date',
            ],
            self::Bill => [
                'title' => 'Bill',
                'number' => 'Bill Number',
                'reference_number' => 'P.O/S.O Number',
                'date' => 'Bill Date',
                'due_date' => 'Payment Due',
            ],
        };
    }
}
