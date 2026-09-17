<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class QuotationPdfController extends Controller
{
    public function show(Quotation $quotation): Response
    {
        $quotation->load(['lines', 'thirdParty', 'company']);

        return Pdf::loadView('pdf.quotation', ['quotation' => $quotation])
            ->download("{$quotation->number}.pdf");
    }
}
