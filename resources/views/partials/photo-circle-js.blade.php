{{-- The Profile tool behind <x-admin.photo-circle method="…">.

     The window event `lms-circle-photo` { method, url, circle } opens the
     photo in the middle of the window with the round circle the lists show
     over it — where it was set before, else at the top of the photo (as the
     lists show it with none set). The photo is dragged under the circle and
     zoomed in or out (the slider, − and +, the mouse wheel) to show less or
     more of it; the previews beside it show the circle as the lists will.
     Save hands the circle to the Livewire method named by `method` as
     fractions of the photo — x, y, w, h — and the photo itself is left as it
     is. Nothing is cut here, so the photo may come from the media host.

     Global, as the photo editor's (partials/photo-editor-js). --}}
@once
    <script>
        window.lmsPhotoCircle = function (method) {
            // Which opening is current: a photo that loads after Cancel is dropped.
            let ticket = 0;
            const MAX_ZOOM = 5;   // the circle down to a fifth of the photo's shorter side
            const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));

            return {
                method: method,
                open: false,
                busy: false,
                loading: false,
                error: '',
                src: null,
                natW: 0, natH: 0,
                // The square on screen and the room round the circle in it
                // (the photo dimmed there) — CSS pixels.
                stage: 320,
                edge: 24,
                // The circle's square on the photo — photo pixels.
                c: { x: 0, y: 0, s: 1 },
                drag: null,

                // ── Opening ────────────────────────────────────────────────
                openUrl(url, circle) {
                    if (!url || this.busy) return;
                    this.error = '';
                    this.src = null;
                    this.loading = true;
                    this.open = true;
                    const mine = ++ticket;
                    const img = new Image();
                    img.onload = () => {
                        if (mine !== ticket) return;
                        this.loading = false;
                        this.natW = img.naturalWidth;
                        this.natH = img.naturalHeight;
                        this.stage = Math.round(Math.max(200, Math.min(360, window.innerWidth - 96, window.innerHeight - 300)));
                        this.start(circle);
                        this.src = url;
                    };
                    img.onerror = () => {
                        if (mine !== ticket) return;
                        this.loading = false;
                        this.error = 'Could not open the photo. Please try again.';
                    };
                    img.src = url;
                },

                // The circle set before, else the top of the photo.
                start(circle) {
                    const W = this.natW, H = this.natH, max = Math.min(W, H);
                    if (circle && circle.w > 0 && circle.h > 0) {
                        this.place(circle.x * W, circle.y * H, Math.min(circle.w * W, circle.h * H));
                    } else {
                        this.place((W - max) / 2, 0, max);
                    }
                },
                // The circle kept inside the photo, never bigger than its shorter side.
                place(x, y, s) {
                    const W = this.natW, H = this.natH, max = Math.min(W, H);
                    s = clamp(s, max / MAX_ZOOM, max);
                    this.c = { x: clamp(x, 0, W - s), y: clamp(y, 0, H - s), s: s };
                },

                // ── Zoom: 1 = the whole of the shorter side in the circle ──
                get dia() { return this.stage - 2 * this.edge; },
                get k() { return this.dia / this.c.s; },
                get zoom() { return Math.min(this.natW, this.natH) / this.c.s; },
                setZoom(z) {
                    const s = Math.min(this.natW, this.natH) / clamp(z, 1, MAX_ZOOM);
                    // About the circle's middle.
                    const mx = this.c.x + this.c.s / 2, my = this.c.y + this.c.s / 2;
                    this.place(mx - s / 2, my - s / 2, s);
                },
                zoomBy(f) { if (this.src && !this.busy) this.setZoom(this.zoom * f); },
                wheel(e) { this.zoomBy(e.deltaY < 0 ? 1.1 : 1 / 1.1); },

                // ── Drawing ────────────────────────────────────────────────
                imgStyle() {
                    const k = this.k;
                    return 'left:' + (this.edge - this.c.x * k) + 'px;top:' + (this.edge - this.c.y * k) + 'px;'
                        + 'width:' + (this.natW * k) + 'px;height:' + (this.natH * k) + 'px';
                },
                ringStyle() {
                    return 'left:' + this.edge + 'px;top:' + this.edge + 'px;width:' + this.dia + 'px;height:' + this.dia + 'px;'
                        + 'box-shadow:0 0 0 9999px rgba(0,0,0,.55);border:2px solid #fff';
                },
                // The circle, `size` across, as the lists will draw it.
                previewStyle(size) {
                    const k = size / this.c.s;
                    return 'width:' + size + 'px;height:' + size + 'px;background-repeat:no-repeat;'
                        + 'background-image:url(' + JSON.stringify(this.src || '') + ');'
                        + 'background-size:' + (this.natW * k) + 'px ' + (this.natH * k) + 'px;'
                        + 'background-position:' + (-this.c.x * k) + 'px ' + (-this.c.y * k) + 'px';
                },

                // ── The photo dragged under the circle ─────────────────────
                down(e) {
                    if (!this.src || this.busy) return;
                    e.preventDefault();
                    this.drag = { id: e.pointerId, px: e.clientX, py: e.clientY, x: this.c.x, y: this.c.y };
                    try { e.currentTarget.setPointerCapture(e.pointerId); } catch (x) {}
                },
                move(e) {
                    const d = this.drag;
                    if (!d || d.id !== e.pointerId) return;
                    const k = this.k;
                    this.place(d.x - (e.clientX - d.px) / k, d.y - (e.clientY - d.py) / k, this.c.s);
                },
                up() { this.drag = null; },

                // ── Saving ─────────────────────────────────────────────────
                save() {
                    if (!this.src || this.busy) return;
                    const W = this.natW, H = this.natH, c = this.c;
                    this.busy = true;
                    this.error = '';
                    Promise.resolve(this.$wire.call(this.method, c.x / W, c.y / H, c.s / W, c.s / H))
                        .then(() => { this.busy = false; this.close(); })
                        .catch(() => { this.busy = false; this.error = 'Could not save. Please try again.'; });
                },

                close() {
                    if (this.busy) return;
                    ticket++;
                    this.open = false;
                    this.loading = false;
                    this.error = '';
                    this.drag = null;
                    this.src = null;
                },
            };
        };
    </script>
@endonce
