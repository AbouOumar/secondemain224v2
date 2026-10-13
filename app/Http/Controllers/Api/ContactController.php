<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(ContactRequest $request)
    {
        $to = config('legal.public_email');
        if (! $to) {
            return response()->json(['message' => 'Le formulaire de contact est momentanément indisponible.'], 503);
        }

        Mail::to($to)->send(new ContactMessageMail(
            $request->input('name'),
            $request->input('email'),
            $request->input('message'),
        ));

        return response()->json(['message' => 'Message envoyé avec succès.']);
    }
}
