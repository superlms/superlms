{{-- The photo cropper behind <x-admin.photo-cropper model="…">.

     Picking a file opens it in the middle of the window inside the same circle
     the photo is shown in across the panel. It can be dragged into place and
     zoomed (slider or mouse wheel); "Use photo" cuts out the square behind the
     circle (512 × 512 JPEG) and uploads that to the Livewire property named by
     `model`, as a plain file input with wire:model would have. A file the
     browser can't draw (HEIC, say) is uploaded as it is, as before.

     The photo already saved opens the same way (openUrl, asked for by the
     window event `lms-crop-photo`): fetched from this site, fitted, and sent
     to `model` as a new photo.

     Global — not inside the component's markup — because the forms that hold
     the input are rendered by Livewire updates, and scripts added that way
     never run. --}}
@once
    <script>
        window.lmsPhotoCropper = function (model) {
            // The last photo sent, kept out of Alpine's reactive state (a
            // proxied File can't go back into the input).
            let chosen = null;
            // The input shows the photo sent ("photo.jpg"), not "No file chosen".
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
                // Image and circle geometry, in CSS pixels.
                size: 260,
                natW: 0, natH: 0,
                zoom: 1,
                x: 0, y: 0,
                drag: null,

                baseScale() { return Math.max(this.size / this.natW, this.size / this.natH); },
                scale() { return this.baseScale() * this.zoom; },

                imgStyle() {
                    const s = this.scale();
                    return 'width:' + (this.natW * s) + 'px;height:' + (this.natH * s) + 'px;left:' + this.x + 'px;top:' + this.y + 'px';
                },

                // The photo always covers the whole circle.
                clamp() {
                    const s = this.scale();
                    const w = this.natW * s, h = this.natH * s;
                    this.x = Math.min(0, Math.max(this.size - w, this.x));
                    this.y = Math.min(0, Math.max(this.size - h, this.y));
                },

                // Zoom about the circle's centre.
                setZoom(z) {
                    z = Math.min(4, Math.max(1, Number(z) || 1));
                    const old = this.scale();
                    const cx = (this.size / 2 - this.x) / old;
                    const cy = (this.size / 2 - this.y) / old;
                    this.zoom = z;
                    const now = this.scale();
                    this.x = this.size / 2 - cx * now;
                    this.y = this.size / 2 - cy * now;
                    this.clamp();
                },

                pick(e) {
                    const file = e.target.files && e.target.files[0];
                    e.target.value = '';   // the same file can be picked again
                    if (!file) return;
                    this.error = '';
                    if (!/^image\//.test(file.type)) {
                        this.error = 'Please pick an image.';
                        this.open = true;
                        this.src = null;
                        return;
                    }
                    this.fileName = (file.name || 'photo').replace(/\.[^.]+$/, '') || 'photo';
                    const url = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = () => {
                        this.release();
                        this.src = url;
                        this.natW = img.naturalWidth;
                        this.natH = img.naturalHeight;
                        this.size = Math.min(260, window.innerWidth - 96);
                        this.zoom = 1;
                        const s = this.scale();
                        this.x = (this.size - this.natW * s) / 2;
                        this.y = (this.size - this.natH * s) / 2;
                        this.open = true;
                    };
                    img.onerror = () => {
                        // The browser can't draw it — send it as it is.
                        URL.revokeObjectURL(url);
                        this.send(file);
                    };
                    img.src = url;
                },

                // The photo already saved, fitted again. Fetched as a file from
                // this site, so the canvas may read it.
                openUrl(url) {
                    if (!url || this.busy || this.loading) return;
                    this.release();
                    this.error = '';
                    this.fileName = 'photo';
                    this.loading = true;
                    this.open = true;
                    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
                        .then((r) => { if (!r.ok) throw new Error('http ' + r.status); return r.blob(); })
                        .then((blob) => {
                            const src = URL.createObjectURL(blob);
                            const img = new Image();
                            img.onload = () => {
                                this.loading = false;
                                if (!this.open) { URL.revokeObjectURL(src); return; }   // closed meanwhile
                                this.src = src;
                                this.natW = img.naturalWidth;
                                this.natH = img.naturalHeight;
                                this.size = Math.min(260, window.innerWidth - 96);
                                this.zoom = 1;
                                const s = this.scale();
                                this.x = (this.size - this.natW * s) / 2;
                                this.y = (this.size - this.natH * s) / 2;
                            };
                            img.onerror = () => {
                                URL.revokeObjectURL(src);
                                this.loading = false;
                                this.error = 'This photo cannot be cropped here. Please pick a new one instead.';
                            };
                            img.src = src;
                        })
                        .catch(() => {
                            this.loading = false;
                            this.error = 'Could not open the photo. Please try again.';
                        });
                },

                down(e) {
                    if (!this.src || this.busy) return;
                    this.drag = { id: e.pointerId, px: e.clientX, py: e.clientY, x: this.x, y: this.y };
                    try { e.currentTarget.setPointerCapture(e.pointerId); } catch (x) {}
                },
                move(e) {
                    if (!this.drag || this.drag.id !== e.pointerId) return;
                    this.x = this.drag.x + (e.clientX - this.drag.px);
                    this.y = this.drag.y + (e.clientY - this.drag.py);
                    this.clamp();
                },
                up() { this.drag = null; },
                wheel(e) {
                    if (!this.src || this.busy) return;
                    this.setZoom(this.zoom * (e.deltaY < 0 ? 1.1 : 1 / 1.1));
                },

                use() {
                    if (!this.src || this.busy) return;
                    const img = this.$refs.img;
                    const OUT = 512;
                    const s = this.scale();
                    const canvas = document.createElement('canvas');
                    canvas.width = OUT;
                    canvas.height = OUT;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';   // a transparent PNG gets white, not black
                    ctx.fillRect(0, 0, OUT, OUT);
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, -this.x / s, -this.y / s, this.size / s, this.size / s, 0, 0, OUT, OUT);
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
                },
                close() {
                    if (this.busy) return;
                    this.open = false;
                    this.error = '';
                    this.drag = null;
                    this.release();
                    showChosen(this.$refs.input);
                },
            };
        };
    </script>
@endonce
