<?php

use App\Support\WebsiteDesign;
use Illuminate\Support\Facades\Route;

Route::middleware('track.website')->group(function () {

Route::get('/', function () {
    return view(WebsiteDesign::view('index'));
})->name('website.home');

Route::prefix('web')->group(function () {

    Route::get('/about', fn() => view(WebsiteDesign::view('about')))
        ->name('website.about');

    Route::get('/features', fn() => view(WebsiteDesign::view('features')))
        ->name('website.features');

    Route::get('/pricing', fn() => view(WebsiteDesign::view('pricing')))
        ->name('website.pricing');

    Route::get('/contact', fn() => view(WebsiteDesign::view('contact')))
        ->name('website.contact');

    Route::get('/privacy', fn() => view(WebsiteDesign::view('privacy-policy')))
        ->name('website.privacy');

    Route::get('/refund-policy', fn() => view(WebsiteDesign::view('refund-policy')))
        ->name('website.refund-policy');

    Route::get('/terms-conditions', fn() => view(WebsiteDesign::view('terms-conditions')))
        ->name('website.terms-conditions');

    Route::get('/terms-of-use', fn() => view(WebsiteDesign::view('terms-of-use')))
        ->name('website.terms-of-use');

    Route::get('/demo', fn() => view(WebsiteDesign::view('demo')))
        ->name('website.demo');

    /* ── Company / Resources pages ─────────────────────────────
       Content is managed from the super-admin panel and stored in the
       website_pages table (keyed by slug). The blades render this data
       and fall back to built-in defaults when a row is missing. ──────── */
    $dynamicPage = fn(string $slug, string $view) =>
        view($view, ['page' => \App\Models\WebsitePage::where('slug', $slug)->first()]);

    Route::get('/why-us', fn() => $dynamicPage('why-us', WebsiteDesign::view('why-us')))
        ->name('website.why-us');

    Route::get('/services', fn() => $dynamicPage('services', WebsiteDesign::view('services')))
        ->name('website.services');

    Route::get('/careers', fn() => $dynamicPage('careers', WebsiteDesign::view('careers')))
        ->name('website.careers');

    Route::get('/become-an-executive', fn() => $dynamicPage('become-executive', WebsiteDesign::view('become-executive')))
        ->name('website.become-executive');

    Route::get('/blogs', fn() => view(WebsiteDesign::view('blogs'), [
        'page'  => \App\Models\WebsitePage::where('slug', 'blogs')->first(),
        'blogs' => \App\Models\Blog::latest()->get(),
    ]))->name('website.blogs');

    Route::get('/blogs/{blog}', function (\App\Models\Blog $blog) {
        // Count a view each time the article is opened (simple analytics).
        $blog->incrementQuietly('views');

        return view(WebsiteDesign::view('blog-detail'), [
            'blog'   => $blog,
            'recent' => \App\Models\Blog::where('id', '!=', $blog->id)->latest()->take(3)->get(),
        ]);
    })->name('website.blog.detail');

    Route::get('/faqs', fn() => view(WebsiteDesign::view('faqs'), [
        'faqs'       => \App\Models\Faq::orderBy('category')->orderBy('id')->get(),
        'categories' => \App\Models\Faq::query()->whereNotNull('category')
            ->distinct()->orderBy('category')->pluck('category'),
    ]))->name('website.faqs');
});

}); // track.website

// A demo request's own link (sent in its thank-you): pick a free date and
// time; the meeting's link is made and sent the moment one is picked. Private
// to the one who asked, so not counted in the website's visits.
Route::prefix('web')->group(function () {
    Route::get('/demo/book/{token}', [\App\Http\Controllers\DemoBookingController::class, 'show'])
        ->name('website.demo.book');
    Route::post('/demo/book/{token}', [\App\Http\Controllers\DemoBookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('website.demo.book.store');
    // The same link as a WhatsApp button opens it when the Meta template's URL
    // was typed a little off (/web/demo/book{token}, /web/demo/{token} …):
    // sent on to the page above (DemoBookingController::loose).
    Route::get('/demo/{path}', [\App\Http\Controllers\DemoBookingController::class, 'loose'])
        ->where('path', '.+')
        ->middleware('throttle:30,1');
});
Route::get('/{path}', [\App\Http\Controllers\DemoBookingController::class, 'loose'])
    ->where('path', '[A-Za-z0-9]{40}')
    ->middleware('throttle:30,1');
