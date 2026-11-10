<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverReferral;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileDriverReferralController extends Controller
{
    public function index(Request $request)
    {
        $driver = $this->driver($request);
        $referrals = DriverReferral::with('referredDriver:id,name,start_date,end_date')
            ->where('referrer_driver_id', $driver->id)
            ->latest()
            ->get()
            ->each->refreshEligibility();

        return response()->json([
            'reward_amount' => 50,
            'qualification_days' => 30,
            'referrals' => $referrals->map(fn (DriverReferral $referral) => $this->serialize($referral))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $driver = $this->driver($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:160'],
            'phone' => ['required', 'string', 'min:9', 'max:40'],
            'consent' => ['accepted'],
        ]);

        $normalizedPhone = DriverReferral::normalizePhone($validated['phone']);
        if (strlen($normalizedPhone) < 9) {
            throw ValidationException::withMessages([
                'phone' => 'Indica um número de telefone válido.',
            ]);
        }

        $duplicate = DriverReferral::where('company_id', $driver->company_id)
            ->where('referred_phone_normalized', $normalizedPhone)
            ->where('status', '!=', DriverReferral::STATUS_REJECTED)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'phone' => 'Este contacto já foi indicado.',
            ]);
        }

        $referral = DriverReferral::create([
            'company_id' => $driver->company_id,
            'referrer_driver_id' => $driver->id,
            'referred_name' => trim($validated['name']),
            'referred_phone' => trim($validated['phone']),
            'referred_phone_normalized' => $normalizedPhone,
            'status' => DriverReferral::STATUS_SUBMITTED,
            'reward_amount' => 50,
            'consent_confirmed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Indicação registada. Podes acompanhar o estado nesta página.',
            'referral' => $this->serialize($referral),
        ], 201);
    }

    private function driver(Request $request): Driver
    {
        $driver = Driver::where('user_id', $request->user()->id)->first();
        abort_unless($driver && $driver->company_id, 403, 'A conta não está associada a um motorista ativo.');

        return $driver;
    }

    private function serialize(DriverReferral $referral): array
    {
        return [
            'id' => $referral->id,
            'name' => $referral->referred_name,
            'phone' => $referral->referred_phone,
            'status' => $referral->status,
            'status_label' => $referral->status_label,
            'reward_amount' => (float) $referral->reward_amount,
            'qualified_at' => optional($referral->qualified_at)->toDateString(),
            'paid_at' => optional($referral->paid_at)->toDateString(),
            'created_at' => optional($referral->created_at)->format('Y-m-d H:i:s'),
        ];
    }
}
