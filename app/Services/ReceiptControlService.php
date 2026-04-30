<?php

namespace App\Services;

use App\Models\CurrentAccount;
use App\Models\DriversBalance;
use App\Models\Receipt;
use Illuminate\Support\Collection;

class ReceiptControlService
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ALL = 'all';
    public const STATUS_MISSING = 'missing';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_PAID = 'paid';
    public const STATUS_NOT_REQUIRED = 'not_required';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Pendentes + colocados',
        self::STATUS_ALL => 'Todos',
        self::STATUS_MISSING => 'Em falta',
        self::STATUS_SUBMITTED => 'Colocado',
        self::STATUS_VERIFIED => 'Verificado',
        self::STATUS_PAID => 'Pago',
        self::STATUS_NOT_REQUIRED => 'Sem recibo necessario',
    ];

    public function rows(array $filters = []): Collection
    {
        $driverId = $filters['driver_id'] ?? null;
        $weekId = $filters['tvde_week_id'] ?? null;
        $status = $filters['status'] ?? self::STATUS_ACTIVE;

        $receipts = Receipt::with(['media', 'driver.company', 'tvde_week'])
            ->when($driverId, fn ($query) => $query->where('driver_id', $driverId))
            ->when($weekId, fn ($query) => $query->where('tvde_week_id', $weekId))
            ->get()
            ->keyBy(fn (Receipt $receipt) => $this->key($receipt->driver_id, $receipt->tvde_week_id));

        $balances = DriversBalance::with(['driver.company', 'tvde_week'])
            ->when($driverId, fn ($query) => $query->where('driver_id', $driverId))
            ->when($weekId, fn ($query) => $query->where('tvde_week_id', $weekId))
            ->get()
            ->keyBy(fn (DriversBalance $balance) => $this->key($balance->driver_id, $balance->tvde_week_id));

        $accounts = CurrentAccount::with(['driver.company', 'tvde_week'])
            ->when($driverId, fn ($query) => $query->where('driver_id', $driverId))
            ->when($weekId, fn ($query) => $query->where('tvde_week_id', $weekId))
            ->get()
            ->filter(fn (CurrentAccount $account) => $account->driver && $account->tvde_week_id)
            ->keyBy(fn (CurrentAccount $account) => $this->key($account->driver_id, $account->tvde_week_id));

        return $accounts->keys()
            ->merge($balances->keys())
            ->merge($receipts->keys())
            ->unique()
            ->map(function (string $key) use ($accounts, $balances, $receipts) {
                $account = $accounts->get($key);
                $balance = $balances->get($key);
                $receipt = $receipts->get($key);
                $driver = $account?->driver ?? $balance?->driver ?? $receipt?->driver;
                $week = $account?->tvde_week ?? $balance?->tvde_week ?? $receipt?->tvde_week;

                if (!$driver || !$week) {
                    return null;
                }

                $requiredValue = $this->requiredValue($account, $balance);
                $expectedValue = $receipt ? (float) $receipt->value : $requiredValue;
                $rowStatus = $this->statusFor($receipt, $requiredValue);

                return [
                    'driver' => $driver,
                    'week' => $week,
                    'balance' => $balance,
                    'receipt' => $receipt,
                    'required_value' => $requiredValue,
                    'expected_value' => $expectedValue,
                    'status' => $rowStatus,
                    'status_label' => self::STATUS_LABELS[$rowStatus],
                ];
            })
            ->filter()
            ->filter(fn (array $row) => $this->matchesStatus($row['status'], $status))
            ->sortByDesc(fn (array $row) => ($row['week']?->getRawOriginal('start_date') ?? '') . '|' . ($row['driver']?->name ?? ''))
            ->values();
    }

    public function requiredValue(?CurrentAccount $account, ?DriversBalance $balance): float
    {
        if ($balance) {
            return round((float) $balance->new_balance, 2);
        }

        return round($this->expectedValueFromAccount($account), 2);
    }

    protected function expectedValueFromAccount(?CurrentAccount $account): float
    {
        $data = json_decode($account?->data ?? '', true) ?? [];

        return (float) (
            $data['driver_total']
            ?? $data['total']
            ?? $data['subtotal_after_tips']
            ?? $data['total_after_vat']
            ?? 0
        );
    }

    public function statusFor(?Receipt $receipt, float $requiredValue): string
    {
        if ($receipt) {
            if ($receipt->paid) {
                return self::STATUS_PAID;
            }

            if ($receipt->verified) {
                return self::STATUS_VERIFIED;
            }

            return self::STATUS_SUBMITTED;
        }

        return $requiredValue > 0 ? self::STATUS_MISSING : self::STATUS_NOT_REQUIRED;
    }

    protected function matchesStatus(string $rowStatus, string $filterStatus): bool
    {
        if ($filterStatus === self::STATUS_ALL) {
            return true;
        }

        if ($filterStatus === self::STATUS_ACTIVE) {
            return $rowStatus !== self::STATUS_NOT_REQUIRED;
        }

        return $rowStatus === $filterStatus;
    }

    protected function key($driverId, $weekId): string
    {
        return $driverId . ':' . $weekId;
    }
}
