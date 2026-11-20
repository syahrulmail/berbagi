/*
 * Unggah video beranda.
 *
 * Video dikompres di browser admin sebelum diunggah agar tidak membutuhkan
 * ffmpeg di server (produksi memblokir exec/proc_open). Hasil kompresi
 * ditargetkan di bawah 10 MB; sampul diambil otomatis dari frame pertama
 * bila admin tidak mengunggah gambar sampul.
 */
(function () {
    'use strict';

    var MAX_OUTPUT = 10 * 1024 * 1024;
    var TARGET_BYTES = 9.3 * 1024 * 1024;
    var MAX_EDGE = 720;
    var MIME_CANDIDATES = [
        'video/mp4;codecs=avc1.42E01E,mp4a.40.2',
        'video/mp4',
        'video/webm;codecs=vp9,opus',
        'video/webm;codecs=vp8,opus',
        'video/webm'
    ];

    function pickMime() {
        if (typeof window.MediaRecorder === 'undefined') {
            return '';
        }

        for (var i = 0; i < MIME_CANDIDATES.length; i++) {
            if (MediaRecorder.isTypeSupported(MIME_CANDIDATES[i])) {
                return MIME_CANDIDATES[i];
            }
        }

        return '';
    }

    function once(target, event) {
        return new Promise(function (resolve, reject) {
            function done() {
                target.removeEventListener(event, done);
                target.removeEventListener('error', fail);
                resolve();
            }
            function fail() {
                target.removeEventListener(event, done);
                target.removeEventListener('error', fail);
                reject(new Error('Media error'));
            }
            target.addEventListener(event, done, { once: true });
            target.addEventListener('error', fail, { once: true });
        });
    }

    function formatSize(bytes) {
        if (!bytes) return '0 KB';
        if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function baseName(file) {
        return (file.name || 'video').replace(/\.[^.]+$/, '') || 'video';
    }

    function createVideoElement(file) {
        var video = document.createElement('video');
        video.preload = 'auto';
        video.playsInline = true;
        video.style.cssText = 'position:fixed;left:-9999px;top:0;width:2px;height:2px;opacity:0;pointer-events:none;';
        video.src = URL.createObjectURL(file);
        document.body.appendChild(video);

        return video;
    }

    function removeVideoElement(video) {
        try {
            if (video.src) URL.revokeObjectURL(video.src);
        } catch (e) {}
        if (video.parentNode) video.parentNode.removeChild(video);
    }

    function even(value) {
        return Math.max(2, Math.round(value / 2) * 2);
    }

    function grabPoster(file) {
        var video = createVideoElement(file);

        return once(video, 'loadeddata').then(function () {
            var at = Math.min(0.15, (video.duration || 1) * 0.05);
            if (at > 0) {
                video.currentTime = at;
                return once(video, 'seeked').catch(function () {});
            }
        }).then(function () {
            var w = video.videoWidth || 720;
            var h = video.videoHeight || 720;
            var scale = Math.min(1, MAX_EDGE / Math.max(w, h));
            var canvas = document.createElement('canvas');
            canvas.width = even(w * scale);
            canvas.height = even(h * scale);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', 0.82);
            });
        }).then(function (blob) {
            removeVideoElement(video);
            return blob;
        }).catch(function (error) {
            removeVideoElement(video);
            throw error;
        });
    }

    function compressVideo(file, onProgress) {
        var mime = pickMime();
        if (!mime) {
            return Promise.reject(new Error('MediaRecorder tidak didukung'));
        }

        var video = createVideoElement(file);

        return once(video, 'loadeddata').then(function () {
            var duration = video.duration || 0;
            var sourceW = video.videoWidth || 720;
            var sourceH = video.videoHeight || 720;
            var scale = Math.min(1, MAX_EDGE / Math.max(sourceW, sourceH));
            var outW = even(sourceW * scale);
            var outH = even(sourceH * scale);
            var ratio = sourceH / sourceW;

            // Bitrate disesuaikan agar hasil akhir muat di bawah 10 MB.
            var totalKbps = Math.min(2500, Math.max(400, Math.floor((TARGET_BYTES * 8) / (duration || 1) / 1000)));
            var audioKbps = 64;
            var videoKbps = Math.max(300, totalKbps - audioKbps);

            var canvas = document.createElement('canvas');
            canvas.width = outW;
            canvas.height = outH;
            var ctx = canvas.getContext('2d');

            if (!canvas.captureStream) {
                throw new Error('Canvas captureStream tidak didukung');
            }

            var stream = canvas.captureStream(30);

            // Ambil audio asli bila tersedia.
            try {
                var mediaStream = video.captureStream
                    ? video.captureStream()
                    : (video.mozCaptureStream ? video.mozCaptureStream() : null);
                if (mediaStream) {
                    mediaStream.getAudioTracks().forEach(function (track) {
                        stream.addTrack(track);
                    });
                }
            } catch (e) {}

            var chunks = [];
            var recorder = new MediaRecorder(stream, {
                mimeType: mime,
                videoBitsPerSecond: videoKbps * 1000,
                audioBitsPerSecond: audioKbps * 1000
            });

            recorder.ondataavailable = function (event) {
                if (event.data && event.data.size > 0) chunks.push(event.data);
            };

            var stopped = new Promise(function (resolve) {
                recorder.onstop = resolve;
            });

            var raf = null;
            var draw = function () {
                ctx.drawImage(video, 0, 0, outW, outH);
                if (!video.ended && !video.paused) {
                    raf = window.requestAnimationFrame(draw);
                }
            };

            recorder.start(400);

            return video.play().catch(function () {
                video.muted = true;
                return video.play();
            }).then(function () {
                raf = window.requestAnimationFrame(draw);

                var onTime = function () {
                    if (duration > 0 && typeof onProgress === 'function') {
                        onProgress(Math.min(1, video.currentTime / duration));
                    }
                };
                video.addEventListener('timeupdate', onTime);

                return once(video, 'ended').then(function () {
                    video.removeEventListener('timeupdate', onTime);
                    if (raf) window.cancelAnimationFrame(raf);
                    recorder.stop();
                    return stopped;
                });
            }).then(function () {
                removeVideoElement(video);

                return {
                    blob: new Blob(chunks, { type: mime.split(';')[0] }),
                    mime: mime,
                    ratio: ratio
                };
            }).catch(function (error) {
                if (raf) window.cancelAnimationFrame(raf);
                try { if (recorder.state !== 'inactive') recorder.stop(); } catch (e) {}
                removeVideoElement(video);
                throw error;
            });
        });
    }

    function setFile(input, file) {
        if (!input || !file) return false;

        try {
            var transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            return true;
        } catch (e) {
            return false;
        }
    }

    function setupRow(row) {
        var videoInput = row.querySelector('[data-video-input]');
        var posterInput = row.querySelector('[data-poster-input]');
        var ratioInput = row.querySelector('[data-ratio-input]');
        var removeInput = row.querySelector('[data-remove-input]');
        var preview = row.querySelector('[data-preview]');
        var status = row.querySelector('[data-status]');
        var progress = row.querySelector('[data-progress]');
        var bar = row.querySelector('[data-progress-bar]');
        var busy = false;

        row.__isBusy = function () { return busy; };

        function setStatus(text, isError) {
            if (!status) return;
            status.textContent = text || '';
            status.classList.toggle('is-error', !!isError);
        }

        function setProgress(value) {
            if (!progress) return;
            if (value === null) {
                progress.hidden = true;
                if (bar) bar.style.width = '0%';
                return;
            }
            progress.hidden = false;
            if (bar) bar.style.width = Math.round(value * 100) + '%';
        }

        function setPreview(blob) {
            if (!preview || !blob) return;
            var url = URL.createObjectURL(blob);
            preview.style.backgroundImage = 'url("' + url + '")';
            preview.textContent = '';
            preview.classList.add('has-image');
        }

        var removeButton = row.querySelector('[data-remove]');
        if (removeButton) {
            removeButton.addEventListener('click', function () {
                if (removeInput) removeInput.value = '1';
                row.style.display = 'none';
                if (videoInput) videoInput.value = '';
                if (posterInput) posterInput.value = '';
            });
        }

        if (posterInput) {
            posterInput.addEventListener('change', function () {
                var file = posterInput.files && posterInput.files[0];
                if (file) setPreview(file);
            });
        }

        if (!videoInput) return;

        videoInput.addEventListener('change', function () {
            var file = videoInput.files && videoInput.files[0];
            if (!file) return;

            if (!/^video\//.test(file.type) && !/\.(mp4|webm|mov|m4v)$/i.test(file.name)) {
                setStatus('Berkas harus berupa video MP4/WebM.', true);
                return;
            }

            if (file.size > 1024 * 1024 * 1024) {
                setStatus('Ukuran berkas asli terlalu besar (maks 1 GB).', true);
                return;
            }

            if (typeof window.MediaRecorder === 'undefined') {
                setStatus('Browser ini tidak mendukung kompresi video. Unggah video yang sudah ≤ 10 MB.', true);
                return;
            }

            if (file.size <= MAX_OUTPUT && !/\.(mov|m4v)$/i.test(file.name)) {
                // Sudah cukup ringan: lewati kompresi.
                if (ratioInput && !ratioInput.value) {
                    previewRatio(file, ratioInput);
                }
                setStatus('Siap diunggah: ' + formatSize(file.size) + ' (sudah di bawah 10 MB).');
                if (posterInput && (!posterInput.files || !posterInput.files.length)) {
                    grabPoster(file).then(function (blob) {
                        if (blob) setFile(posterInput, new File([blob], baseName(file) + '-sampul.jpg', { type: 'image/jpeg' }));
                    }).catch(function () {});
                }
                return;
            }

            busy = true;
            setStatus('Mengompres video…');
            setProgress(0.02);

            var posterPromise = (posterInput && (!posterInput.files || !posterInput.files.length))
                ? grabPoster(file).then(function (blob) {
                    if (blob) {
                        setFile(posterInput, new File([blob], baseName(file) + '-sampul.jpg', { type: 'image/jpeg' }));
                        setPreview(blob);
                    }
                }).catch(function () {})
                : Promise.resolve();

            posterPromise.then(function () {
                return compressVideo(file, function (value) {
                    setProgress(value);
                    setStatus('Mengompres video… ' + Math.round(value * 100) + '%');
                });
            }).then(function (result) {
                if (result.blob.size > MAX_OUTPUT) {
                    setStatus('Hasil kompresi ' + formatSize(result.blob.size) + ' melebihi 10 MB. Perpendek durasi video.', true);
                    return;
                }

                var extension = result.mime.indexOf('mp4') !== -1 ? '.mp4' : '.webm';
                var compressed = new File([result.blob], baseName(file) + extension, { type: result.mime.split(';')[0] });

                if (!setFile(videoInput, compressed)) {
                    setStatus('Browser gagal menyiapkan hasil kompresi. Coba browser lain.', true);
                    return;
                }

                if (ratioInput) ratioInput.value = result.ratio.toFixed(3);
                setStatus('Siap diunggah: ' + formatSize(result.blob.size) + ' (asli ' + formatSize(file.size) + ').');
            }).catch(function () {
                setStatus('Gagal mengompres di browser. Unggah video yang sudah ≤ 10 MB.', true);
            }).then(function () {
                busy = false;
                setProgress(null);
            });
        });
    }

    function previewRatio(file, ratioInput) {
        var video = createVideoElement(file);
        once(video, 'loadedmetadata').then(function () {
            if (video.videoWidth > 0) {
                ratioInput.value = (video.videoHeight / video.videoWidth).toFixed(3);
            }
            removeVideoElement(video);
        }).catch(function () {
            removeVideoElement(video);
        });
    }

    function init() {
        var root = document.querySelector('[data-video-form]');
        if (!root) return;

        var rows = root.querySelectorAll('[data-video-row]');
        Array.prototype.forEach.call(rows, setupRow);

        var addButton = root.querySelector('[data-video-add]');
        var template = root.querySelector('[data-video-template]');
        var nextIndex = parseInt(root.getAttribute('data-next-index'), 10) || rows.length;

        if (addButton && template) {
            addButton.addEventListener('click', function () {
                var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
                var holder = document.createElement('div');
                holder.innerHTML = html.trim();
                var row = holder.firstElementChild;
                if (!row) return;

                var list = root.querySelector('[data-video-list]');
                (list || root).appendChild(row);
                setupRow(row);
                nextIndex++;

                var input = row.querySelector('[data-video-input]');
                if (input) input.focus();
            });
        }

        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function (event) {
                var active = root.querySelectorAll('[data-video-row]');
                for (var i = 0; i < active.length; i++) {
                    if (active[i].__isBusy && active[i].__isBusy()) {
                        event.preventDefault();
                        window.alert('Video masih dikompres. Tunggu hingga selesai lalu simpan.');
                        return;
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
