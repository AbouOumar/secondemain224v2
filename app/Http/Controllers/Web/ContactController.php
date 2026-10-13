<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact');
    }

    public function send(ContactRequest $request)
    {
        $to = config('legal.public_email');
        if (! $to) {
            return back()->withInput()->withErrors(['message' => 'Le formulaire de contact est momentanément indisponible.']);
        }

        Mail::to($to)->send(new ContactMessageMail(
            $request->input('name'),
            $request->input('email'),
            $request->input('message'),
        ));

        return back()->with('success', 'Message envoyé avec succès.');
    }
}
