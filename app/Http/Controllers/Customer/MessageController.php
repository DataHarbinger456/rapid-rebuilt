<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /**
     * The customer's own half of the conversation.
     *
     * Staff-internal notes are excluded outright; beyond that a customer sees
     * only messages they sent or that were addressed to them.
     */
    public function index()
    {
        $messages = Message::query()
            ->forUser(auth()->id())
            ->external()
            ->with(['userCase', 'sender'])
            ->latest()
            ->paginate(20);

        return view('customer.messages.index', compact('messages'));
    }

    /**
     * Compose screen. `?case_id=` (from a case's "Contact Support" button)
     * preselects that case, but only when the case belongs to the customer.
     */
    public function create(Request $request)
    {
        $cases = auth()->user()->cases()
            ->with('vehicle')
            ->orderByDesc('created_at')
            ->get();

        // firstWhere over the user's own cases: a foreign id simply finds
        // nothing, so it can neither preselect nor leak that the case exists.
        $selectedCase = $request->filled('case_id')
            ? $cases->firstWhere('id', (int) $request->query('case_id'))
            : null;

        return view('customer.messages.create', compact('cases', 'selectedCase'));
    }

    /**
     * Send a message to the support pool.
     *
     * recipient_id stays null — that is how the schema models "to support"
     * rather than to one named person. No email and no staff inbox exist yet;
     * that is future work rather than something faked here.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'case_id' => [
                'required',
                'integer',
                // Ownership is part of the rule, so a foreign case id fails
                // validation instead of quietly attaching a message to it.
                Rule::exists('user_cases', 'id')->where('user_id', auth()->id()),
            ],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'case_id.exists' => 'Please choose one of your own cases.',
        ]);

        Message::create([
            'user_case_id' => $validated['case_id'],
            'sender_id' => auth()->id(),
            'recipient_id' => null,
            'message' => $validated['message'],
            'message_type' => 'text',
            'is_internal' => false,
        ]);

        return redirect()
            ->route('customer.messages')
            ->with('success', 'Message sent. Our team will get back to you soon.');
    }
}
