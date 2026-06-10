<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact');
    }

    public function send(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // TODO: envoyer l'email
        return back()->with('success', 'Message envoyé avec succès.');
    }
}
