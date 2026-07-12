// ═══════════════════════════════════════════════════════════════
// scanner.js  —  OMR Answer Sheet Scanner (Fixed Corner Detection)
// ═══════════════════════════════════════════════════════════════

let video          = document.getElementById('video');
let canvasOutput   = document.getElementById('canvasOutput');
let ctx            = canvasOutput.getContext('2d');
let debugCanvas    = document.getElementById('debug-canvas');
let videoWrapper   = document.getElementById('video-wrapper');
let statusIndicator = document.getElementById('statusIndicator');

let streaming  = false;
let dst        = null;
let gray       = null;

// Hidden canvas for iOS-compatible frame capture (no cv.VideoCapture)
let _captureCanvas = document.createElement('canvas');
let _captureCtx    = _captureCanvas.getContext('2d', { willReadFrequently: true });

// ── Audio ─────────────────────────────────────────────────────
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let _audioCtx = null;
function playBeep() {
    try {
        if (!_audioCtx) _audioCtx = new AudioCtx();
        const osc  = _audioCtx.createOscillator();
        const gain = _audioCtx.createGain();
        osc.connect(gain);
        gain.connect(_audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, _audioCtx.currentTime);
        gain.gain.setValueAtTime(0.3, _audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, _audioCtx.currentTime + 0.3);
        osc.start(_audioCtx.currentTime);
        osc.stop(_audioCtx.currentTime + 0.3);
    } catch(e) {}
}

// ── OpenCV ready ──────────────────────────────────────────────
function onOpenCvReady() {
    statusIndicator.textContent = 'กำลังเปิดกล้อง กรุณารอสักครู่...';
    if (window.dbg) dbg('OpenCV script loaded, waiting for WASM init...', 'ok');
    cv['onRuntimeInitialized'] = () => {
        if (window.dbg) dbg('OpenCV WASM ready!', 'ok');
        startCamera();
    };
}

// ── Camera start ──────────────────────────────────────────────
function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusIndicator.textContent = 'เบราว์เซอร์ไม่รองรับการเปิดกล้อง (ตรวจสอบ HTTPS)';
        return;
    }
    navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
    })
    .then(stream => {
        if (window.dbg) dbg('Camera stream started', 'ok');
        video.srcObject = stream; video.play(); streaming = true;
    })
    .catch(err => {
        console.error('Camera error:', err);
        if (window.dbg) dbg('Camera ERROR: ' + err.message, 'err');
        statusIndicator.textContent = 'ไม่สามารถเปิดกล้องได้';
    });

    // iOS WebKit (Chrome/Safari on iPhone) fires canplay before videoWidth is ready.
    // We poll until we get valid dimensions before initializing OpenCV mats.
    let _loopStarted = false;

    function tryStartLoop() {
        if (_loopStarted) return;
        if (!video.videoWidth || !video.videoHeight) {
            // Not ready yet — retry in 100ms
            if (window.dbg) dbg('Waiting for video dimensions... (' + video.videoWidth + 'x' + video.videoHeight + ')', 'warn');
            setTimeout(tryStartLoop, 100);
            return;
        }
        _loopStarted = true;
        initMats();
        if (window.dbg) dbg('Video ready: ' + video.videoWidth + 'x' + video.videoHeight + ' px', 'ok');
        statusIndicator.textContent = 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
        requestAnimationFrame(processVideo);
    }

    // Listen to multiple events — iOS needs both
    video.addEventListener('loadedmetadata', tryStartLoop, false);
    video.addEventListener('canplay',        tryStartLoop, false);
    video.addEventListener('playing',        tryStartLoop, false);
}

function initMats() {
    if (dst)  dst.delete();
    if (gray) gray.delete();
    const w = video.videoWidth, h = video.videoHeight;
    canvasOutput.width  = w;
    canvasOutput.height = h;
    _captureCanvas.width  = w;
    _captureCanvas.height = h;
    dst  = new cv.Mat(h, w, cv.CV_8UC1);
    gray = new cv.Mat();
}

// ── Detection constants ────────────────────────────────────────
const AREA_MIN   = 150;
const AREA_MAX   = 80000;
const AR_MIN     = 0.4;
const AR_MAX     = 2.5;
const APPROX_EPS = 0.06;
const FILL_FRAC  = 0.35;

function findSquareMarkers(binaryMat) {
    let contours  = new cv.MatVector();
    let hierarchy = new cv.Mat();
    cv.findContours(binaryMat, contours, hierarchy, cv.RETR_EXTERNAL, cv.CHAIN_APPROX_SIMPLE);
    let markers = [];
    for (let i = 0; i < contours.size(); i++) {
        let cnt  = contours.get(i);
        let area = cv.contourArea(cnt);
        if (area > AREA_MIN && area < AREA_MAX) {
            let peri   = cv.arcLength(cnt, true);
            let approx = new cv.Mat();
            cv.approxPolyDP(cnt, approx, APPROX_EPS * peri, true);
            if (approx.rows === 4) {
                let rect = cv.boundingRect(approx);
                let ar   = rect.width / rect.height;
                if (ar >= AR_MIN && ar <= AR_MAX) {
                    let Mv = cv.moments(cnt);
                    if (Mv.m00 > 0) {
                        let cx = Mv.m10 / Mv.m00;
                        let cy = Mv.m01 / Mv.m00;
                        markers.push({ x: cx, y: cy, area, rect });
                    }
                }
            }
            approx.delete();
        }
        cnt.delete();
    }
    contours.delete(); hierarchy.delete();
    return markers;
}

function pickCorners(markers, W, H) {
    if (markers.length < 4) return null;

    // Calculate simulated reticle size on video dimensions
    // In scanner.php, the reticle has w = 80% (max-width: 340px) and aspect ratio 1:1.414 (A4)
    // On portrait mobile screen, width is typically the limiting factor.
    let reticleW = W * 0.80;
    let reticleH = reticleW * 1.414;

    // If height exceeds 80% of screen height, bound it
    if (reticleH > H * 0.80) {
        reticleH = H * 0.80;
        reticleW = reticleH / 1.414;
    }

    // Calculate center coordinates
    let cx = W / 2;
    let cy = H / 2;

    // Ideal coordinates for reticle corners
    let rx1 = cx - reticleW / 2;
    let rx2 = cx + reticleW / 2;
    let ry1 = cy - reticleH / 2;
    let ry2 = cy + reticleH / 2;

    // Instead of using absolute screen boundaries (0 or W/H), we pick corners closest to the target reticle area
    const spec = [
        { name: 'TL', tx: rx1, ty: ry1 },
        { name: 'TR', tx: rx2, ty: ry1 },
        { name: 'BL', tx: rx1, ty: ry2 },
        { name: 'BR', tx: rx2, ty: ry2 },
    ];

    let picked = [], used = new Set();
    for (let corner of spec) {
        let best = -1, bestDist = Infinity;
        for (let j = 0; j < markers.length; j++) {
            if (used.has(j)) continue;
            let dx = markers[j].x - corner.tx, dy = markers[j].y - corner.ty;
            let d  = dx * dx + dy * dy;
            if (d < bestDist) { bestDist = d; best = j; }
        }
        if (best === -1) return null;
        used.add(best);
        picked.push({ ...markers[best], role: corner.name });
    }
    
    // Map roles to coordinate objects
    let tl = picked.find(p => p.role === 'TL');
    let tr = picked.find(p => p.role === 'TR');
    let bl = picked.find(p => p.role === 'BL');
    let br = picked.find(p => p.role === 'BR');

    // ── RETICLE CONSTRAINTS (Forcing the sheet to align with yellow guidelines) ──
    // Allow up to 25% margin of error around the reticle width/height
    let maxOffset = Math.max(reticleW, reticleH) * 0.25; 
    
    let isAligned = (
        Math.abs(tl.x - rx1) < maxOffset && Math.abs(tl.y - ry1) < maxOffset &&
        Math.abs(tr.x - rx2) < maxOffset && Math.abs(tr.y - ry1) < maxOffset &&
        Math.abs(bl.x - rx1) < maxOffset && Math.abs(bl.y - ry2) < maxOffset &&
        Math.abs(br.x - rx2) < maxOffset && Math.abs(br.y - ry2) < maxOffset
    );

    if (!isAligned) {
        if (window.dbg && _frameCount % 30 === 0) {
            dbg('Reject: Out of yellow reticle bounds (Move paper closer to yellow brackets)', 'warn');
        }
        return null;
    }

    // ── GEOMETRIC SANITY CHECKS (A4 Simulation like Zipgrade) ──
    
    // 1. Calculate side lengths
    let topWidth = Math.hypot(tr.x - tl.x, tr.y - tl.y);
    let bottomWidth = Math.hypot(br.x - bl.x, br.y - bl.y);
    let leftHeight = Math.hypot(bl.x - tl.x, bl.y - tl.y);
    let rightHeight = Math.hypot(br.x - tr.x, br.y - tr.y);

    let avgWidth = (topWidth + bottomWidth) / 2;
    let avgHeight = (leftHeight + rightHeight) / 2;

    // 2. Aspect Ratio Check: A4 is 1:1.414. Width / Height in portrait should ideally be around 0.707
    let aspectRatio = avgWidth / avgHeight;
    if (aspectRatio < 0.50 || aspectRatio > 0.95) {
        if (window.dbg && _frameCount % 30 === 0) {
            dbg('Reject: Aspect Ratio ' + aspectRatio.toFixed(2) + ' (expected 0.50 - 0.95)', 'warn');
        }
        return null;
    }

    // 3. Parallelism Check
    let widthRatio = Math.min(topWidth, bottomWidth) / Math.max(topWidth, bottomWidth);
    let heightRatio = Math.min(leftHeight, rightHeight) / Math.max(leftHeight, rightHeight);
    if (widthRatio < 0.75 || heightRatio < 0.75) {
        if (window.dbg && _frameCount % 30 === 0) {
            dbg('Reject: Not parallel (wRatio: ' + widthRatio.toFixed(2) + ', hRatio: ' + heightRatio.toFixed(2) + ')', 'warn');
        }
        return null;
    }

    return [tl, tr, bl, br];
}

let _cooldown  = false;
let _frameCount = 0;

function processVideo() {
    if (!streaming) return;
    _frameCount++;
    let src = null;
    try {
        // One-time log on first frame to confirm loop is alive
        if (_frameCount === 1 && window.dbg) dbg('processVideo loop STARTED', 'ok');

        // Re-create mats if video resolution changed
        const vw = video.videoWidth, vh = video.videoHeight;
        if (!vw || !vh) { requestAnimationFrame(processVideo); return; }
        if (_captureCanvas.width !== vw || _captureCanvas.height !== vh) {
            if (window.dbg) dbg('Video resized to ' + vw + 'x' + vh + ', reinit', 'warn');
            initMats();
        }

        // Capture frame: draw video to hidden canvas, then create Mat
        _captureCtx.drawImage(video, 0, 0, vw, vh);
        let imageData = _captureCtx.getImageData(0, 0, vw, vh);
        src = cv.matFromImageData(imageData);

        cv.cvtColor(src, gray, cv.COLOR_RGBA2GRAY);
        const W = src.cols, H = src.rows;

        let binary1 = new cv.Mat();
        cv.GaussianBlur(gray, binary1, new cv.Size(3, 3), 0);
        cv.adaptiveThreshold(binary1, binary1, 255, cv.ADAPTIVE_THRESH_GAUSSIAN_C, cv.THRESH_BINARY_INV, 15, 4);

        let canny = new cv.Mat();
        cv.GaussianBlur(gray, canny, new cv.Size(5, 5), 0);
        cv.Canny(canny, canny, 30, 120, 3, false);

        cv.bitwise_or(binary1, canny, dst);
        binary1.delete(); canny.delete();

        let kernel = cv.getStructuringElement(cv.MORPH_RECT, new cv.Size(3, 3));
        cv.morphologyEx(dst, dst, cv.MORPH_CLOSE, kernel);
        kernel.delete();

        let markers = findSquareMarkers(dst);
        let corners = (markers.length >= 4) ? pickCorners(markers, W, H) : null;

        // Log diagnostics every 30 frames to avoid flooding
        if (_frameCount % 30 === 0 && window.dbg) {
            let areaList = markers.slice(0,6).map(m => Math.round(m.area)).join(',');
            dbg('f#' + _frameCount + ' frame=' + W + 'x' + H + ' squares=' + markers.length + (markers.length ? ' areas=[' + areaList + (markers.length>6?'...' : '') + ']' : ''), corners ? 'ok' : 'warn');
        }

        ctx.clearRect(0, 0, canvasOutput.width, canvasOutput.height);
        ctx.strokeStyle = 'rgba(255,165,0,0.6)';
        ctx.lineWidth   = 1;
        for (let m of markers) ctx.strokeRect(m.rect.x, m.rect.y, m.rect.width, m.rect.height);

        const rootEl = document.getElementById('root-container');

        if (corners && !_cooldown) {
            for (let c of corners) {
                ctx.fillStyle = '#10B981';
                ctx.beginPath(); ctx.arc(c.x, c.y, 10, 0, 2 * Math.PI); ctx.fill();
                ctx.fillStyle = '#fff'; ctx.font = 'bold 11px sans-serif'; ctx.textAlign = 'center';
                ctx.fillText(c.role, c.x, c.y + 4);
            }
            rootEl.classList.remove('error'); rootEl.classList.add('success');
            statusIndicator.textContent = 'เจอครบ 4 มุมแล้ว กรุณาถือกล้องนิ่งๆ...';
            processSheet(corners, W, H, src);
        } else if (!_cooldown) {
            rootEl.classList.remove('success'); rootEl.classList.add('error');
            let hint = markers.length === 0 ? 'ไม่เห็นมุมเลย ขยับให้ marker ดำอยู่ในกรอบ'
                     : markers.length <  4  ? 'เห็น ' + markers.length + ' มุม ยังขาดอีก ' + (4 - markers.length) + ' มุม'
                     : corners === null     ? 'เห็นสี่เหลี่ยมแต่ไม่กางเป็นกรอบกระดาษ เล็งใหม่ช้าๆ'
                     :                        'เห็น ' + markers.length + ' สี่เหลี่ยม (มากเกิน) ลดสิ่งที่รบกวนในเฟรม';
            statusIndicator.textContent = hint;
            debugCanvas.style.display = 'none';
        }
    } catch (err) {
        if (window.dbg) dbg('ERROR f#' + _frameCount + ': ' + (err.message || String(err)), 'err');
        console.error('OpenCV error:', err);
    }
    // src is created fresh each frame — must delete to prevent memory leak
    if (src && src.delete) src.delete();
    requestAnimationFrame(processVideo);
}

function processSheet(corners, W, H, frameMat) {
    let [tl, tr, bl, br] = corners;
    const outW = 600, outH = 848;
    let srcTri = null, dstTri = null, PM = null, warped = null, wGray = null, wBin = null;
    try {
        srcTri = cv.matFromArray(4, 1, cv.CV_32FC2, [tl.x, tl.y, tr.x, tr.y, br.x, br.y, bl.x, bl.y]);
        dstTri = cv.matFromArray(4, 1, cv.CV_32FC2, [0, 0, outW, 0, outW, outH, 0, outH]);
        PM = cv.getPerspectiveTransform(srcTri, dstTri);
        warped = new cv.Mat();
        cv.warpPerspective(frameMat, warped, PM, new cv.Size(outW, outH), cv.INTER_LINEAR, cv.BORDER_CONSTANT, new cv.Scalar());
        wGray = new cv.Mat(); wBin = new cv.Mat();
        cv.cvtColor(warped, wGray, cv.COLOR_RGBA2GRAY);
        cv.threshold(wGray, wBin, 0, 255, cv.THRESH_BINARY_INV | cv.THRESH_OTSU);

        let rawAnswers = readAllBubbles(wBin, outW, outH);
        let studentId  = readStudentId(wBin, outW, outH) || '00000000000';

        debugCanvas.width = outW; debugCanvas.height = outH;
        debugCanvas.style.display = 'block';
        cv.imshow('debug-canvas', warped);
        let base64Image = debugCanvas.toDataURL('image/jpeg', 0.7);

        if (!isSubmitting) {
            if (scanMode === 'key') {
                submitKey(JSON.stringify(rawAnswers));
            } else {
                submitScore(studentId, Object.keys(rawAnswers).length, JSON.stringify(rawAnswers), base64Image);
            }
        }
    } finally {
        if (srcTri) srcTri.delete(); if (dstTri) dstTri.delete(); if (PM) PM.delete();
        if (warped) warped.delete(); if (wGray)  wGray.delete();  if (wBin) wBin.delete();
    }
}

// ═══════════════════════════════════════════════════════════════
// BUBBLE LAYOUT — mirrors generate_pdf.php constants exactly
// outW=600 outH=848  =>  PX=600/210  PY=848/297
// ═══════════════════════════════════════════════════════════════
const PX = 600 / 210;
const PY = 848 / 297;
function mm2px(mm, axis) { return mm * (axis === 'x' ? PX : PY); }

const _MARG      = 12;
const _MK_SIZE   = 8;
const _MK_OFF    = 5;
const _BUB_R_MM  = 2.0;
const _BUB_DX_MM = 5.2;
const _SEC_MK_MM = 3.0;
const _BUB_PX    = mm2px(_BUB_R_MM * 1.15, 'x');

const _header_top    = _MK_OFF + _MK_SIZE + 3;
const _row2_y        = _header_top + 10 + 1;
const _info_y        = _row2_y + 10 + 2;
const _y_div1        = _info_y + 6;
const _sid_top       = _y_div1 + 3;
const _sid_y_start   = _sid_top + 13;
const _sid_dy        = 5.5;
const _digit_rows    = 10;
const _sid_block_bot = _sid_y_start + (_digit_rows - 1) * _sid_dy + _BUB_R_MM + 2;
const _y_div2        = _sid_block_bot + 2;
const _ans_start_y   = _y_div2 + 3;
const _first_row_y   = _ans_start_y + _SEC_MK_MM + 3;
const _ans_dy        = 5.5;

function readAllBubbles(binMat, W, H) {
    const opts = ['A','B','C','D','E'], n_opts = 5, n_cols = 5, rows_per = 10, q_count = 50;
    const usable_w  = 210 - _MARG * 2;
    const col_w_mm  = usable_w / n_cols;
    const q_label_w = 9;
    const content_w = q_label_w + (n_opts - 1) * _BUB_DX_MM;
    const offset_x  = (col_w_mm - content_w) / 2;
    let answers = {}, q = 1;
    for (let c = 0; c < n_cols && q <= q_count; c++) {
        let base_x_mm = _MARG + c * col_w_mm + offset_x;
        for (let r = 0; r < rows_per && q <= q_count; r++) {
            let qy_mm = _first_row_y + r * _ans_dy;
            let bestOpt = null, bestFill = 0;
            for (let oi = 0; oi < n_opts; oi++) {
                let bx_mm = base_x_mm + q_label_w + oi * _BUB_DX_MM;
                let bx_px = Math.round(mm2px(bx_mm, 'x'));
                let by_px = Math.round(mm2px(qy_mm,  'y'));
                let r_px  = Math.round(_BUB_PX);
                let x1 = Math.max(0, bx_px - r_px), y1 = Math.max(0, by_px - r_px);
                let x2 = Math.min(W-1, bx_px + r_px), y2 = Math.min(H-1, by_px + r_px);
                if (x2 <= x1 || y2 <= y1) continue;
                let roi = binMat.roi(new cv.Rect(x1, y1, x2-x1, y2-y1));
                let frac = cv.countNonZero(roi) / (roi.rows * roi.cols);
                roi.delete();
                if (frac > bestFill) { bestFill = frac; bestOpt = opts[oi]; }
            }
            if (bestFill >= FILL_FRAC) answers[q] = bestOpt;
            q++;
        }
    }
    return answers;
}

function readStudentId(binMat, W, H) {
    const digits = 11, digit_rows = 10;
    const sid_base_x_mm = _MARG + 10;
    let studentId = '';
    for (let col = 0; col < digits; col++) {
        let cx_mm = sid_base_x_mm + col * _BUB_DX_MM;
        let bestRow = -1, bestFill = 0;
        for (let row = 0; row < digit_rows; row++) {
            let cy_mm = _sid_y_start + row * _sid_dy;
            let bx_px = Math.round(mm2px(cx_mm, 'x'));
            let by_px = Math.round(mm2px(cy_mm,  'y'));
            let r_px  = Math.round(_BUB_PX);
            let x1 = Math.max(0, bx_px - r_px), y1 = Math.max(0, by_px - r_px);
            let x2 = Math.min(W-1, bx_px + r_px), y2 = Math.min(H-1, by_px + r_px);
            if (x2 <= x1 || y2 <= y1) continue;
            let roi = binMat.roi(new cv.Rect(x1, y1, x2-x1, y2-y1));
            let frac = cv.countNonZero(roi) / (roi.rows * roi.cols);
            roi.delete();
            if (frac > bestFill) { bestFill = frac; bestRow = row; }
        }
        studentId += (bestFill >= FILL_FRAC && bestRow >= 0) ? String(bestRow) : '?';
    }
    return studentId.includes('?') ? null : studentId;
}

// ════════════════════════════════════════════════════════════════
// SUBMISSION
// ════════════════════════════════════════════════════════════════
let scannedStudentIds = new Set();
let isSubmitting = false;
let examId   = document.getElementById('examId')?.value || document.querySelector('input[name="exam_id"]')?.value || 1;
let scanMode = 'student';

window.setScanMode = function(mode) {
    scanMode = mode;
    const btnStudent = document.getElementById('modeStudentBtn');
    const btnKey     = document.getElementById('modeKeyBtn');
    if (mode === 'student') {
        btnStudent.className = 'px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap bg-yellow-500 text-gray-900 shadow-md active:scale-95 transition-all';
        btnKey.className     = 'px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap text-gray-300 hover:text-white active:scale-95 transition-all';
        statusIndicator.textContent = 'โหมดตรวจกระดาษคำตอบ';
        statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
    } else {
        btnKey.className     = 'px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap bg-yellow-500 text-gray-900 shadow-md active:scale-95 transition-all';
        btnStudent.className = 'px-4 py-2 rounded-full text-xs md:text-sm font-bold whitespace-nowrap text-gray-300 hover:text-white active:scale-95 transition-all';
        statusIndicator.textContent = 'โหมดสร้างเฉลย (Scan as Key)';
        statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)';
    }
};

async function submitKey(rawAnswers) {
    if (isSubmitting) return;
    isSubmitting = true; _cooldown = true;
    statusIndicator.textContent = 'กำลังบันทึกเฉลย...';
    statusIndicator.style.backgroundColor = 'rgba(37, 99, 235, 0.9)';
    let examSet = document.getElementById('examSetScanner')?.value || 'A';
    const fd = new FormData();
    fd.append('exam_id', examId); fd.append('exam_set', examSet); fd.append('raw_answers', rawAnswers);
    try {
        const res  = await fetch('api/scan_key.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') {
            playBeep();
            statusIndicator.textContent = 'บันทึกเฉลยชุด ' + examSet + ' และตรวจใหม่ ' + data.regraded_count + ' คน';
            statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)';
        } else {
            statusIndicator.textContent = data.message;
            statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
        }
    } catch (e) {
        statusIndicator.textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้';
        statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
    }
    setTimeout(() => { isSubmitting = false; _cooldown = false; setScanMode('key'); }, 2000);
}

async function submitScore(studentId, score, rawAnswers, imageBase64) {
    rawAnswers  = rawAnswers  || '{}';
    imageBase64 = imageBase64 || '';
    if (isSubmitting) return;
    if (scannedStudentIds.has(studentId)) {
        statusIndicator.textContent = 'รหัสนิสิตนี้ตรวจไปแล้ว (สแกนซ้ำ)';
        statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
        
        // Fast cooldown reset for duplicates so it doesn't block the camera scan flow
        _cooldown = true;
        isSubmitting = true;
        setTimeout(() => {
            isSubmitting = false;
            _cooldown = false;
            statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
            statusIndicator.textContent = 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
            document.getElementById('root-container').classList.remove('success', 'error');
        }, 1500);
        return;
    }
    isSubmitting = true; _cooldown = true;
    statusIndicator.textContent = 'กำลังบันทึกคะแนน...';
    let examSet = document.getElementById('examSetScanner')?.value || 'A';
    const fd = new FormData();
    fd.append('exam_id', examId); fd.append('student_id', studentId); fd.append('score', score);
    fd.append('exam_set', examSet); fd.append('raw_answers', rawAnswers); fd.append('image', imageBase64);
    try {
        const res  = await fetch('api/scores.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.status === 'success') {
            scannedStudentIds.add(studentId);
            playBeep();
            statusIndicator.textContent = 'บันทึกคะแนนสำเร็จ';
            statusIndicator.style.backgroundColor = 'rgba(16, 185, 129, 0.9)';
            const resultCard = document.getElementById('scanResultCard');
            if (resultCard) {
                function escHtml(t) { return String(t).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
                document.getElementById('resStudentId').innerHTML = escHtml(studentId);
                document.getElementById('resScore').textContent = data.calculated_score !== undefined ? data.calculated_score : score;
                resultCard.classList.remove('hidden');
            }
        } else if (data.status === 'duplicate') {
            scannedStudentIds.add(studentId);
            statusIndicator.textContent = data.message;
            statusIndicator.style.backgroundColor = 'rgba(239, 68, 68, 0.9)';
        } else {
            statusIndicator.textContent = data.message;
        }
    } catch (e) { 
        statusIndicator.textContent = 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'; 
    }
    setTimeout(() => {
        isSubmitting = false; _cooldown = false;
        statusIndicator.style.backgroundColor = 'rgba(0,0,0,0.7)';
        statusIndicator.textContent = 'เล็งกล้องให้เห็นสี่เหลี่ยมครบ 4 มุม...';
        document.getElementById('root-container').classList.remove('success', 'error');
        const resultCard = document.getElementById('scanResultCard');
        if (resultCard) resultCard.classList.add('hidden');
    }, 2500);
}

// ── Manual Entry ──────────────────────────────────────────────
const manualModal     = document.getElementById('manualModal');
const btnManual       = document.getElementById('btnManual');
const btnCancelManual = document.getElementById('btnCancelManual');
const manualForm      = document.getElementById('manualForm');
btnManual.addEventListener('click', () => manualModal.showModal());
btnCancelManual.addEventListener('click', () => manualModal.close());
manualForm.addEventListener('submit', async e => {
    e.preventDefault();
    const studentId = document.getElementById('studentId').value;
    const score     = document.getElementById('score').value;
    manualModal.close();
    await submitScore(studentId, score);
    manualForm.reset();
});
