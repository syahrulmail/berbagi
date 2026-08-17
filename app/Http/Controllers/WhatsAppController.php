<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function index(Request $request)
    {
        $query = WhatsappMessage::with('contact');

        $query->when($request->status, function ($q, $status) {
            return $q->where('status', $status);
        });

        $messages = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('whatsapp.index', compact('messages'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('name')->get();

        return view('whatsapp.create', compact('contacts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string'],
        ]);

        $whatsappMessage = WhatsappMessage::create([
            'contact_id' => $data['contact_id'] ?? null,
            'phone' => $data['phone'],
            'message' => $data['message'],
            'status' => WhatsappMessage::STATUS_PENDING,
        ]);

        ActivityLog::record('whatsapp.create', 'Menjadwalkan pesan WhatsApp ke ' . $whatsappMessage->phone);

        return redirect()->route('whatsapp.index')->with('success', 'Pesan WhatsApp dijadwalkan untuk dikirim.');
    }

    public function destroy(WhatsappMessage $whatsappMessage)
    {
        ActivityLog::record('whatsapp.delete', 'Menghapus pesan WhatsApp ke ' . $whatsappMessage->phone);
        $whatsappMessage->delete();

        return redirect()->route('whatsapp.index')->with('success', 'Pesan WhatsApp berhasil dihapus.');
    }
}
