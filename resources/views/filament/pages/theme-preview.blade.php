@php
    $c = $colors;

    $primary = $c['primary_color'] ?? '#eab308';
    $primaryHover = $c['primary_hover_color'] ?? '#d3a308';
    $secondary = $c['secondary_color'] ?? '#334155';
    $secondaryHover = $c['secondary_hover_color'] ?? '#283544';
    $accent = $c['accent_color'] ?? '#06b6d4';
    $success = $c['success_color'] ?? '#22c55e';
    $warning = $c['warning_color'] ?? '#f59e0b';
    $danger = $c['danger_color'] ?? '#ef4444';
    $info = $c['info_color'] ?? '#3b82f6';
    $text = $c['text_color'] ?? '#1f2937';
    $muted = $c['text_muted_color'] ?? '#6b7280';
    $heading = $c['heading_color'] ?? '#111827';
    $surface = $c['surface_color'] ?? '#ffffff';
    $border = $c['border_color'] ?? '#e5e7eb';
    $background = $c['background_color'] ?? '#f9fafb';

    $swatches = [
        'Principale' => $primary,
        'Principal (survol)' => $primaryHover,
        'Secondaire' => $secondary,
        'Secondaire (survol)' => $secondaryHover,
        'Accent' => $accent,
        'Succès' => $success,
        'Avertissement' => $warning,
        'Danger' => $danger,
        'Info' => $info,
    ];
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div class="rounded-xl border p-4" style="background-color: {{ $surface }}; border-color: {{ $border }}">
        <p class="text-sm font-semibold" style="color: {{ $heading }}">Aperçu des composants</p>

        <div class="mt-4 space-y-1">
            <p class="text-base font-bold" style="color: {{ $heading }}">Titre de section</p>
            <p class="text-sm" style="color: {{ $text }}">
                Texte normal du paragraphe pour vérifier la lisibilité du contenu sur la surface choisie.
            </p>
            <p class="text-xs" style="color: {{ $muted }}">Texte estompé (meta-données, descriptions, pied de liste)</p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium text-white"
                style="background-color: {{ $primary }}"
            >
                Bouton principal
            </button>
            <button
                type="button"
                class="rounded-lg px-4 py-2 text-sm font-medium text-white"
                style="background-color: {{ $secondary }}"
            >
                Bouton secondaire
            </button>
            <a href="#" class="text-sm font-medium no-underline" style="color: {{ $primary }}">Lien</a>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-medium text-white" style="background-color: {{ $success }}">
                Commande livrée
            </span>
            <span class="rounded-full px-3 py-1 text-xs font-medium text-white" style="background-color: {{ $warning }}">
                Stock faible
            </span>
            <span class="rounded-full px-3 py-1 text-xs font-medium text-white" style="background-color: {{ $danger }}">
                Annulée
            </span>
            <span class="rounded-full px-3 py-1 text-xs font-medium text-white" style="background-color: {{ $info }}">
                Nouveau
            </span>
        </div>
    </div>

    <div class="rounded-xl border p-4" style="background-color: {{ $background }}; border-color: {{ $border }}">
        <p class="text-sm font-semibold" style="color: {{ $heading }}">Palette</p>

        <div class="mt-4 grid grid-cols-2 gap-2">
            @foreach ($swatches as $label => $hex)
                <div class="flex items-center gap-2 rounded-lg p-2" style="background-color: {{ $surface }}">
                    <span
                        class="inline-block h-6 w-6 shrink-0 rounded-md border"
                        style="background-color: {{ $hex }}; border-color: {{ $border }}"
                    ></span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium" style="color: {{ $text }}">{{ $label }}</p>
                        <p class="text-[10px] uppercase" style="color: {{ $muted }}">{{ $hex }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-[10px]" style="color: {{ $muted }}">
            Fond : {{ $background }} &middot; Surface : {{ $surface }} &middot; Bordure : {{ $border }}
        </p>
    </div>
</div>