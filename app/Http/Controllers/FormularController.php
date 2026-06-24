<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class FormularController extends Controller
{
    /** Übersicht aller Formulare. */
    public function index()
    {
        return view('pages.formulare', [
            'title'       => 'Kfz-Formulare zum Download – Vollmacht, SEPA, eidesstattliche Versicherung',
            'description' => 'Kostenlose Formulare für die Zulassungsstelle als PDF: Vollmacht, SEPA-Lastschriftmandat '
                .'für die Kfz-Steuer, Einverständniserklärung, eidesstattliche Versicherung (ZB I & II) und Halterauskunft.',
            'canonical'   => url('/formulare'),
            'schemas'     => [[
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Start', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Formulare', 'item' => url('/formulare')],
                ],
            ]],
            'formulare'   => config('formulare', []),
        ]);
    }

    /** Einzelnes Formular als PDF herunterladen. */
    public function download(string $slug)
    {
        $form = config('formulare.'.$slug);
        abort_unless(is_array($form), 404);

        $pdf = Pdf::loadView('formulare.pdf', ['form' => $form])->setPaper('a4');

        return $pdf->download($slug.'.pdf');
    }
}
