<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
    @continue(empty($url['loc']))
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (! empty($url['lastmod']))
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        @if (! empty($url['changefreq']))
            <changefreq>{{ $url['changefreq'] }}</changefreq>
        @endif
        @if (! empty($url['prior']))
            <priority>{{ $url['prior'] }}</priority>
        @endif
        @foreach ($url['alternates'] ?? [] as $locale => $alternate)
            <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternate }}" />
        @endforeach
    </url>
@endforeach
</urlset>
