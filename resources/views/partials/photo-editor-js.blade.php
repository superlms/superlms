{{-- The photo editor behind <x-admin.photo-editor model="…">.

     Picking a file (or the window event `lms-crop-photo` with this site's copy
     of the photo already saved) opens it in the middle of the window, the
     whole photo kept to begin with. The part kept is cropped from any side by
     its edges and corners and moved by dragging inside it; the dotted circle
     in it — its top square's, where the face is — is what the round photos in
     the lists show,
     and the previews beside it show that circle as the lists will. The button
     cuts the part out (its longer side at most 1024 px, JPEG) and uploads it
     to the Livewire property named by `model`, as a plain file input with
     wire:model would have. A file the browser can't draw (HEIC, say) is
     uploaded as it is.

     Global, as the circle cropper's (partials/photo-cropper-js): the forms
     holding the input are drawn by Livewire updates, where scripts never run. --}}
@once
    <script>
        window.lmsPhotoEditor = function (model) {
            // Kept out of Alpine's reactive state: the picture drawn into the
            // canvas, and the last file sent (a proxied File can't go back into
            // the input).
            let picture = null;
            let chosen = null;
            // Which opening is current: a picture that loads after Cancel, or
            // after another was opened, is dropped.
            let ticket = 0;
            const OUT = 1024;   // longer side of what is sent, at most
            const MIN = 24;     // smallest side of the part kept, on screen
            const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
            const showChosen = (input) => {
                if (!input) return;
                try {
                    const dt = new DataTransfer();
                    if (chosen) dt.items.add(chosen);
                    input.files = dt.files;
                } catch (x) {}
            };

            return {
                model: model,
                open: false,
                busy: false,
                loading: false,
                error: '',
                src: null,
                fileName: 'photo',
                natW: 0, natH: 0,
                // The photo on screen, and the part kept on it — CSS pixels.
                dispW: 0, dispH: 0,
                box: { x: 0, y: 0, w: 0, h: 0 },
                drag: null,
                handles: [
                    { k: 'nw', cursor: 'nwse-resize', style: 'left:-8px;top:-8px' },
                    { k: 'n',  cursor: 'ns-resize',   style: 'left:calc(50% - 8px);top:-8px' },
                    { k: 'ne', cursor: 'nesw-resize', style: 'right:-8px;top:-8px' },
                    { k: 'e',  cursor: 'ew-resize',   style: 'right:-8px;top:calc(50% - 8px)' },
                    { k: 'se', cursor: 'nwse-resize', style: 'right:-8px;bottom:-8px' },
                    { k: 's',  cursor: 'ns-resize',   style: 'left:calc(50% - 8px);bottom:-8px' },
                    { k: 'sw', cursor: 'nesw-resize', style: 'left:-8px;bottom:-8px' },
                    { k: 'w',  cursor: 'ew-resize',   style: 'left:-8px;top:calc(50% - 8px)' },
                ],

                // ── Opening ────────────────────────────────────────────────
                pick(e) {
                    const file = e.target.files && e.target.files[0];
                    e.target.value = '';   // the same file can be picked again
                    if (!file) return;
                    this.error = '';
                    if (!/^image\//.test(file.type)) {
                        this.release();
                        this.error = 'Please pick an image.';
                        this.open = true;
                        return;
                    }
                    this.fileName = (file.name || 'photo').replace(/\.[^.]+$/, '') || 'photo';
                    ticket++;
                    this.show(file, () => this.send(file));
                },

                // The photo already saved, cropped again.
                openUrl(url) {
                    if (!url || this.busy || this.loading) return;
                    this.release();
                    this.error = '';
                    this.fileName = 'photo';
                    this.loading = true;
                    this.open = true;
                    ticket++;
                    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
                        .then((r) => { if (!r.ok) throw new Error('http ' + r.status); return r.blob(); })
                        .then((blob) => this.show(blob, () => {
                            this.error = 'This photo cannot be cropped here. Please pick a new one instead.';
                        }))
                        .catch(() => {
                            this.loading = false;
                            this.error = 'Could not open the photo. Please try again.';
                        });
                },

                // A picture (File or Blob) laid out on screen, all of it kept.
                show(blob, cannotDraw) {
                    const mine = ticket;
                    const url = URL.createObjectURL(blob);
                    const img = new Image();
                    img.onload = () => {
                        if (mine !== ticket) { URL.revokeObjectURL(url); return; }
                        this.loading = false;
                        this.release();
                        picture = img;
                        this.src = url;
                        this.natW = img.naturalWidth;
                        this.natH = img.naturalHeight;
                        const wide = window.innerWidth >= 768;
                        const maxW = Math.max(160, Math.min(560, window.innerWidth - (wide ? 260 : 96)));
                        const maxH = Math.max(160, Math.min(460, window.innerHeight - 280));
                        const k = Math.min(maxW / this.natW, maxH / this.natH);
                        this.dispW = Math.round(this.natW * k);
                        this.dispH = Math.round(this.natH * k);
                        this.reset();
                        this.open = true;
                    };
                    img.onerror = () => {
                        URL.revokeObjectURL(url);
                        if (mine !== ticket) return;
                        this.loading = false;
                        cannotDraw();
                    };
                    img.src = url;
                },

                reset() { this.box = { x: 0, y: 0, w: this.dispW, h: this.dispH }; },

                // ── The part kept ──────────────────────────────────────────
                boxStyle() {
                    const b = this.box;
                    return 'left:' + b.x + 'px;top:' + b.y + 'px;width:' + b.w + 'px;height:' + b.h + 'px;'
                        + 'box-shadow:0 0 0 9999px rgba(0,0,0,.55);outline:2px solid #fff';
                },
                // The circle the lists show: the top square's (across, the middle).
                circleStyle() {
                    const b = this.box;
                    const s = Math.min(b.w, b.h);
                    return 'left:' + (b.w - s) / 2 + 'px;top:0px;width:' + s + 'px;height:' + s + 'px';
                },
                // That circle, `size` across, as the lists will draw it.
                previewStyle(size) {
                    const b = this.box;
                    const s = Math.max(1, Math.min(b.w, b.h));
                    const k = size / s;
                    const sx = b.x + (b.w - s) / 2;
                    const sy = b.y;
                    return 'width:' + size + 'px;height:' + size + 'px;background-repeat:no-repeat;'
                        + 'background-image:url(' + this.src + ');'
                        + 'background-size:' + (this.dispW * k) + 'px ' + (this.dispH * k) + 'px;'
                        + 'background-position:' + (-sx * k) + 'px ' + (-sy * k) + 'px';
                },

                down(e) {
                    if (!this.src || this.busy) return;
                    const target = e.target.closest ? e.target.closest('[data-h]') : null;
                    if (!target) return;
                    e.preventDefault();
                    this.drag = { id: e.pointerId, h: target.dataset.h, px: e.clientX, py: e.clientY, box: { ...this.box } };
                    try { e.currentTarget.setPointerCapture(e.pointerId); } catch (x) {}
                },
                move(e) {
                    const d = this.drag;
                    if (!d || d.id !== e.pointerId) return;
                    const dx = e.clientX - d.px;
                    const dy = e.clientY - d.py;
                    const W = this.dispW, H = this.dispH;
                    let { x, y, w, h } = d.box;
                    if (d.h === 'move') {
                        x = clamp(x + dx, 0, W - w);
                        y = clamp(y + dy, 0, H - h);
                    } else {
                        if (d.h.includes('w')) { const nx = clamp(x + dx, 0, x + w - MIN); w += x - nx; x = nx; }
                        if (d.h.includes('e')) { w = clamp(w + dx, MIN, W - x); }
                        if (d.h.includes('n')) { const ny = clamp(y + dy, 0, y + h - MIN); h += y - ny; y = ny; }
                        if (d.h.includes('s')) { h = clamp(h + dy, MIN, H - y); }
                    }
                    this.box = { x, y, w, h };
                },
                up() { this.drag = null; },

                // ── Sending ────────────────────────────────────────────────
                use() {
                    if (!this.src || this.busy || !picture) return;
                    const k = this.natW / this.dispW;   // screen → photo pixels
                    const b = this.box;
                    const cx = b.x * k, cy = b.y * k, cw = b.w * k, ch = b.h * k;
                    const out = Math.min(1, OUT / Math.max(cw, ch));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(cw * out));
                    canvas.height = Math.max(1, Math.round(ch * out));
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';   // a transparent PNG gets white, not black
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(picture, cx, cy, cw, ch, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob((blob) => {
                        if (!blob) { this.error = 'Could not prepare the photo. Please try another.'; return; }
                        this.send(new File([blob], this.fileName + '.jpg', { type: 'image/jpeg' }));
                    }, 'image/jpeg', 0.9);
                },

                send(file) {
                    this.open = true;
                    this.busy = true;
                    this.error = '';
                    this.$wire.upload(this.model, file,
                        () => { chosen = file; this.busy = false; this.close(); },
                        () => { this.busy = false; this.open = true; this.error = 'Upload failed. Please try again.'; }
                    );
                },

                release() {
                    if (this.src) URL.revokeObjectURL(this.src);
                    this.src = null;
                    picture = null;
                },
                close() {
                    if (this.busy) return;
                    ticket++;
                    this.open = false;
                    this.loading = false;
                    this.error = '';
                    this.drag = null;
                    this.release();
                    showChosen(this.$refs.input);
                },
            };
        };
    </script>
@endonce
