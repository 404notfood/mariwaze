<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use App\Models\CruiseChecklist;
use App\Models\CruiseChecklistItem;
use App\Services\Accounts\AccountContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CruiseTravelController extends Controller
{
    private function cruise(Request $request, string $slug): Cruise
    {
        $organization = app(AccountContext::class)->currentOrCreate($request->user());
        $cruise = Cruise::where('organization_id', $organization->id)->where('slug', $slug)->firstOrFail();
        $this->authorize('view', $cruise);

        return $cruise;
    }

    public function booklet(Request $request, string $slug): Response
    {
        $cruise = $this->cruise($request, $slug)->load(['boat', 'selectedRoute.segments']);
        $checklists = CruiseChecklist::with('items')->where('cruise_id', $cruise->id)->get();

        return response()->view('exports.cruise-booklet', [
            'cruise' => $cruise, 'route' => $cruise->selectedRoute,
            'checklists' => $checklists, 'exportedAt' => now(),
        ])->header('Content-Disposition', 'attachment; filename="carnet-'.(Str::slug($cruise->title) ?: 'croisiere').'.html"')
            ->header('Cache-Control', 'private, no-store');
    }

    public function gpx(Request $request, string $slug): Response
    {
        $cruise = $this->cruise($request, $slug)->load('selectedRoute');
        $route = $cruise->selectedRoute;
        abort_if($route === null || count($route->geometry ?? []) < 2, 422, 'Calculez et retenez un itinéraire avant de l’exporter.');
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('gpx');
        $xml->writeAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
        $xml->writeAttribute('version', '1.1');
        $xml->writeAttribute('creator', 'Mariwaze');
        $xml->startElement('metadata');
        $xml->writeElement('name', $cruise->title);
        $xml->writeElement('desc', 'Itinéraire de préparation. Calcul : '.($route->computed_at?->toIso8601String() ?? 'date inconnue').'. Vérifiez les données nautiques avant utilisation.');
        $xml->writeElement('time', now()->toIso8601String());
        $xml->endElement();
        $xml->startElement('trk');
        $xml->writeElement('name', $cruise->title.' — '.$route->label);
        $xml->startElement('trkseg');
        foreach ($route->geometry as $point) {
            $xml->startElement('trkpt');
            $xml->writeAttribute('lat', (string) $point['latitude']);
            $xml->writeAttribute('lon', (string) $point['longitude']);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, [
            'Content-Type' => 'application/gpx+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.(Str::slug($cruise->title) ?: 'croisiere').'.gpx"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function check(Request $request, string $slug, int $item): RedirectResponse
    {
        $cruise = $this->cruise($request, $slug);
        $this->authorize('update', $cruise);
        $data = $request->validate(['checked' => ['required', 'boolean']]);
        $check = CruiseChecklistItem::whereHas('checklist', fn ($query) => $query->where('cruise_id', $cruise->id))->findOrFail($item);
        $check->update($data);

        return back();
    }
}
