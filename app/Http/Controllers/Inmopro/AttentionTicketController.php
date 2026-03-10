<?php

namespace App\Http\Controllers\Inmopro;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\StoreAttentionTicketRequest;
use App\Http\Requests\Inmopro\UpdateAttentionTicketRequest;
use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AttentionTicket;
use App\Models\Inmopro\DeliveryDeed;
use App\Models\Inmopro\Lot;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttentionTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AttentionTicket::with(['advisor', 'lot.project', 'lot.client', 'deliveryDeed'])
            ->orderBy('scheduled_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tickets = $query->paginate(15)->withQueryString();

        return Inertia::render('inmopro/operations/attention-tickets/index', [
            'tickets' => $tickets,
            'filters' => $request->only('status'),
        ]);
    }

    public function calendar(Request $request): Response
    {
        $query = AttentionTicket::with(['advisor', 'lot.project', 'lot.client'])
            ->orderBy('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tickets = $query->get();
        $events = $tickets->map(function (AttentionTicket $t) {
            $start = Carbon::parse($t->scheduled_at);
            $end = $start->copy()->addHour();
            $title = sprintf(
                '#%d · %s · %s-%s %s',
                $t->id,
                $t->advisor?->name ?? '—',
                $t->lot?->block ?? '',
                $t->lot?->number ?? '',
                $t->lot?->project?->name ?? ''
            );

            return [
                'id' => (string) $t->id,
                'title' => $title,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'url' => route('inmopro.attention-tickets.show', $t),
                'extendedProps' => [
                    'status' => $t->status,
                    'advisor' => $t->advisor?->name,
                    'lot' => $t->lot ? $t->lot->block.'-'.$t->lot->number : null,
                    'client' => $t->lot?->client?->name,
                ],
            ];
        })->values()->all();

        return Inertia::render('inmopro/operations/attention-tickets/calendar', [
            'events' => $events,
            'filters' => $request->only('status'),
        ]);
    }

    public function create(Request $request): Response
    {
        $lots = Lot::with(['project', 'client', 'status'])
            ->whereNotNull('client_id')
            ->orderBy('block')
            ->orderBy('number')
            ->get();

        $advisors = Advisor::orderBy('name')->get(['id', 'name']);

        return Inertia::render('inmopro/operations/attention-tickets/create', [
            'lots' => $lots,
            'advisors' => $advisors,
        ]);
    }

    public function store(StoreAttentionTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['scheduled_at'] = Carbon::parse($validated['scheduled_at']);
        $validated['status'] = 'agendado';

        AttentionTicket::create($validated);

        return redirect()->route('inmopro.attention-tickets.index');
    }

    public function show(AttentionTicket $attention_ticket): Response
    {
        $attention_ticket->load(['advisor', 'lot.project', 'lot.client', 'deliveryDeed']);

        return Inertia::render('inmopro/operations/attention-tickets/show', [
            'ticket' => $attention_ticket,
        ]);
    }

    public function edit(AttentionTicket $attention_ticket): Response
    {
        $attention_ticket->load(['lot.project', 'lot.client', 'advisor']);

        return Inertia::render('inmopro/operations/attention-tickets/edit', [
            'ticket' => $attention_ticket,
        ]);
    }

    public function update(UpdateAttentionTicketRequest $request, AttentionTicket $attention_ticket): RedirectResponse
    {
        $attention_ticket->update($request->validated());

        return redirect()->route('inmopro.attention-tickets.show', $attention_ticket);
    }

    public function destroy(AttentionTicket $attention_ticket): RedirectResponse
    {
        $attention_ticket->delete();

        return redirect()->route('inmopro.attention-tickets.index');
    }

    public function deliveryDeed(AttentionTicket $attention_ticket): Response
    {
        $attention_ticket->load(['lot.project', 'lot.client', 'advisor']);

        $deed = $attention_ticket->deliveryDeed;
        if (! $deed) {
            $deed = DeliveryDeed::create([
                'attention_ticket_id' => $attention_ticket->id,
                'lot_id' => $attention_ticket->lot_id,
                'printed_at' => now(),
            ]);
        } elseif (! $deed->printed_at) {
            $deed->update(['printed_at' => now()]);
        }

        $companyName = config('app.name');

        return Inertia::render('inmopro/operations/delivery-deed-print', [
            'ticket' => $attention_ticket->load(['lot.project', 'lot.client', 'advisor']),
            'deed' => $deed,
            'companyName' => $companyName,
        ]);
    }

    public function markDeedSigned(AttentionTicket $attention_ticket): RedirectResponse
    {
        $deed = $attention_ticket->deliveryDeed;
        if (! $deed) {
            $deed = DeliveryDeed::create([
                'attention_ticket_id' => $attention_ticket->id,
                'lot_id' => $attention_ticket->lot_id,
                'printed_at' => null,
                'signed_at' => now(),
            ]);
        } else {
            $deed->update(['signed_at' => now()]);
        }

        $attention_ticket->update(['status' => 'realizado']);

        return back();
    }
}
