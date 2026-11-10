<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverReferral;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverReferralController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess($request);
        $companyId = $this->companyId($request);

        $referrals = DriverReferral::with(['referrer:id,name', 'referredDriver:id,name,start_date,end_date'])
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->latest()
            ->get()
            ->each->refreshEligibility();

        $drivers = Driver::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'start_date']);

        return view('admin.driverReferrals.index', [
            'referrals' => $referrals,
            'drivers' => $drivers,
            'statuses' => DriverReferral::STATUS_LABELS,
        ]);
    }

    public function update(Request $request, DriverReferral $driverReferral)
    {
        $this->authorizeAccess($request);
        $companyId = $this->companyId($request);
        abort_if($companyId && (int) $driverReferral->company_id !== $companyId, 403);

        $validated = $request->validate([
            'referred_driver_id' => [
                'nullable',
                'integer',
                Rule::exists('drivers', 'id')->where(fn ($query) => $query
                    ->where('company_id', $driverReferral->company_id)
                    ->whereNull('deleted_at')),
            ],
            'status' => ['required', Rule::in(array_keys(DriverReferral::STATUS_LABELS))],
            'reward_amount' => ['required', 'numeric', 'min:0', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $validated['status'];
        $driverReferral->fill([
            'referred_driver_id' => $validated['referred_driver_id'] ?: null,
            'status' => $status,
            'reward_amount' => $validated['reward_amount'],
            'notes' => $validated['notes'] ?? null,
            'paid_at' => $status === DriverReferral::STATUS_PAID
                ? ($driverReferral->paid_at ?: now())
                : null,
            'qualified_at' => $status === DriverReferral::STATUS_ELIGIBLE
                ? ($driverReferral->qualified_at ?: now())
                : $driverReferral->qualified_at,
        ])->save();

        if ($driverReferral->referred_driver_id
            && in_array($status, [DriverReferral::STATUS_SUBMITTED, DriverReferral::STATUS_JOINED, DriverReferral::STATUS_ELIGIBLE], true)) {
            $driverReferral->refreshEligibility();
        }

        return back()->with('message', 'Indicação atualizada.');
    }

    private function authorizeAccess(Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole('Admin') || $request->user()?->hasRole('Gestor'),
            403
        );
    }

    private function companyId(Request $request): ?int
    {
        $sessionCompany = session()->get('company_id');
        if ($sessionCompany && $sessionCompany !== '0') {
            return (int) $sessionCompany;
        }

        return optional($request->user()->company)->id;
    }
}
