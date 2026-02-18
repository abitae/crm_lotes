<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Models\Inmopro\Commission;
use App\Models\Inmopro\CommissionStatus;
use App\Services\Inmopro\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionController extends Controller
{
    public function __construct(
        private CommissionService $commissionService
    ) {}

    public function index(Request $request): Response
    {
        $query = Commission::with(['lot.project', 'advisor.level', 'status']);

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->input('end_date'));
        }
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->whereHas('advisor', fn ($q) => $q->where('name', 'like', "%{$term}%"));
        }

        $commissions = $query->orderBy('date', 'desc')->paginate(20)->withQueryString();
        $totalCommissions = Commission::when($request->filled('start_date'), fn ($q) => $q->where('date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->where('date', '<=', $request->input('end_date')))
            ->sum('amount');
        $pendingStatus = CommissionStatus::where('code', 'PENDIENTE')->first();
        $paidStatus = CommissionStatus::where('code', 'PAGADO')->first();

        return Inertia::render('inmopro/commissions', [
            'commissions' => $commissions,
            'totalCommissions' => $totalCommissions,
            'commissionStatuses' => CommissionStatus::orderBy('sort_order')->get(),
            'filters' => $request->only('start_date', 'end_date', 'search'),
        ]);
    }

    public function markAsPaid(Commission $commission): RedirectResponse
    {
        $this->commissionService->markAsPaid($commission);

        return back();
    }
}
