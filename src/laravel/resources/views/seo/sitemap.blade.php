@php echo '<'.'?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
@if(!empty($entry['lastmod']))
        <lastmod>{{ $entry['lastmod'] }}</lastmod>
@endif
@if(!empty($entry['changefreq']))
        <changefreq>{{ $entry['changefreq'] }}</changefreq>
@endif
@if(!empty($entry['priority']))
        <priority>{{ $entry['priority'] }}</priority>
@endif
    </url>
@endforeach
</urlset>
