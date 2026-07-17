<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('components.website.index');
})->name('website.home');

Route::prefix('web')->group(function () {

    Route::get('/about', fn() => view('components.website.about'))
        ->name('website.about');

    Route::get('/features', fn() => view('components.website.features'))
        ->name('website.features');

    Route::get('/pricing', fn() => view('components.website.pricing'))
        ->name('website.pricing');

    Route::get('/contact', fn() => view('components.website.contact'))
        ->name('website.contact');

    Route::get('/privacy', fn() => view('components.website.privacy-policy'))
        ->name('website.privacy');

    Route::get('/refund-policy', fn() => view('components.website.refund-policy'))
        ->name('website.refund-policy');

    Route::get('/terms-conditions', fn() => view('components.website.terms-conditions'))
        ->name('website.terms-conditions');

    Route::get('/terms-of-use', fn() => view('components.website.terms-of-use'))
        ->name('website.terms-of-use');

    Route::get('/demo', fn() => view('components.website.demo'))
        ->name('website.demo');

    /* ── Company / Resources pages ─────────────────────────────
       Content is managed from the super-admin panel and stored in the
       website_pages table (keyed by slug). The blades render this data
       and fall back to built-in defaults when a row is missing. ──────── */
    $dynamicPage = fn(string $slug, string $view) =>
        view($view, ['page' => \App\Models\WebsitePage::where('slug', $slug)->first()]);

    Route::get('/why-us', fn() => $dynamicPage('why-us', 'components.website.why-us'))
        ->name('website.why-us');

    Route::get('/services', fn() => $dynamicPage('services', 'components.website.services'))
        ->name('website.services');

    Route::get('/careers', fn() => $dynamicPage('careers', 'components.website.careers'))
        ->name('website.careers');

    Route::get('/become-an-executive', fn() => $dynamicPage('become-executive', 'components.website.become-executive'))
        ->name('website.become-executive');

    Route::get('/blogs', fn() => view('components.website.blogs', [
        'page'  => \App\Models\WebsitePage::where('slug', 'blogs')->first(),
        'blogs' => \App\Models\Blog::latest()->get(),
    ]))->name('website.blogs');

    Route::get('/blogs/{blog}', function (\App\Models\Blog $blog) {
        // Count a view each time the article is opened (simple analytics).
        $blog->incrementQuietly('views');

        return view('components.website.blog-detail', [
            'blog'   => $blog,
            'recent' => \App\Models\Blog::where('id', '!=', $blog->id)->latest()->take(3)->get(),
        ]);
    })->name('website.blog.detail');

    Route::get('/faqs', fn() => view('components.website.faqs', [
        'faqs'       => \App\Models\Faq::orderBy('category')->orderBy('id')->get(),
        'categories' => \App\Models\Faq::query()->whereNotNull('category')
            ->distinct()->orderBy('category')->pluck('category'),
    ]))->name('website.faqs');
});
