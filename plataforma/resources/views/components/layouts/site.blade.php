@props(['title' => null, 'description' => null, 'ogImage' => null])
@include('layouts.site', ['slot' => $slot, 'title' => $title, 'description' => $description, 'ogImage' => $ogImage])
