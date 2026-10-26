<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carnet — {{ $cruise->title }}</title>
    <style>
        body{font:16px/1.6 system-ui,sans-serif;color:#163449;background:#f5f8fa;margin:0;padding:24px}main{max-width:900px;margin:auto}h1{font-size:2rem;line-height:1.2}h2{margin-top:2rem}article,section{background:white;border:1px solid #cbd5df;border-radius:12px;padding:20px;margin:16px 0}small,.meta{color:#526579}label{display:block;padding:10px 0;border-bottom:1px solid #e5eaee}input{width:20px;height:20px;vertical-align:middle;margin-right:10px}.warning{border-left:4px solid #c2611a;padding:12px;background:#fff4e8}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:8px;border-bottom:1px solid #e5eaee}@media print{body{background:white;padding:0;font-size:11pt}article,section{break-inside:avoid}h2{break-after:avoid}.help{display:none}}
    </style>
</head>
<body><main>
    <p>MARIWAZE · CARNET DE ROUTE</p>
    <h1>{{ $cruise->title }}</h1>
    <p>{{ $cruise->departure_port }} → {{ $cruise->destination_port }}</p>
    <p class="meta">Départ : {{ $cruise->departure_date?->format('d/m/Y') }} · {{ $cruise->crew_count }} personnes · {{ $cruise->duration_days }} jours</p>
    <p class="meta">Exporté le {{ $exportedAt->format('d/m/Y à H:i T') }}. Itinéraire calculé le {{ $route?->computed_at?->format('d/m/Y à H:i T') ?? 'non calculé' }}.</p>
    <p class="help">Ce fichier contient les étapes et la checklist et s’ouvre sans connexion. Utilisez « Imprimer » dans votre navigateur pour le papier ou un PDF. Les coches modifiées ici restent dans cette page ouverte : elles ne sont pas synchronisées et peuvent être perdues à la fermeture. Imprimez la page pour les conserver.</p>
    <p class="warning">Copie datée, sans actualisation météo ni carte interactive. Les prévisions et profondeurs restent à vérifier avant le départ avec les informations nautiques officielles.</p>
    @if ($cruise->boat)
        <section><h2>Bateau</h2><p>{{ $cruise->boat->name }} · tirant d’eau {{ $cruise->boat->draft }} m · vitesse de calcul {{ $cruise->boat->avg_speed }} nœuds.</p></section>
    @endif
    <h2>Étapes — {{ $route?->label ?? 'aucun itinéraire retenu' }}</h2>
    @forelse ($route?->segments ?? [] as $leg)
        <article><h3>Jour {{ $leg->day }} · {{ $leg->from_name }} → {{ $leg->to_name }}</h3>
            <p>{{ $leg->distance_nm }} milles · {{ $leg->duration_hours ?? 'Durée inconnue' }} h · {{ $leg->overnight }}</p>
            <p>Position d’arrivée : {{ $leg->to_latitude }}, {{ $leg->to_longitude }}</p>
            <p>Profondeur minimale : {{ $leg->min_depth_meters === null ? 'non renseignée' : $leg->min_depth_meters.' m' }}</p>
            <p>Météo : {{ $leg->weather_snapshot['label'] ?? 'À confirmer' }}. {{ $leg->weather_snapshot['detail'] ?? 'Données indisponibles.' }}</p>
            @if (isset($leg->weather_snapshot['coverage_percent']))<p>Couverture des mesures : {{ $leg->weather_snapshot['coverage_percent'] }} %.</p>@endif
            @if ($leg->note)<p>{{ $leg->note }}</p>@endif
        </article>
    @empty
        <p>Calculez et retenez un itinéraire, puis téléchargez à nouveau ce carnet pour y inclure les étapes.</p>
    @endforelse
    <h2>Checklist de préparation</h2>
    @forelse ($checklists as $checklist)
        <section><h3>{{ $checklist->name }}</h3>
        @foreach ($checklist->items->groupBy('section') as $section => $items)
            <h4>{{ $section }}</h4>
            @foreach ($items as $item)
                <label><input type="checkbox" @checked($item->checked)> {{ $item->label }} @if($item->quantity) — {{ $item->quantity }} @endif @if($item->required) (requis) @endif</label>
            @endforeach
        @endforeach
        </section>
    @empty
        <p>Aucune checklist enregistrée pour cette croisière.</p>
    @endforelse
</main></body></html>
