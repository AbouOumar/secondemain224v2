<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
use App\Services\Escrow\EscrowService;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    public function __construct(private EscrowService $escrow) {}

    public function index(Request $request)
    {
        $query = Escrow::with(['order.buyer', 'order.seller', 'order.article']);

        $status = $request->get('status', 'retenu');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $escrows = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.escrows.index', compact('escrows', 'status'));
    }

    public function release(Escrow $escrow)
    {
        $this->escrow->release($escrow, 'liberation_manuelle_admin');

        return back()->with('success', "Escrow #{$escrow->id} libéré manuellement.");
    }

    public function refund(Escrow $escrow)
    {
        $this->escrow->refund($escrow, 'remboursement_manuel_admin');

        return back()->with('success', "Escrow #{$escrow->id} remboursé à l'acheteur.");
    }

    public function dispute(Escrow $escrow)
    {
        $this->escrow->markDisputed($escrow);

        return back()->with('success', "Escrow #{$escrow->id} marqué en litige.");
    }
}
